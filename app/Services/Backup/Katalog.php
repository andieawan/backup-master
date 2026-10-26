<?php

namespace App\Services\Backup;

use App\Models\Aplikasi;
use App\Models\Berkas;
use App\Support\Dates;
use App\Support\Jalankan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Isi BM_DATA_DIR = sumber kebenaran. Susunan:
 *   <kode>/arsip/<berkas backup bertanggal>      (db_*.sql.gz, berkas_*.tar.gz, absensi_*.sql, ...)
 *   <kode>/cermin/<YYYY-MM-DD>/...               (potret harian folder, berbagi hardlink antar hari)
 *   <kode>/info.json                             (nama aplikasi & tabel rahasia; ikut tersalin ke Backup Master lain)
 *   .lokal/                                      (milik tempat ini saja: daftar terhapus, manifest sumber, jelajah sqlite)
 *   MANIFEST.json                                (daftar sha256 untuk verifikasi oleh salinan)
 */
class Katalog
{
    public const DB = ['sql', 'gz:sql', 'sqlite3', 'sqlite'];

    public static function dir(string $rel = ''): string
    {
        $base = rtrim((string) config('bm.data_dir'), '/');

        return $rel === '' ? $base : $base.'/'.ltrim($rel, '/');
    }

    public static function siapkan(): void
    {
        foreach ([self::dir(), self::dir('.lokal')] as $d) {
            if (! is_dir($d) && ! @mkdir($d, 0750, true) && ! is_dir($d)) {
                throw new \RuntimeException("Folder data tidak bisa dibuat: $d (periksa BM_DATA_DIR dan izin folder).");
            }
        }
    }

