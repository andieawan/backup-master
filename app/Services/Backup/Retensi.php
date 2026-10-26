<?php

namespace App\Services\Backup;

use App\Models\Berkas;
use App\Services\Audit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;

/**
 * Retensi bertingkat per aplikasi (dihitung per HARI yang punya backup):
 *   - N hari terbaru disimpan semua,
 *   - tiap minggu (M minggu terbaru) disimpan hari terakhirnya,
 *   - tiap bulan (K bulan terbaru) disimpan hari terakhirnya.
 * Hari terbaru selalu disimpan. Yang dihapus dicatat agar tidak ditarik ulang dari sumber.
 */
class Retensi
{
    /** @param string[] $hari daftar YYYY-MM-DD  @return string[] hari yang disimpan */
    public static function pilih(array $hari, int $harian, int $mingguan, int $bulanan): array
    {
        $hari = array_values(array_unique($hari));
        rsort($hari);
        if (! $hari) {
            return [];
        }
        $simpan = [$hari[0] => true];
        foreach (array_slice($hari, 0, max(1, $harian)) as $h) {
            $simpan[$h] = true;
        }
        $minggu = $bulan = [];
        foreach ($hari as $h) { // menurun: yang pertama dijumpai = hari terakhir periode itu
            $w = CarbonImmutable::parse($h, 'UTC')->format('o-W');
            if (! isset($minggu[$w]) && count($minggu) < $mingguan) {
                $minggu[$w] = $h;
            }
            $b = substr($h, 0, 7);
            if (! isset($bulan[$b]) && count($bulan) < $bulanan) {
                $bulan[$b] = $h;
            }
        }
        foreach ([...array_values($minggu), ...array_values($bulan)] as $h) {
            $simpan[$h] = true;
        }
        $hasil = array_keys($simpan);
        rsort($hasil);

        return $hasil;
    }

    /** @return int jumlah berkas/potret yang dihapus */
    public static function jalankan(): int
    {
        $n = 0;
        $dihapus = [];
        foreach (Berkas::query()->distinct()->pluck('aplikasi') as $kode) {
            $semua = Berkas::where('aplikasi', $kode)->get();
            $simpan = array_flip(self::pilih($semua->pluck('hari')->all(), (int) config('bm.simpan_harian'), (int) config('bm.simpan_mingguan'), (int) config('bm.simpan_bulanan')));
            foreach ($semua as $b) {
                if (isset($simpan[$b->hari])) {
                    continue;
                }
                $full = $b->full();
                $b->jenis === 'cermin' ? File::deleteDirectory($full) : @unlink($full);
                if (! file_exists($full)) {
                    $dihapus[] = $b->path;
                    $b->delete();
                    $n++;
                }
            }
        }
        Katalog::tandaiTerhapus($dihapus);
        if ($n) {
            Audit::catat('Retensi', 'sistem', "$n berkas/potret lama dihapus");
        }

        return $n;
    }
}
