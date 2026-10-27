<?php

namespace App\Services\Backup;

use App\Models\Aplikasi;
use App\Models\Berkas;
use App\Models\UjiPulih;
use App\Services\Audit;
use App\Support\Jalankan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Uji pulih: dump database dimuat ke database terpisah di mesin ini (bmj_<kode>), dihitung baris per tabel,
 * dibandingkan dengan uji sebelumnya. Database itu sekaligus menjadi isi menu Jelajah Data (baca saja).
 * Database aplikasi aslinya TIDAK pernah disentuh.
 */
class Pemulih
{
    public static function namaDb(string $kode): string
    {
        return config('bm.jelajah_prefix', 'bmj_').str_replace('-', '_', $kode);
    }

    /** Koneksi MySQL memakai user uji (hanya bmj_*). Tanpa user uji, uji pulih MySQL ditolak demi keamanan. */
    public static function mysqlUji(string $db): array
    {
        $u = (string) config('bm.uji_db_username');
        if ($u === '') {
            throw new \RuntimeException('BM_UJI_DB_USERNAME belum diisi: uji pulih database MySQL butuh user DB khusus yang hanya berhak atas '.config('bm.jelajah_prefix').'* (lihat docs/PASANG.md).');
        }

        return array_merge(config('database.connections.mysql'), ['database' => $db, 'username' => $u, 'password' => (string) config('bm.uji_db_password')]);
    }

    private static function sqlite(Berkas $b): bool
    {
        return (bool) preg_match('/\.sqlite3?$/i', $b->nama);
    }

    /** Nama koneksi Laravel ke database jelajah aplikasi ini (berdasarkan uji pulih terakhir yang berhasil). */
    public static function koneksi(Aplikasi $a): ?string
    {
        $uji = UjiPulih::where('aplikasi', $a->kode)->whereIn('status', ['ok', 'peringatan'])->orderByDesc('id')->first();
        if (! $uji) {
            return null;
        }
        $nama = 'jelajah_'.$a->kode;
        $sqlite = Katalog::dir('.lokal/jelajah/'.$a->kode.'.sqlite3');
        if (is_file($sqlite) && ($uji->rincian['_sqlite'] ?? false)) {
            config(["database.connections.$nama" => ['driver' => 'sqlite', 'database' => $sqlite, 'prefix' => '', 'foreign_key_constraints' => false]]);
        } else {
            try {
                config(["database.connections.$nama" => self::mysqlUji(self::namaDb($a->kode))]);
            } catch (\RuntimeException) {
                return null;
            }
        }
        DB::purge($nama);

        return $nama;
    }

    /** Nama tabel di database koneksi ini saja (MySQL: getTables() tanpa skema mengembalikan semua database). @return string[] */
    public static function daftarTabel(string $koneksi): array
    {
        $c = DB::connection($koneksi);
        $skema = $c->getDriverName() === 'sqlite' ? null : $c->getDatabaseName();

        return collect(Schema::connection($koneksi)->getTables($skema))
            ->filter(fn ($t) => $skema === null || ($t['schema'] ?? $skema) === $skema)
            ->pluck('name')->reject(fn ($n) => str_starts_with($n, 'sqlite_'))->values()->all();
    }

    public static function ujiTerbaru(string $kode): ?UjiPulih
    {
        $b = Berkas::where('aplikasi', $kode)->where('jenis', 'db')->where('utuh', true)->orderByDesc('waktu')->first();

        return $b ? self::uji($b) : null;
    }

    public static function uji(Berkas $b): UjiPulih
    {
        $a = Aplikasi::pastikan($b->aplikasi);
        try {
            $nama = self::sqlite($b) ? self::muatSqlite($b) : self::muatMysql($b);
            $koneksi = self::koneksiSementara($b, $nama);
            $rincian = [];
            foreach (self::daftarTabel($koneksi) as $t) {
                $rincian[$t] = (int) DB::connection($koneksi)->table($t)->count();
            }
            DB::purge($koneksi);
            if (! $rincian) {
                throw new \RuntimeException('Database hasil pemulihan kosong (tidak ada tabel).');
            }
            [$status, $pesan] = self::bandingkan($a->kode, $rincian);
            $rincian['_sqlite'] = self::sqlite($b);
        } catch (\Throwable $e) {
            [$status, $pesan, $rincian] = ['gagal', Jalankan::ringkas($e->getMessage(), 800), []];
        }
        $u = UjiPulih::create(['aplikasi' => $a->kode, 'berkas_id' => $b->id, 'waktu' => now('UTC'), 'status' => $status,
            'tabel' => count(array_filter(array_keys($rincian), fn ($k) => $k[0] !== '_')),
            'baris' => array_sum(array_filter($rincian, 'is_int')), 'rincian' => $rincian, 'pesan' => $pesan]);
        if ($status !== 'ok') {
            Notifikasi::kirim(($status === 'gagal' ? '❌' : '⚠️')." Uji pulih {$a->nama} ({$b->nama}): $pesan");
        }
        Audit::catat('Uji pulih', auth()->user()?->username ?? 'sistem', "{$a->nama} {$b->nama}: $status");

        return $u;
    }