    public static function kodeValid(string $kode): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9_-]{0,39}$/', $kode);
    }

    /** Jenis berkas dari namanya: db | arsip | null (diabaikan). */
    public static function jenis(string $nama): ?string
    {
        $n = strtolower($nama);

        return match (true) {
            str_ends_with($n, '.sql'), str_ends_with($n, '.sql.gz'), str_ends_with($n, '.sqlite3'), str_ends_with($n, '.sqlite') => 'db',
            str_ends_with($n, '.tar.gz'), str_ends_with($n, '.tgz'), str_ends_with($n, '.zip'), str_ends_with($n, '.tar') => 'arsip',
            default => null,
        };
    }

    /** Waktu backup (UTC) dari nama berkas: ..._2026-10-26_0130, ..._2026-10-26T02-00-00; bila tak ada, mtime. */
    public static function waktu(string $nama, ?int $mtime = null): CarbonImmutable
    {
        $tz = Dates::tz();
        if (preg_match('/(\d{4})-(\d{2})-(\d{2})(?:[T_ ](\d{2})[-:]?(\d{2})(?:[-:]?(\d{2}))?)?/', $nama, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            $jam = sprintf('%02d:%02d:%02d', min(23, (int) ($m[4] ?? 0)), min(59, (int) ($m[5] ?? 0)), min(59, (int) ($m[6] ?? 0)));

            return CarbonImmutable::parse("$m[1]-$m[2]-$m[3] $jam", $tz)->utc();
        }

        return CarbonImmutable::createFromTimestamp($mtime ?? time(), 'UTC');
    }

    /** Cek keutuhan berkas. @return array{0:bool,1:?string} */
    public static function cekUtuh(string $full): array
    {
        $n = strtolower($full);
        if (! is_file($full) || filesize($full) === 0) {
            return [false, 'Berkas kosong.'];
        }
        if (str_ends_with($n, '.gz') || str_ends_with($n, '.tgz')) {
            [$k, , $err] = Jalankan::perintah(['gzip', '-t', $full], 1800);

            return $k === 0 ? [true, null] : [false, 'Gzip rusak/terpotong: '.Jalankan::ringkas($err, 200)];
        }
        if (str_ends_with($n, '.sqlite3') || str_ends_with($n, '.sqlite')) {
            return file_get_contents($full, false, null, 0, 15) === 'SQLite format 3' ? [true, null] : [false, 'Bukan berkas SQLite.'];
        }
        if (str_ends_with($n, '.zip')) {
            $z = new \ZipArchive;

            return $z->open($full, \ZipArchive::RDONLY) === true ? [true, null] : [false, 'Zip rusak.'];
        }
        if (str_ends_with($n, '.sql')) {
            $awal = (string) file_get_contents($full, false, null, 0, 65536);
            $fh = fopen($full, 'rb');
            fseek($fh, max(0, filesize($full) - 4096));
            $akhir = (string) fread($fh, 4096);
            fclose($fh);
            if (! preg_match('/CREATE TABLE|INSERT INTO/i', $awal.$akhir)) {
                return [false, 'Isi tidak tampak seperti dump SQL.'];
            }

            return [true, str_contains($akhir, 'Dump completed') || preg_match('/;\s*$/', $akhir) ? null : 'Akhir dump tidak lazim (mungkin terpotong).'];
        }

        return [true, null];
    }

    private static function ukuranFolder(string $dir): int
    {
        $n = 0;
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && ! $f->isLink()) {
                $n += $f->getSize();
            }
        }

        return $n;
    }

    /**
     * Selaraskan tabel "berkas" dengan isi folder data: tambah yang baru (hitung sha256 + cek utuh), hapus yang hilang.
     *
     * @return Collection<int, Berkas> berkas baru
     */
    public static function pindai(): Collection
    {
        self::siapkan();
        $ada = [];
        $baru = collect();
        foreach (glob(self::dir('*'), GLOB_ONLYDIR) ?: [] as $dirApl) {
            $kode = basename($dirApl);
            if (! self::kodeValid($kode)) {
                continue;
            }
            $info = is_file("$dirApl/info.json") ? (array) json_decode((string) file_get_contents("$dirApl/info.json"), true) : [];
            Aplikasi::pastikan($kode, array_intersect_key($info, array_flip(['nama', 'rahasia', 'app_dir', 'db'])));

            foreach (glob("$dirApl/arsip/*") ?: [] as $f) {
                if (! is_file($f) || ! ($jenis = self::jenis(basename($f)))) {
                    continue;
                }
                $rel = "$kode/arsip/".basename($f);
                $ada[$rel] = true;
                if ($b = self::catat($kode, $jenis, $rel, $f, filesize($f), filemtime($f))) {
                    $baru->push($b);
                }
            }
            foreach (glob("$dirApl/cermin/*", GLOB_ONLYDIR) ?: [] as $d) {
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', basename($d))) {
                    continue; // .tmp (sedang ditulis) dan lainnya
                }
                $rel = "$kode/cermin/".basename($d);
                $ada[$rel] = true;
                if ($b = self::catat($kode, 'cermin', $rel, $d, null, filemtime($d))) {
                    $baru->push($b);
                }
            }
        }
        Berkas::query()->pluck('path', 'id')->each(function ($path, $id) use ($ada) {
            if (! isset($ada[$path])) {
                Berkas::whereKey($id)->delete();
            }
        });

        return $baru;
    }

    private static function catat(string $kode, string $jenis, string $rel, string $full, ?int $ukuran, int $mtime): ?Berkas
    {
        $b = Berkas::where('path', $rel)->first();
        if ($b && ($jenis === 'cermin' || $b->ukuran === $ukuran)) {
            return null;
        }
        $nama = basename($rel);
        $w = $jenis === 'cermin' ? CarbonImmutable::parse($nama.' 23:59:00', Dates::tz())->utc() : self::waktu($nama, $mtime);
        [$utuh, $pesan] = $jenis === 'cermin' ? [true, null] : self::cekUtuh($full);
        $data = [
            'aplikasi' => $kode, 'jenis' => $jenis, 'nama' => $nama, 'ukuran' => $ukuran ?? self::ukuranFolder($full),
            'sha256' => $jenis === 'cermin' ? null : hash_file('sha256', $full), 'waktu' => $w->format('Y-m-d H:i:s'),
            'hari' => $w->setTimezone(Dates::tz())->format('Y-m-d'), 'utuh' => $utuh, 'pesan' => $pesan,
        ];
        if ($b) {
            $b->update($data); // ukuran berubah (mis. dulu tertarik setengah): hitung ulang

            return $b;
        }

        return Berkas::create($data + ['path' => $rel]);
    }

    /** Tulis info.json tiap aplikasi + MANIFEST.json (dibaca Backup Master lain yang menyalin dari sini). */
    public static function tulisManifest(): void
    {
        foreach (Aplikasi::all() as $a) {
            if (is_dir(self::dir($a->kode))) {
                @file_put_contents(self::dir($a->kode.'/info.json'), json_encode(
                    ['nama' => $a->nama, 'rahasia' => $a->rahasia ?? [], 'app_dir' => $a->app_dir, 'db' => $a->db], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }
        }
        $isi = ['lokasi' => config('bm.lokasi'), 'dibuat' => now('UTC')->toIso8601String(),
            'berkas' => Berkas::whereNotNull('sha256')->orderBy('path')->get(['path', 'sha256', 'ukuran'])->map(fn ($b) => $b->only(['path', 'sha256', 'ukuran']))->all()];
        $tmp = self::dir('MANIFEST.json.tmp');
        file_put_contents($tmp, json_encode($isi, JSON_UNESCAPED_SLASHES));
        rename($tmp, self::dir('MANIFEST.json'));
    }

    // ---- Daftar "sudah dihapus retensi" agar tidak ditarik ulang dari sumber ----
    public static function terhapus(): array
    {
        $f = self::dir('.lokal/terhapus.txt');

        return is_file($f) ? array_values(array_filter(array_map('trim', file($f)))) : [];
    }

    public static function tandaiTerhapus(array $paths): void
    {
        if (! $paths) {
            return;
        }
        $semua = array_values(array_unique([...self::terhapus(), ...$paths]));
        file_put_contents(self::dir('.lokal/terhapus.txt'), implode("\n", array_slice($semua, -20000))."\n");
    }
}
