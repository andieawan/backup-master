<?php

namespace App\Console\Commands;

use App\Models\Sumber;
use App\Services\Backup\Katalog;
use App\Services\Backup\Notifikasi;
use App\Support\Jalankan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Periksa extends Command
{
    protected $signature = 'bm:periksa {--cepat : Lewati pemeriksaan database dan jaringan}';

    protected $description = 'Periksa kesiapan produksi (.env, database, folder data, kunci SSH, alat rsync/tar/mysql). Aman dijalankan kapan saja.';

    private int $gagal = 0;

    private int $peringatan = 0;

    private function ok(string $t): void
    {
        $this->line("  <info>[OK]</info>     $t");
    }

    private function peringatkan(string $t, string $saran = ''): void
    {
        $this->peringatan++;
        $this->line("  <comment>[PERINGATAN]</comment> $t".($saran ? "\n             → $saran" : ''));
    }

    private function gagalkan(string $t, string $saran = ''): void
    {
        $this->gagal++;
        $this->line("  <error>[GAGAL]</error>  $t".($saran ? "\n             → $saran" : ''));
    }

    public function handle(): int
    {
        $prod = app()->environment('production');
        $url = (string) config('app.url');
        $this->info('Pemeriksaan Backup Master — '.config('bm.lokasi').'  ('.config('app.env').')');

        $this->newLine();
        $this->line('<options=bold>PHP & berkas</>');
        version_compare(PHP_VERSION, '8.3.0', '>=') ? $this->ok('PHP '.PHP_VERSION) : $this->gagalkan('PHP '.PHP_VERSION.' terlalu lama', 'Butuh PHP 8.3+');
        $ext = array_values(array_filter(['pdo_mysql', 'mbstring', 'openssl', 'curl', 'xml', 'zip'], fn ($e) => ! extension_loaded($e)));
        $ext ? $this->gagalkan('Ekstensi PHP belum ada: '.implode(', ', $ext), 'sudo apt install '.implode(' ', array_map(fn ($e) => 'php-'.str_replace('pdo_mysql', 'mysql', $e), $ext)).' lalu muat ulang php-fpm') : $this->ok('Ekstensi PHP lengkap');
        is_file(base_path('vendor/autoload.php')) ? $this->ok('vendor/ ada') : $this->gagalkan('vendor/ tidak ada', 'composer install --no-dev --optimize-autoloader');
        foreach (['storage', 'storage/logs', 'storage/framework', 'bootstrap/cache'] as $d) {
            if (! is_writable(base_path($d))) {
                $this->gagalkan("Folder $d tidak bisa ditulis user ini (".get_current_user().')', 'sudo chown -R $USER:www-data storage bootstrap/cache && sudo chmod -R ug+rwX storage bootstrap/cache');
            }
        }

        $this->newLine();
        $this->line('<options=bold>.env</>');
        config('app.key') ? $this->ok('APP_KEY terisi') : $this->gagalkan('APP_KEY kosong', 'php artisan key:generate --force');
        if ($prod) {
            config('app.debug') ? $this->gagalkan('APP_DEBUG=true di produksi', 'Ubah ke APP_DEBUG=false') : $this->ok('APP_DEBUG=false');
            str_starts_with($url, 'https://') ? $this->ok('APP_URL memakai https') : $this->line("  <comment>[INFO]</comment>   APP_URL http ($url): wajar bila hanya dibuka dari jaringan sekolah / lewat VPN.");
            if (str_starts_with($url, 'https://') && ! config('session.secure')) {
                $this->peringatkan('SESSION_SECURE_COOKIE belum true padahal HTTPS', 'Set SESSION_SECURE_COOKIE=true');
            }
            if (str_starts_with($url, 'http://') && config('session.secure')) {
                $this->gagalkan('SESSION_SECURE_COOKIE=true tetapi APP_URL http — login akan terus gagal', 'Pakai HTTPS atau set SESSION_SECURE_COOKIE=false');
            }
            $host = (string) parse_url($url, PHP_URL_HOST);
            if (in_array($host, ['localhost', '127.0.0.1'], true) || str_ends_with($host, '.test')) {
                $this->peringatkan('APP_URL menunjuk alamat lokal', 'Isi domain asli');
            }
        }
        config('app.trusted_proxies') ? $this->ok('TRUSTED_PROXIES = '.config('app.trusted_proxies')) : $this->line('  <comment>[INFO]</comment>   Diakses langsung (tanpa reverse proxy). Backup Master sebaiknya TIDAK dibuka ke internet.');

        if (! $this->option('cepat')) {
            $this->newLine();
            $this->line('<options=bold>Database</>');
            try {
                DB::connection()->getPdo();
                $this->ok('Terhubung ke database "'.DB::connection()->getDatabaseName().'"');
                if (! Schema::hasTable('migrations')) {
                    $this->gagalkan('Tabel belum dibuat', 'php artisan migrate --force');
                } else {
                    $belum = collect(glob(database_path('migrations/*.php')))->map(fn ($f) => basename($f, '.php'))->diff(DB::table('migrations')->pluck('migration'));
                    $belum->isEmpty() ? $this->ok('Semua migrasi sudah dijalankan') : $this->gagalkan($belum->count().' migrasi belum dijalankan', 'php artisan migrate --force');
                }
            } catch (\Throwable $e) {
                $this->gagalkan('Tidak bisa terhubung ke database: '.mb_substr($e->getMessage(), 0, 160), 'Periksa DB_* di .env (DB_PASSWORD tidak boleh kosong), lalu php artisan config:clear');
            }
        }

        $this->newLine();
        $this->line('<options=bold>Backup</>');
        foreach (['rsync', 'ssh', 'tar', 'gzip', config('bm.mysql_bin', 'mysql')] as $p) {
            Jalankan::ada($p) ? $this->ok("$p ada") : $this->gagalkan("$p belum terpasang", 'sudo apt install rsync openssh-client tar gzip mariadb-client');
        }
        $data = Katalog::dir();
        if (! is_dir($data)) {
            $this->gagalkan("Folder data $data tidak ada", 'Periksa BM_DATA_DIR; installer membuatnya otomatis');
        } else {
            is_writable($data) ? $this->ok("Folder data $data bisa ditulis") : $this->gagalkan("Folder data $data tidak bisa ditulis user ".get_current_user(), "sudo chown -R www-data:www-data $data");
            $total = @disk_total_space($data) ?: 0;
            $bebas = @disk_free_space($data) ?: 0;
            $persen = $total ? round(($total - $bebas) / $total * 100) : 0;
            $persen < 85 ? $this->ok("Disk data terpakai $persen%") : $this->peringatkan("Disk data terpakai $persen%", 'Kurangi BM_SIMPAN_* atau tambah disk');
        }
        $kunci = (string) config('bm.ssh_key');
        is_readable($kunci) && is_readable($kunci.'.pub') ? $this->ok('Kunci SSH ada: '.$kunci.'.pub') : $this->peringatkan('Kunci SSH belum ada / tidak terbaca', 'Jalankan ulang deploy/install.sh (membuat kunci untuk www-data)');
        Notifikasi::aktif() ? $this->ok('Notifikasi Telegram diatur') : $this->peringatkan('Notifikasi Telegram belum diatur', 'Isi BM_TELEGRAM_TOKEN & BM_TELEGRAM_CHAT agar kegagalan backup terkabar');
        if (! $this->option('cepat')) {
            try {
                $n = Sumber::where('aktif', true)->count();
                $n ? $this->ok("$n sumber aktif") : $this->peringatkan('Belum ada sumber', 'Tambahkan di menu Sumber');
                foreach (Sumber::where('aktif', true)->where('terakhir_status', 'gagal')->get() as $x) {
                    $this->peringatkan("Sumber {$x->nama} gagal terakhir kali: ".mb_substr((string) $x->terakhir_pesan, 0, 200));
                }
                $c = config('database.connections.mysql');
                [$k, , $err] = Jalankan::shell(implode(' ', array_map('escapeshellarg', [config('bm.mysql_bin', 'mysql'), '-h', (string) $c['host'], '-P', (string) $c['port'], '-u', (string) $c['username']]))
                    .' -e '.escapeshellarg('CREATE DATABASE IF NOT EXISTS `'.config('bm.jelajah_prefix').'cek`; DROP DATABASE `'.config('bm.jelajah_prefix').'cek`'), 30, ['MYSQL_PWD' => (string) $c['password']]);
                $k === 0 ? $this->ok('User DB boleh membuat database '.config('bm.jelajah_prefix').'* (uji pulih)') : $this->gagalkan('User DB tidak boleh membuat database uji pulih: '.Jalankan::ringkas($err, 160), "GRANT ALL ON `".config('bm.jelajah_prefix')."%`.* TO '".$c['username']."'@'localhost';");
            } catch (\Throwable $e) {
                $this->peringatkan('Pemeriksaan sumber dilewati: '.mb_substr($e->getMessage(), 0, 120));
            }
        }

        $this->newLine();
        if ($this->gagal) {
            $this->error("{$this->gagal} masalah harus diperbaiki, {$this->peringatan} peringatan.");

            return self::FAILURE;
        }
        $this->info($this->peringatan ? "Siap, dengan {$this->peringatan} peringatan." : 'Semua siap untuk produksi.');

        return self::SUCCESS;
    }
}
