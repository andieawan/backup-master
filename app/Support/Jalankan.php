<?php

namespace App\Support;

use Symfony\Component\Process\Process;

/** Menjalankan program luar (rsync, ssh, tar, gzip, mysql) tanpa shell, dengan batas waktu. */
final class Jalankan
{
    /** @return array{0:int,1:string,2:string} [kode keluar, stdout, stderr] */
    public static function perintah(array $cmd, ?int $timeout = 600, array $env = [], ?string $cwd = null): array
    {
        $p = new Process($cmd, $cwd, $env ?: null, null, $timeout);
        $p->run();

        return [(int) $p->getExitCode(), $p->getOutput(), $p->getErrorOutput()];
    }

    /** Untuk pipa (gunzip | mysql). Semua bagian WAJIB sudah di-escapeshellarg oleh pemanggil. */
    public static function shell(string $baris, ?int $timeout = 3600, array $env = []): array
    {
        $p = new Process(['bash', '-c', 'set -o pipefail; '.$baris], null, $env ?: null, null, $timeout);
        $p->run();

        return [(int) $p->getExitCode(), $p->getOutput(), $p->getErrorOutput()];
    }

    public static function ada(string $program): bool
    {
        return (new Process(['sh', '-c', 'command -v '.escapeshellarg($program)]))->run() === 0;
    }

    /** Potong pesan error agar muat dan tidak membocorkan isi panjang. */
    public static function ringkas(string $s, int $max = 600): string
    {
        $s = trim(preg_replace('/\s+/', ' ', $s));

        return mb_strlen($s) > $max ? mb_substr($s, 0, $max).'…' : $s;
    }
}
