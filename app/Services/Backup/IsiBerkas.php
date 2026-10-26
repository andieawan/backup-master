<?php

namespace App\Services\Backup;

use App\Exceptions\UserError;
use App\Models\Berkas;
use App\Support\Jalankan;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Process;

/** Melihat isi arsip (tar.gz/zip) dan potret folder tanpa membongkar semuanya. */
class IsiBerkas
{
    public const MIME = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif',
        'pdf' => 'application/pdf', 'txt' => 'text/plain; charset=utf-8', 'csv' => 'text/csv; charset=utf-8', 'json' => 'application/json'];

    public static function gambar(string $nama): bool
    {
        return (bool) preg_match('/\.(jpe?g|png|webp|gif)$/i', $nama);
    }

    public static function mime(string $nama): string
    {
        return self::MIME[strtolower(pathinfo($nama, PATHINFO_EXTENSION))] ?? 'application/octet-stream';
    }

    /** Header aman: berkas dari backup tidak boleh menjalankan skrip di domain ini. */
    public static function header(string $nama, bool $unduh): array
    {
        $aman = str_replace(['"', "\r", "\n", '/', '\\'], '_', basename($nama));

        return ['Content-Type' => $unduh ? 'application/octet-stream' : self::mime($nama), 'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => 'sandbox', 'Cache-Control' => 'private, no-store',
            'Content-Disposition' => ($unduh ? 'attachment' : 'inline').'; filename="'.$aman.'"'];
    }

    /** @return array<int, array{nama:string, ukuran:?int}> */
    public static function daftarArsip(Berkas $b): array
    {
        return Cache::remember('isi:'.$b->id.':'.$b->sha256, 86400 * 30, function () use ($b) {
            $f = $b->full();
            if (str_ends_with(strtolower($f), '.zip')) {
                $z = new \ZipArchive;
                if ($z->open($f, \ZipArchive::RDONLY) !== true) {
                    throw new UserError('Zip tidak bisa dibuka.');
                }
                $hasil = [];
                for ($i = 0; $i < $z->numFiles; $i++) {
                    $s = $z->statIndex($i);
                    if (! str_ends_with($s['name'], '/')) {
                        $hasil[] = ['nama' => $s['name'], 'ukuran' => (int) $s['size']];
                    }
                }

                return $hasil;
            }
            $z = preg_match('/\.(gz|tgz)$/i', $f) ? 'z' : '';
            [$k, $out, $err] = Jalankan::perintah(['tar', "-t{$z}vf", $f, '--numeric-owner'], 600, ['LC_ALL' => 'C']);
            if ($k !== 0) {
                throw new UserError('Arsip tidak bisa dibaca: '.Jalankan::ringkas($err, 200));
            }
            $hasil = [];
            foreach (explode("\n", trim($out)) as $baris) {
                // -rw-r--r-- 33/33   12345 2026-10-26 01:45 ./bk-surat/a.pdf
                if (preg_match('/^-\S+\s+\S+\s+(\d+)\s+\S+\s+\S+\s+(.+)$/', $baris, $m)) {
                    $hasil[] = ['nama' => preg_replace('#^\./#', '', $m[2]), 'ukuran' => (int) $m[1]];
                }
            }

            return $hasil;
        });
    }

    public static function ambilDariArsip(Berkas $b, string $nama, bool $unduh): StreamedResponse
    {
        $ada = collect(self::daftarArsip($b))->firstWhere('nama', $nama) ?? throw new UserError('Berkas tidak ada di arsip.');
        $f = $b->full();
        $h = self::header($nama, $unduh) + ['Content-Length' => (string) $ada['ukuran']];
        if (str_ends_with(strtolower($f), '.zip')) {
            return new StreamedResponse(function () use ($f, $nama) {
                $z = new \ZipArchive;
                $z->open($f, \ZipArchive::RDONLY);
                $s = $z->getStream($nama);
                fpassthru($s);
                fclose($s);
            }, 200, $h);
        }
        $z = preg_match('/\.(gz|tgz)$/i', $f) ? 'z' : '';

        return new StreamedResponse(function () use ($f, $nama, $z) {
            // Anggota disimpan dengan awalan "./" oleh tar -C dir . — coba keduanya.
            foreach (['./'.$nama, $nama] as $anggota) {
                $p = new Process(['tar', "-x{$z}Of", $f, '--', $anggota], null, null, null, 600);
                $ada = false;
                $p->run(function ($tipe, $data) use (&$ada) {
                    if ($tipe === Process::OUT) {
                        $ada = true;
                        echo $data;
                        flush();
                    }
                });
                if ($p->getExitCode() === 0 && $ada) {
                    return;
                }
            }
        }, 200, $h);
    }

    /** Path aman di dalam potret folder. */
    public static function pathCermin(Berkas $b, string $sub): string
    {
        $akar = realpath($b->full());
        $p = realpath($b->full().'/'.ltrim($sub, '/'));
        if (! $akar || ! $p || ($p !== $akar && ! str_starts_with($p, $akar.'/'))) {
            throw new UserError('Lokasi tidak ditemukan.');
        }

        return $p;
    }

    /** @return array<int, array{nama:string, dir:bool, ukuran:int}> */
    public static function daftarCermin(Berkas $b, string $sub): array
    {
        $p = self::pathCermin($b, $sub);
        if (! is_dir($p)) {
            throw new UserError('Bukan folder.');
        }
        $hasil = [];
        foreach (scandir($p) ?: [] as $n) {
            if ($n === '.' || $n === '..' || is_link("$p/$n")) {
                continue;
            }
            $hasil[] = ['nama' => $n, 'dir' => is_dir("$p/$n"), 'ukuran' => is_file("$p/$n") ? filesize("$p/$n") : 0];
        }
        usort($hasil, fn ($x, $y) => [$y['dir'], $x['nama']] <=> [$x['dir'], $y['nama']]);

        return $hasil;
    }

    public static function ambilDariCermin(Berkas $b, string $sub, bool $unduh): BinaryFileResponse
    {
        $p = self::pathCermin($b, $sub);
        if (! is_file($p)) {
            throw new UserError('Bukan berkas.');
        }

        return new BinaryFileResponse($p, 200, self::header(basename($p), $unduh));
    }
}
