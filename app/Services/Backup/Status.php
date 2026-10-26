<?php

namespace App\Services\Backup;

use App\Models\Aplikasi;
use App\Models\Berkas;
use App\Models\Sumber;
use App\Models\UjiPulih;
use App\Support\Dates;

/** Ringkasan kesehatan per aplikasi untuk dasbor & notifikasi harian. */
class Status
{
    /** @return array<int, array{a:Aplikasi, db:?Berkas, arsip:?Berkas, cermin:?Berkas, uji:?UjiPulih, umur:?float, masalah:string[], jumlah:int, ukuran:int}> */
    public static function semua(): array
    {
        $batas = (int) config('bm.batas_umur_jam', 30);
        $hasil = [];
        foreach (Aplikasi::orderBy('nama')->get() as $a) {
            $q = fn ($j) => Berkas::where('aplikasi', $a->kode)->where('jenis', $j)->orderByDesc('waktu')->first();
            $db = $q('db');
            $uji = UjiPulih::where('aplikasi', $a->kode)->orderByDesc('id')->first();
            $umur = $db ? round((time() - $db->waktu->getTimestamp()) / 3600, 1) : null;
            $masalah = [];
            if (! $db) {
                $masalah[] = 'Belum ada backup database.';
            } elseif ($umur > $batas) {
                $masalah[] = 'Backup database terbaru berumur '.Dates::lalu($db->waktu).'.';
            }
            if ($db && $db->utuh === false) {
                $masalah[] = 'Backup database terbaru tidak utuh: '.$db->pesan;
            }
            if ($uji && $uji->status === 'gagal') {
                $masalah[] = 'Uji pulih terakhir gagal: '.$uji->pesan;
            } elseif ($uji && $uji->status === 'peringatan') {
                $masalah[] = 'Uji pulih: '.$uji->pesan;
            }
            $hasil[] = ['a' => $a, 'db' => $db, 'arsip' => $q('arsip'), 'cermin' => $q('cermin'), 'uji' => $uji, 'umur' => $umur, 'masalah' => $masalah,
                'jumlah' => Berkas::where('aplikasi', $a->kode)->count(), 'ukuran' => (int) Berkas::where('aplikasi', $a->kode)->sum('ukuran')];
        }

        return $hasil;
    }

    public static function disk(): array
    {
        $d = Katalog::dir();
        $total = @disk_total_space($d) ?: 0;
        $bebas = @disk_free_space($d) ?: 0;

        return ['total' => $total, 'bebas' => $bebas, 'persen' => $total ? round(($total - $bebas) / $total * 100) : null];
    }

    public static function ringkasan(): string
    {
        $baris = [];
        foreach (self::semua() as $r) {
            $baris[] = ($r['masalah'] ? '⚠️ ' : '✅ ').$r['a']->nama.': '.($r['db'] ? 'DB '.Dates::lalu($r['db']->waktu) : 'belum ada').($r['masalah'] ? ' — '.implode(' ', $r['masalah']) : '');
        }
        foreach (Sumber::where('aktif', true)->where('terakhir_status', 'gagal')->get() as $s) {
            $baris[] = "❌ Sumber {$s->nama}: {$s->terakhir_pesan}";
        }
        $disk = self::disk();
        if ($disk['persen'] !== null) {
            $baris[] = ($disk['persen'] >= 90 ? '⚠️ ' : '💾 ').'Disk terpakai '.$disk['persen'].'% (sisa '.Dates::ukuran($disk['bebas']).')';
        }

        return "Ringkasan backup:\n".implode("\n", $baris ?: ['(belum ada aplikasi)']);
    }
}
