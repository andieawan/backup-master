<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Sumber;
use App\Models\User;
use App\Services\Backup\Penarik;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** Master App: cari di semua aplikasi, profil siswa/guru lintas aplikasi, unduh Excel. */
class DataTest extends TestCase
{
    use RefreshDatabase;

    private const UUID = '11111111-2222-3333-4444-555555555555';

    private const GURU = '99999999-8888-7777-6666-555555555555';

    private User $admin;

    private function db(string $t, string $kode, array $sql): void
    {
        $path = "$t/vm-$kode/backups/{$kode}_2026-11-08T02-00-00.sqlite3";
        File::ensureDirectoryExists(dirname($path));
        $p = new \PDO('sqlite:'.$path);
        foreach ($sql as $q) {
            $p->exec($q);
        }
        $p = null;
        Penarik::tarik(Sumber::create(['kode' => $kode, 'nama' => $kode, 'jenis' => 'aplikasi', 'folder' => "$t/vm-$kode", 'arsip' => 'backups', 'aktif' => true]));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $t = $this->siapkanFolder();
        config(['bm.wajib_2fa' => false]);
        $this->admin = User::create(['username' => 'admin', 'nama' => 'Admin', 'roles' => ['admin'], 'aktif' => true, 'password' => 'password-panjang-12']);
        $u = self::UUID;
        $g = self::GURU;
        $this->db($t, 'pusat', [
            'CREATE TABLE siswa (id INTEGER PRIMARY KEY, uuid TEXT, nis TEXT, nisn TEXT, nama TEXT, status TEXT, no_hp_ortu TEXT)',
            "INSERT INTO siswa (uuid, nis, nisn, nama, status, no_hp_ortu) VALUES ('$u', '01234', '0099', 'Budi Santoso', 'aktif', '0812')",
            'CREATE TABLE pegawai (id INTEGER PRIMARY KEY, uuid TEXT, nip TEXT, nama TEXT, jabatan TEXT)',
            "INSERT INTO pegawai (uuid, nip, nama, jabatan) VALUES ('$g', '1980', 'Bu Sari', 'Guru')",
            'CREATE TABLE users (id INTEGER PRIMARY KEY, nama TEXT, username TEXT, password TEXT)',
            "INSERT INTO users (nama, username, password) VALUES ('Budi Santoso', 'budi', 'HASHRAHASIA')",
        ]);
        $this->db($t, 'jurnal', [
            'CREATE TABLE students (id INTEGER PRIMARY KEY, nis TEXT, nama TEXT, pusat_uuid TEXT)',
            "INSERT INTO students (nis, nama, pusat_uuid) VALUES ('01234', 'Budi Santoso', '$u')",
            'CREATE TABLE attendance (id INTEGER PRIMARY KEY, student_id INTEGER, tanggal TEXT, status TEXT, notes TEXT)',
            "INSERT INTO attendance (student_id, tanggal, status) VALUES (1, '2026-11-02', 'H'), (1, '2026-11-03', 'S')",
            'CREATE TABLE grade_activities (id INTEGER PRIMARY KEY, teacher_id INTEGER, nama_kegiatan TEXT, tanggal_kegiatan TEXT)',
            "INSERT INTO grade_activities (teacher_id, nama_kegiatan, tanggal_kegiatan) VALUES (1, 'Ulangan Bab 1', '2026-11-04')",
            'CREATE TABLE grade_values (activity_id INTEGER, student_id INTEGER, nilai TEXT)',
            "INSERT INTO grade_values VALUES (1, 1, '88')",
            'CREATE TABLE users (id INTEGER PRIMARY KEY, nama TEXT, username TEXT, password_hash TEXT, pegawai_uuid TEXT)',
            "INSERT INTO users (nama, username, password_hash, pegawai_uuid) VALUES ('Bu Sari', 'sari', 'HASHRAHASIA', '$g')",
            'CREATE TABLE jurnal (id INTEGER PRIMARY KEY, user_id INTEGER, tanggal TEXT, jam_ke TEXT, materi TEXT, kegiatan TEXT, catatan TEXT)',
            "INSERT INTO jurnal (user_id, tanggal, jam_ke, materi) VALUES (1, '2026-11-05', '1-2', 'Tipografi dasar')",
        ]);
        $this->db($t, 'bk', [
            'CREATE TABLE siswa (id INTEGER PRIMARY KEY, pusat_uuid TEXT, nis TEXT, nisn TEXT, nama TEXT)',
            "INSERT INTO siswa (pusat_uuid, nis, nama) VALUES ('$u', '01234', 'Budi Santoso')",
            'CREATE TABLE bk_catatan (id INTEGER PRIMARY KEY, siswa_id INTEGER, tanggal TEXT, jenis TEXT, kategori TEXT, judul TEXT, uraian TEXT, tindak_lanjut TEXT, status TEXT)',
            "INSERT INTO bk_catatan (siswa_id, tanggal, jenis, judul, uraian) VALUES (1, '2026-11-01', 'kasus', 'Sering terlambat', 'uraian konseling rahasia')",
        ]);
        $this->db($t, 'pkl', [
            'CREATE TABLE siswa (id INTEGER PRIMARY KEY, pusat_uuid TEXT, nis TEXT, nama TEXT)',
            "INSERT INTO siswa (pusat_uuid, nis, nama) VALUES ('$u', '01234', 'Budi Santoso')",
            'CREATE TABLE dudi (id INTEGER PRIMARY KEY, nama TEXT, alamat TEXT, bidang TEXT)',
            "INSERT INTO dudi (nama) VALUES ('CV Kreatif Jember')",
            'CREATE TABLE penempatan (id INTEGER PRIMARY KEY, siswa_id INTEGER, dudi_id INTEGER, mulai TEXT, selesai TEXT, status TEXT)',
            "INSERT INTO penempatan (siswa_id, dudi_id, mulai, status) VALUES (1, 1, '2026-08-01', 'aktif')",
            'CREATE TABLE absensi (id INTEGER PRIMARY KEY, penempatan_id INTEGER, tanggal TEXT, status TEXT, jam_masuk TEXT, jam_pulang TEXT)',
            "INSERT INTO absensi (penempatan_id, tanggal, status, jam_masuk) VALUES (1, '2026-08-02', 'hadir', '07:10')",
        ]);
    }

