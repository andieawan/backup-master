<?php

namespace Tests\Feature;

use App\Models\Aplikasi;
use App\Models\Berkas;
use App\Models\Sumber;
use App\Models\UjiPulih;
use App\Services\Backup\Katalog;
use App\Services\Backup\Penarik;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TarikTest extends TestCase
{
    use RefreshDatabase;

    private string $vm;

    protected function setUp(): void
    {
        parent::setUp();
        $t = $this->siapkanFolder();
        $this->vm = "$t/vm-absensi";
        $this->sqliteBackup("$this->vm/backups/absensi_2026-10-26T02-00-00.sqlite3");
        file_put_contents("$this->vm/backups/abaikan.txt", 'x');
        File::ensureDirectoryExists("$this->vm/private/bk-surat");
        file_put_contents("$this->vm/private/bk-surat/s1.pdf", '%PDF-1.4 surat');
        $gz = gzopen("$this->vm/backups/rusak_2026-10-20_0100.sql.gz", 'w');
        gzwrite($gz, 'CREATE TABLE x (id int);');
        gzclose($gz);
        file_put_contents("$this->vm/backups/rusak_2026-10-20_0100.sql.gz", substr(file_get_contents("$this->vm/backups/rusak_2026-10-20_0100.sql.gz"), 0, 12)); // terpotong
        touch("$this->vm/backups/rusak_2026-10-20_0100.sql.gz", strtotime('-3 days'));
    }

    private function sumber(): Sumber
    {
        return Sumber::create(['kode' => 'absensi', 'nama' => 'Absensi', 'jenis' => 'aplikasi', 'folder' => $this->vm,
            'arsip' => 'backups', 'cermin' => 'private', 'jam' => '02:30', 'aktif' => true]);
    }

    public function test_tarik_lokal_katalog_cermin_dan_uji_pulih(): void
    {
        $t = Penarik::tarik($this->sumber());
        $this->assertSame('ok', $t->status, (string) $t->pesan);
        $this->assertStringContainsString('rusak_2026-10-20_0100.sql.gz', (string) $t->pesan);
        $this->assertFileExists(Katalog::dir('absensi/arsip/absensi_2026-10-26T02-00-00.sqlite3'));
        $this->assertFileDoesNotExist(Katalog::dir('absensi/arsip/abaikan.txt'));
        $this->assertFileExists(Katalog::dir('absensi/cermin/'.\App\Support\Dates::today().'/bk-surat/s1.pdf'));
        $this->assertFalse(Berkas::where('nama', 'rusak_2026-10-20_0100.sql.gz')->value('utuh'));
        $this->assertSame(3, Berkas::count());
        $u = UjiPulih::firstOrFail();
        $this->assertSame('ok', $u->status);
        $this->assertSame(3, $u->tabel);
        $this->assertSame(2, $u->rincian['students']);
        $this->assertSame('Absensi Siswa', Aplikasi::where('kode', 'absensi')->value('nama')); // dari templat
        $this->assertFileExists(Katalog::dir('MANIFEST.json'));
        $this->assertFileExists(Katalog::dir('absensi/info.json'));
    }

    public function test_berkas_yang_sudah_ada_tidak_ditimpa_sumber(): void
    {
        $s = $this->sumber();
        Penarik::tarik($s);
        $asli = hash_file('sha256', Katalog::dir('absensi/arsip/absensi_2026-10-26T02-00-00.sqlite3'));
        file_put_contents("$this->vm/backups/absensi_2026-10-26T02-00-00.sqlite3", 'TERENKRIPSI RANSOMWARE');
        Penarik::tarik($s);
        $this->assertSame($asli, hash_file('sha256', Katalog::dir('absensi/arsip/absensi_2026-10-26T02-00-00.sqlite3')));
    }

    public function test_retensi_menghapus_dan_tidak_menarik_ulang(): void
    {
        config(['bm.simpan_harian' => 2, 'bm.simpan_mingguan' => 0, 'bm.simpan_bulanan' => 0]);
        foreach (['2026-10-01', '2026-10-24', '2026-10-25'] as $h) {
            $this->sqliteBackup("$this->vm/backups/absensi_{$h}T02-00-00.sqlite3");
        }
        $s = $this->sumber();
        Penarik::tarik($s);
        $this->assertFileDoesNotExist(Katalog::dir('absensi/arsip/absensi_2026-10-01T02-00-00.sqlite3'));
        $this->assertFileDoesNotExist(Katalog::dir('absensi/arsip/absensi_2026-10-24T02-00-00.sqlite3'));
        $this->assertContains('absensi/arsip/absensi_2026-10-01T02-00-00.sqlite3', Katalog::terhapus());
        Penarik::tarik($s->fresh());
        $this->assertFileDoesNotExist(Katalog::dir('absensi/arsip/absensi_2026-10-01T02-00-00.sqlite3'));
    }

    public function test_salinan_menarik_dari_master_dan_memeriksa_sha(): void
    {
        Penarik::tarik($this->sumber());
        $utama = Katalog::dir();
        // salinan: folder data lain, database katalog sama (cukup untuk uji)
        $man = json_decode(file_get_contents("$utama/MANIFEST.json"), true);
        foreach ($man['berkas'] as &$m) {
            if (str_contains($m['path'], 'rusak_')) {
                $m['sha256'] = str_repeat('0', 64);
            }
        }
        unset($m);
        file_put_contents("$utama/MANIFEST.json", json_encode($man));
        Berkas::query()->delete();
        UjiPulih::query()->delete();
        config(['bm.data_dir' => $this->tmp.'/data2']);
        $s = Sumber::create(['kode' => 'utama', 'nama' => 'Utama', 'jenis' => 'master', 'folder' => $utama, 'aktif' => true]);
        $t = Penarik::tarik($s);
        $this->assertFileExists($this->tmp.'/data2/absensi/arsip/absensi_2026-10-26T02-00-00.sqlite3');
        $this->assertDirectoryExists($this->tmp.'/data2/absensi/cermin/'.\App\Support\Dates::today());
        $this->assertSame('ok', UjiPulih::where('aplikasi', 'absensi')->value('status')); // salinan juga menguji pulih
        $this->assertStringContainsString('Berbeda dengan sumber', (string) $t->pesan);
    }

    public function test_jadwal_jatuh_tempo(): void
    {
        $s = $this->sumber();
        $pagi = CarbonImmutable::parse('2026-10-27 02:00', 'Asia/Jakarta');
        $this->assertTrue(Penarik::jatuhTempo($pagi)->contains('id', $s->id)); // belum pernah berhasil
        $s->update(['terakhir_berhasil' => CarbonImmutable::parse('2026-10-26 02:45', 'Asia/Jakarta')->utc(), 'terakhir_status' => 'ok']);
        $this->assertFalse(Penarik::jatuhTempo($pagi)->contains('id', $s->id));                       // jadwal 26 Okt 02:30 sudah lewat & berhasil
        $this->assertTrue(Penarik::jatuhTempo($pagi->setTime(9, 0))->contains('id', $s->id));         // komputer baru menyala pukul 09:00
        $s->update(['terakhir_status' => 'gagal', 'terakhir_mulai' => $pagi->setTime(8, 30)->utc()]);
        $this->assertFalse(Penarik::jatuhTempo($pagi->setTime(9, 0))->contains('id', $s->id));        // gagal: tunggu 60 menit
        $this->assertTrue(Penarik::jatuhTempo($pagi->setTime(9, 31))->contains('id', $s->id));
        $s->update(['perlu_tarik' => true, 'terakhir_status' => 'ok', 'terakhir_berhasil' => now()]);
        $this->assertTrue(Penarik::jatuhTempo()->contains('id', $s->id));
    }
}
