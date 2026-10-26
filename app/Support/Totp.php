<?php

namespace App\Support;

/** TOTP (RFC 6238) untuk aplikasi authenticator: Google Authenticator, Microsoft Authenticator, Aegis, dll. */
final class Totp
{
    private const ABJAD = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function rahasiaBaru(): string
    {
        $b = random_bytes(20);
        $bit = '';
        foreach (str_split($b) as $c) {
            $bit .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $s = '';
        foreach (str_split($bit, 5) as $p) {
            $s .= self::ABJAD[bindec(str_pad($p, 5, '0'))];
        }

        return $s;
    }

    private static function dekode(string $s): string
    {
        $s = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $s));
        $bit = '';
        foreach (str_split($s) as $c) {
            $bit .= str_pad(decbin(strpos(self::ABJAD, $c)), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bit, 8) as $p) {
            if (strlen($p) === 8) {
                $out .= chr(bindec($p));
            }
        }

        return $out;
    }

    public static function kode(string $rahasia, ?int $waktu = null): string
    {
        $t = intdiv($waktu ?? time(), 30);
        $h = hash_hmac('sha1', pack('N2', 0, $t), self::dekode($rahasia), true);
        $o = ord($h[19]) & 0xF;
        $n = ((ord($h[$o]) & 0x7F) << 24) | (ord($h[$o + 1]) << 16) | (ord($h[$o + 2]) << 8) | ord($h[$o + 3]);

        return str_pad((string) ($n % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /** Terima kode periode sekarang ±1 (selisih jam HP ±30 detik). */
    public static function cocok(string $rahasia, string $kode, ?int $waktu = null): bool
    {
        $kode = preg_replace('/\D/', '', $kode);
        if (strlen($kode) !== 6) {
            return false;
        }
        $w = $waktu ?? time();
        foreach ([-30, 0, 30] as $d) {
            if (hash_equals(self::kode($rahasia, $w + $d), $kode)) {
                return true;
            }
        }

        return false;
    }

    public static function uri(string $rahasia, string $akun, string $penerbit): string
    {
        return 'otpauth://totp/'.rawurlencode($penerbit.':'.$akun).'?secret='.$rahasia.'&issuer='.rawurlencode($penerbit).'&digits=6&period=30';
    }
}