    public function test_cari_menemukan_data_di_semua_aplikasi_dan_menyembunyikan_rahasia(): void
    {
        $r = $this->actingAs($this->admin)->get('/cari?q=Budi')->assertOk();
        $r->assertSee('Pusat Sekolah')->assertSee('Jurnal Guru')->assertSee('Bimbingan Konseling')->assertSee('Manajemen PKL');
        $r->assertSee(route('profil', ['siswa', self::UUID]), false)->assertDontSee('HASHRAHASIA');
        $this->get('/cari?q=konseling')->assertOk()->assertSee('bk_catatan')->assertSee('rahasia')->assertDontSee('uraian konseling rahasia');
        $this->get('/cari?q=Tipografi')->assertOk()->assertSee('Tipografi dasar');
        $this->get('/cari?q=x')->assertOk()->assertSee('minimal 2 huruf');
        $this->assertTrue(AuditLog::where('aksi', 'Cari semua aplikasi')->exists());
    }

    public function test_profil_siswa_menggabungkan_data_semua_aplikasi(): void
    {
        $r = $this->actingAs($this->admin)->get(route('profil', ['siswa', self::UUID]))->assertOk();
        $r->assertSee('Budi Santoso')->assertSee('01234')->assertSee('0812');               // Pusat
        $r->assertSee('Absensi')->assertSee('2026-11-03')->assertSee('Ulangan Bab 1')->assertSee('88'); // Jurnal
        $r->assertSee('Catatan BK')->assertSee('Buka dengan alasan')->assertDontSee('Sering terlambat'); // BK rahasia
        $r->assertSee('CV Kreatif Jember')->assertSee('Absensi PKL')->assertSee('07:10');   // PKL (lewat penempatan)

        $this->post('/jelajah/bk/bk_catatan/alasan', ['alasan' => 'pemeriksaan wali kelas oleh admin']);
        $this->get(route('profil', ['siswa', self::UUID]))->assertSee('Sering terlambat');
        $this->get(route('profil', ['siswa', '00000000-0000-0000-0000-000000000000']))->assertNotFound();
    }

    public function test_profil_guru(): void
    {
        $this->actingAs($this->admin)->get(route('profil', ['guru', self::GURU]))->assertOk()
            ->assertSee('Bu Sari')->assertSee('Jurnal mengajar')->assertSee('Tipografi dasar')->assertSee('Kegiatan nilai');
    }

    public function test_unduh_excel_tanpa_kolom_rahasia(): void
    {
        $r = $this->actingAs($this->admin)->get('/jelajah/jurnal/users/ekspor')->assertOk();
        $this->assertStringContainsString('spreadsheetml', $r->headers->get('Content-Type'));
        $zip = new \ZipArchive;
        $zip->open($r->baseResponse->getFile()->getPathname());
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertStringContainsString('Bu Sari', $sheet);
        $this->assertStringNotContainsString('HASHRAHASIA', $sheet);
        $this->assertStringNotContainsString('password_hash', $sheet);
        $this->get('/jelajah/bk/bk_catatan/ekspor')->assertForbidden();
        $this->assertTrue(AuditLog::where('aksi', 'Ekspor tabel')->exists());
    }

    public function test_ringkasan_bernama_master_app_dengan_jumlah_data(): void
    {
        $this->actingAs($this->admin)->get('/')->assertOk()->assertSee('Master App')->assertSee('1 siswa')->assertSee('Cari &amp; Profil', false);
    }
}
