<?php

namespace Tests\Feature;

use App\Models\Aplikasi;
use App\Models\Berkas;
use App\Models\Sumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** API untuk WOL Panel: kunci + IP, status backup, penerus notifikasi Telegram. */
class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function api(string $method, string $url, array $data = [], string $kunci = 'k-rahasia', string $ip = '192.168.10.28')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->withHeaders(['Authorization' => "Bearer $kunci", 'Accept' => 'application/json'])
            ->json($method, $url, $data);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['bm.api_kunci' => 'k-rahasia', 'bm.api_ip' => '192.168.10.28']);
    }

    public function test_api_mati_tanpa_kunci_dan_menolak_kunci_atau_ip_salah(): void
    {
        $this->api('GET', '/api/status', [], 'salah')->assertStatus(401);
        $this->api('GET', '/api/status', [], 'k-rahasia', '192.168.10.99')->assertStatus(403);
        config(['bm.api_kunci' => '']);
        $this->api('GET', '/api/status')->assertStatus(404);
    }

    public function test_status_backup_per_aplikasi_dengan_host(): void
    {
        Sumber::create(['kode' => 'bk', 'nama' => 'BK', 'jenis' => 'aplikasi', 'host' => '192.168.10.23', 'pengguna' => 'bmtarik', 'port' => 22,
            'folder' => '/var/backups/bk-pusat', 'arsip' => '.', 'jam' => '02:30', 'aktif' => true]);
        Aplikasi::pastikan('bk', ['nama' => 'Bimbingan Konseling']);
        Aplikasi::pastikan('wol', ['nama' => 'WOL Panel']);
        Berkas::create(['aplikasi' => 'bk', 'jenis' => 'db', 'nama' => 'db_x.sql.gz', 'path' => 'bk/arsip/db_x.sql.gz', 'ukuran' => 10, 'hari' => date('Y-m-d'),
            'waktu' => now()->subHours(3), 'utuh' => true]);
        $r = $this->api('GET', '/api/status')->assertOk()->json('aplikasi');
        $bk = collect($r)->firstWhere('kode', 'bk');
        $this->assertSame(['192.168.10.23', [], 'db', true], [$bk['host'], $bk['masalah'], $bk['jenis'], $bk['utuh']]);
        $this->assertEqualsWithDelta(3, $bk['umur_jam'], 0.2);
        $wol = collect($r)->firstWhere('kode', 'wol');
        $this->assertSame(['arsip', null], [$wol['jenis'], $wol['terbaru']]);
        $this->assertNotEmpty($wol['masalah']);
    }

    public function test_notifikasi_diteruskan_ke_telegram(): void
    {
        $this->api('POST', '/api/notifikasi', ['teks' => 'VM BK tidak terjangkau'])->assertStatus(409);
        config(['bm.telegram_token' => 't', 'bm.telegram_chat' => '1']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->api('POST', '/api/notifikasi', ['teks' => 'VM BK tidak terjangkau', 'dari' => 'WOL Panel'])->assertOk()->assertJson(['ok' => true]);
        Http::assertSent(fn ($req) => str_contains((string) $req['text'], 'WOL Panel: VM BK tidak terjangkau'));
        $this->api('POST', '/api/notifikasi', [])->assertStatus(422);
    }
}
