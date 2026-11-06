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
            // Aplikasi tanpa database (mis. WOL Panel): kesegaran dinilai dari arsip terbaru.
            $tanpaDb = (bool) config('bm.templat.'.$a->kode.'.tanpa_db', false);
            $apa = $tanpaDb ? 'Backup' : 'Backup database';
            $db = $q($tanpaDb ? 'arsip' : 'db');
            $uji = UjiPulih::where('aplikasi', $a->kode)->orderByDesc('id')->first();
            $umur = $db ? round((time() - $db->waktu->getTimestamp()) / 3600, 1) : null;
            $masalah = [];
            if (! $db) {
                $masalah[] = 'Belum ada '.lcfirst($apa).'.';
            } elseif ($umur > $batas) {
                $masalah[] = $apa.' terbaru dibuat '.Dates::lalu($db->waktu).'.';
            }
            if ($db && $db->utuh === false) {
                $masalah[] = $apa.' terbaru tidak utuh: '.$db->pesan;
            }
            if ($uji && $uji->status === 'gagal') {
                $masalah[] = 'Uji pulih terakhir gagal: '.$uji->pesan;
            } elseif ($uji && $uji->status === 'peringatan') {
                $masalah[] = 'Uji pulih: '.$uji->pesan;
            }
            $hasil[] = ['a' => $a, 'db' => $tanpaDb ? null : $db, 'tanpa_db' => $tanpaDb, 'arsip' => $q('arsip'), 'cermin' => $q('cermin'), 'uji' => $uji, 'umur' => $umur, 'masalah' => $masalah,
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
            $baris[] = ($r['masalah'] ? '⚠️ ' : '✅ ').$r['a']->nama.': '.(($b = $r['tanpa_db'] ? $r['arsip'] : $r['db']) ? ($r['tanpa_db'] ? 'Arsip ' : 'DB ').Dates::lalu($b->waktu) : 'belum ada').($r['masalah'] ? ' — '.implode(' ', $r['masalah']) : '');
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
