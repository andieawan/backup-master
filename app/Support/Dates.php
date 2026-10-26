<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/** Tanggal sesi selalu YYYY-MM-DD; "hari ini" menurut zona sekolah (WIB), data disimpan UTC. */
class Dates
{
    public const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    public const MONTHS = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    public static function tz(): string
    {
        return config('bm.timezone', 'Asia/Jakarta');
    }

    public static function today(): string
    {
        return CarbonImmutable::now(self::tz())->format('Y-m-d');
    }

    /** Epoch detik (UTC 00:00) untuk tanggal valid, atau null. */
    public static function parse(mixed $ymd): ?int
    {
        if (! is_string($ymd) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $ymd, $m) || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }

        return gmmktime(0, 0, 0, (int) $m[2], (int) $m[3], (int) $m[1]);
    }

    public static function valid(mixed $ymd): bool
    {
        return self::parse($ymd) !== null;
    }

    /** Tanggal setelah hari ini (WIB)? String YYYY-MM-DD bisa dibandingkan langsung. */
    public static function isFuture(string $ymd): bool
    {
        return $ymd > self::today();
    }

    /** Selisih hari (dibulatkan ke bawah) antara sekarang dan tanggal. Negatif = masa depan. */
    public static function daysSince(string $ymd): int
    {
        return (int) floor((time() - (int) self::parse($ymd)) / 86400);
    }

    public static function weekday(string $ymd): int
    {
        return (int) gmdate('w', (int) self::parse($ymd));
    }

    public static function human(string $ymd): string
    {
        $ts = self::parse($ymd);
        if ($ts === null) {
            return $ymd;
        }
        $short = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return self::DAYS[(int) gmdate('w', $ts)].', '.(int) gmdate('j', $ts).' '.$short[(int) gmdate('n', $ts)].' '.gmdate('Y', $ts);
    }

    public static function long(?string $ymd = null): string
    {
        $d = $ymd && self::parse($ymd) !== null ? CarbonImmutable::parse($ymd, 'UTC') : CarbonImmutable::now(self::tz());

        return $d->day.' '.self::MONTHS[$d->month].' '.$d->year;
    }

    public static function nowMillis(): int
    {
        return (int) floor(microtime(true) * 1000);
    }

    public static function isoUtc(?int $millis = null): string
    {
        $millis ??= self::nowMillis();

        return gmdate('Y-m-d\TH:i:s', intdiv($millis, 1000)).sprintf('.%03dZ', $millis % 1000);
    }

    public static function local(int $millis): string
    {
        return CarbonImmutable::createFromTimestampMs($millis)->setTimezone(self::tz())->format('j/n/Y H.i');
    }

    /** Waktu UTC (string/DateTime) → "26 Okt 2026 02:30" zona sekolah. */
    public static function waktu(mixed $utc): string
    {
        if (! $utc) {
            return '-';
        }
        $d = CarbonImmutable::parse($utc, 'UTC')->setTimezone(self::tz());
        $short = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return $d->day.' '.$short[$d->month].' '.$d->year.' '.$d->format('H:i');
    }

    /** "3 jam lalu", "2 hari lalu". */
    public static function lalu(mixed $utc): string
    {
        if (! $utc) {
            return 'belum pernah';
        }
        $s = max(0, time() - CarbonImmutable::parse($utc, 'UTC')->getTimestamp());

        return match (true) {
            $s < 90 => 'baru saja',
            $s < 5400 => round($s / 60).' menit lalu',
            $s < 172800 => round($s / 3600).' jam lalu',
            default => round($s / 86400).' hari lalu',
        };
    }

    public static function ukuran(int|float|null $bait): string
    {
        $b = (float) $bait;
        foreach (['B', 'KB', 'MB', 'GB', 'TB'] as $i => $u) {
            if ($b < 1024 || $u === 'TB') {
                return ($i ? number_format($b, $b < 10 ? 1 : 0, ',', '.') : (int) $b).' '.$u;
            }
            $b /= 1024;
        }

        return '';
    }
}