    /** @return array{0:string,1:?string} */
    private static function bandingkan(string $kode, array $rincian): array
    {
        $lalu = UjiPulih::where('aplikasi', $kode)->whereIn('status', ['ok', 'peringatan'])->orderByDesc('id')->first()?->rincian ?? [];
        $batas = (int) config('bm.batas_turun_persen', 20);
        $catatan = [];
        foreach ($lalu as $t => $n) {
            if ($t[0] === '_' || ! is_int($n)) {
                continue;
            }
            if (! array_key_exists($t, $rincian)) {
                $catatan[] = "tabel $t hilang";
            } elseif ($n >= 20 && $rincian[$t] < $n * (100 - $batas) / 100) {
                $catatan[] = "$t turun $n → {$rincian[$t]}";
            }
        }

        return $catatan ? ['peringatan', 'Dibanding uji sebelumnya: '.implode('; ', array_slice($catatan, 0, 10)).'.'] : ['ok', null];
    }

    private static function koneksiSementara(Berkas $b, string $db): string
    {
        $nama = 'ujipulih_'.$b->aplikasi;
        config(["database.connections.$nama" => self::sqlite($b)
            ? ['driver' => 'sqlite', 'database' => $db, 'prefix' => '', 'foreign_key_constraints' => false]
            : self::mysqlUji($db)]);
        DB::purge($nama);

        return $nama;
    }

    private static function muatSqlite(Berkas $b): string
    {
        $tujuan = Katalog::dir('.lokal/jelajah/'.$b->aplikasi.'.sqlite3');
        @mkdir(dirname($tujuan), 0750, true);
        if (! copy($b->full(), $tujuan.'.tmp') || ! rename($tujuan.'.tmp', $tujuan)) {
            throw new \RuntimeException('Gagal menyalin berkas SQLite.');
        }

        return $tujuan;
    }

    /** gunzip -c dump | mysql bmj_<kode> (database dibuat ulang setiap uji). */
    private static function muatMysql(Berkas $b): string
    {
        $db = self::namaDb($b->aplikasi);
        $c = self::mysqlUji($db);
        if (! preg_match('/^[A-Za-z0-9_]+$/', $db)) {
            throw new \RuntimeException('Nama database jelajah tidak valid.');
        }
        $mysql = array_map('escapeshellarg', [config('bm.mysql_bin', 'mysql'), '-h', (string) $c['host'], '-P', (string) $c['port'], '-u', (string) $c['username']]);
        $cli = implode(' ', $mysql);
        $env = ['MYSQL_PWD' => (string) $c['password']];
        [$k, , $err] = Jalankan::shell($cli.' -e '.escapeshellarg("DROP DATABASE IF EXISTS `$db`; CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"), 120, $env);
        if ($k !== 0) {
            throw new \RuntimeException('Tidak bisa membuat database uji '.$db.' (user DB butuh hak penuh pada '.config('bm.jelajah_prefix').'%): '.Jalankan::ringkas($err, 300));
        }
        $baca = str_ends_with(strtolower($b->nama), '.gz') ? 'gunzip -c '.escapeshellarg($b->full()) : 'cat '.escapeshellarg($b->full());
        [$k, , $err] = Jalankan::shell($baca.' | '.$cli.' --default-character-set=utf8mb4 '.escapeshellarg($db), 7200, $env);
        if ($k !== 0) {
            throw new \RuntimeException('Isi backup tidak bisa diimpor (rusak, terpotong, atau berisi perintah di luar database uji): '.Jalankan::ringkas($err, 400));
        }

        return $db;
    }
}
