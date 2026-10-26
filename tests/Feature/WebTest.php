<?php

namespace Tests\Feature;

use App\Models\Aplikasi;
use App\Models\AuditLog;
use App\Models\Berkas;
use App\Models\Sumber;
use App\Models\User;
use App\Services\Backup\Penarik;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class WebTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $t = $this->siapkanFolder();
        $this->sqliteBackup("$t/vm/backups/absensi_2026-10-26T02-00-00.sqlite3");
        File::ensureDirectoryExists("$t/vm/isi/surat");
        file_put_contents("$t/vm/isi/surat/a.pdf", '%PDF-1.4 isi surat');
        exec('tar -czf '.escapeshellarg("$t/vm/backups/berkas_2026-10-26_0130.tar.gz").' -C '.escapeshellarg("$t/vm/isi").' .');
        File::ensureDirectoryExists("$t/vm/private/bk");
        file_put_contents("$t/vm/private/bk/s.pdf", '%PDF cermin');
        config(['bm.wajib_2fa' => false]);
        $this->admin = User::create(['username' => 'admin', 'nama' => 'Admin', 'roles' => ['admin'], 'aktif' => true, 'password' => 'password-panjang-12']);
        Penarik::tarik(Sumber::create(['kode' => 'absensi', 'nama' => 'Absensi', 'jenis' => 'aplikasi', 'folder' => "$t/vm",
            'arsip' => 'backups', 'cermin' => 'private', 'aktif' => true]));
    }

    public function test_tamu_harus_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/jelajah')->assertRedirect('/login');
    }

    public function test_login_dengan_2fa(): void
    {
        $r = Totp::rahasiaBaru();
        $this->admin->update(['totp_secret' => $r, 'totp_aktif_pada' => now()]);
        $this->post('/login', ['username' => 'admin', 'password' => 'salah'])->assertSessionHasErrors('username');
        $this->post('/login', ['username' => 'admin', 'password' => 'password-panjang-12'])->assertRedirect(route('login.2fa'));
        $this->assertGuest();
        $this->post('/login/kode', ['kode' => '000000'])->assertSessionHasErrors('kode');
        $this->post('/login/kode', ['kode' => Totp::kode($r)])->assertRedirect(route('beranda'));
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_wajib_2fa_mengarahkan_ke_akun(): void
    {
        config(['bm.wajib_2fa' => true]);
        $this->actingAs($this->admin)->get('/')->assertRedirect(route('akun'));
        $this->get('/akun')->assertOk()->assertSee('kunci penyiapan');
        $rahasia = session('totp_baru');
        $this->post('/akun/2fa', ['kode' => Totp::kode($rahasia)])->assertRedirect(route('beranda'));
        $this->assertTrue($this->admin->fresh()->punya2fa());
        $this->get('/')->assertOk()->assertSee('Absensi Siswa');
    }

    public function test_jelajah_menyembunyikan_kolom_rahasia(): void
    {
        $this->actingAs($this->admin)->get('/jelajah/absensi')->assertOk()->assertSee('students');
        $this->get('/jelajah/absensi/students?q=Ani')->assertOk()->assertSee('Ani')->assertDontSee('Budi');
        $r = $this->get('/jelajah/absensi/users')->assertOk()->assertSee('admin');
        $r->assertDontSee('RAHASIAHASH')->assertDontSee('tok123')->assertDontSee('remember_token');
        $this->get('/jelajah/absensi/users/1')->assertOk()->assertDontSee('RAHASIAHASH');
        $this->get('/jelajah/absensi/tabel_tidak_ada')->assertRedirect();
    }

    public function test_tabel_rahasia_butuh_alasan_dan_tercatat(): void
    {
        $this->actingAs($this->admin)->get('/jelajah/absensi/bk_records')->assertOk()->assertSee('Tulis alasan')->assertDontSee('catatan konseling rahasia');
        $this->get('/jelajah/absensi/bk_records/1')->assertForbidden();
        $this->post('/jelajah/absensi/bk_records/alasan', ['alasan' => 'iseng'])->assertSessionHas('error');
        $this->post('/jelajah/absensi/bk_records/alasan', ['alasan' => 'memeriksa pemulihan server BK'])->assertRedirect();
        $this->get('/jelajah/absensi/bk_records')->assertOk()->assertSee('catatan konseling rahasia');
        $this->assertTrue(AuditLog::where('aksi', 'Buka tabel rahasia')->where('rincian', 'like', '%memeriksa pemulihan server BK%')->exists());
    }

    public function test_isi_arsip_dan_potret_aman(): void
    {
        $a = Berkas::where('jenis', 'arsip')->firstOrFail();
        $this->actingAs($this->admin)->get("/berkas/{$a->id}")->assertOk()->assertSee('surat/a.pdf');
        $r = $this->get("/berkas/{$a->id}/isi?f=surat/a.pdf")->assertOk()->assertHeader('Content-Security-Policy', 'sandbox');
        $this->assertSame('%PDF-1.4 isi surat', $r->streamedContent());
        $this->get("/berkas/{$a->id}/isi?f=../../etc/passwd")->assertRedirect();
        $c = Berkas::where('jenis', 'cermin')->firstOrFail();
        $this->get("/berkas/{$c->id}?p=bk")->assertOk()->assertSee('s.pdf');
        $this->get("/berkas/{$c->id}?p=../../..")->assertRedirect();
        $this->get("/berkas/{$c->id}/isi?f=bk/s.pdf")->assertOk();
        $d = Berkas::where('jenis', 'db')->firstOrFail();
        $this->get("/berkas/{$d->id}")->assertOk()->assertSee('Langkah memulihkan');
        $this->get("/berkas/{$d->id}/unduh")->assertOk();
        $this->assertTrue(AuditLog::where('aksi', 'Unduh backup')->exists());
    }

    public function test_halaman_utama_dan_sumber(): void
    {
        $this->actingAs($this->admin)->get('/')->assertOk()->assertSee('Absensi Siswa');
        $this->get('/aplikasi/absensi')->assertOk()->assertSee('absensi_2026-10-26T02-00-00.sqlite3');
        $this->get('/sumber')->assertOk();
        $this->get('/sumber?templat=tu')->assertOk()->assertSee('/var/backups/tu-pusat');
        $this->post('/sumber', ['jenis' => 'aplikasi', 'kode' => 'Bad Kode!', 'nama' => 'x', 'folder' => '/x'])->assertSessionHas('error');
        $this->post('/sumber', ['jenis' => 'aplikasi', 'kode' => 'tu', 'nama' => 'TU', 'host' => '192.168.10.23', 'folder' => '/var/backups/tu-pusat', 'arsip' => '.'])->assertRedirect();
        $s = Sumber::where('kode', 'tu')->firstOrFail();
        $this->get("/sumber/{$s->id}")->assertOk()->assertSee('siapkan-sumber.sh');
        $this->post("/sumber/{$s->id}/tarik")->assertRedirect();
        $this->assertTrue($s->fresh()->perlu_tarik);
        $this->put('/aplikasi/absensi', ['nama' => 'Absensi', 'rahasia' => 'bk_records, students'])->assertRedirect();
        $this->assertSame(['bk_records', 'students'], Aplikasi::where('kode', 'absensi')->first()->rahasia);
        $this->get('/audit')->assertOk()->assertSee('Tambah sumber');
    }

    public function test_perintah_konsol(): void
    {
        $this->assertSame(0, Artisan::call('bm:uji', ['kode' => 'absensi']));
        $this->assertSame(0, Artisan::call('bm:ringkasan'));
        $this->assertStringContainsString('Absensi Siswa', Artisan::output());
        $this->assertSame(0, Artisan::call('bm:buat-admin', ['username' => 'kedua', '--password' => 'password-panjang-12']));
        $this->assertSame(1, Artisan::call('bm:buat-admin', ['username' => 'ketiga', '--password' => 'pendek']));
    }
}
