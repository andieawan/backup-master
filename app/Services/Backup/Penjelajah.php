<?php

namespace App\Services\Backup;

use App\Exceptions\UserError;
use App\Models\Aplikasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Membaca isi database hasil uji pulih (baca saja). Kolom rahasia (password, token, …) tidak pernah ditampilkan. */
class Penjelajah
{
    public function __construct(public readonly Aplikasi $a, public readonly string $koneksi) {}

    public static function untuk(Aplikasi $a): ?self
    {
        $k = Pemulih::koneksi($a);

        return $k ? new self($a, $k) : null;
    }

    /** @return array<string,int> tabel => jumlah baris (dari uji pulih terakhir) */
    public function tabel(): array
    {
        $abaikan = array_flip((array) config('bm.tabel_abaikan'));
        $hasil = [];
        foreach (Pemulih::daftarTabel($this->koneksi) as $t) {
            if (! isset($abaikan[$t])) {
                $hasil[$t] = 0;
            }
        }
        ksort($hasil);
        $uji = $this->a->ujiTerakhir?->rincian ?? [];
        foreach ($hasil as $t => $_) {
            $hasil[$t] = (int) ($uji[$t] ?? 0);
        }

        return $hasil;
    }

    public function pastikanTabel(string $t): void
    {
        if (! array_key_exists($t, $this->tabel())) {
            throw new UserError('Tabel tidak ditemukan.');
        }
    }

    public static function kolomRahasia(string $kolom): bool
    {
        return (bool) preg_match((string) config('bm.kolom_rahasia'), $kolom);
    }

    /** @return string[] */
    public function kolom(string $t): array
    {
        $this->pastikanTabel($t);

        return array_values(array_filter(Schema::connection($this->koneksi)->getColumnListing($t), fn ($c) => ! self::kolomRahasia($c)));
    }

    /** @return array{kolom:string[], baris:\Illuminate\Support\Collection, total:int, halaman:int, per:int} */
    public function baris(string $t, string $cari = '', int $halaman = 1, int $per = 50, ?string $urut = null, bool $turun = true): array
    {
        $kolom = $this->kolom($t);
        $semua = Schema::connection($this->koneksi)->getColumnListing($t);
        $q = DB::connection($this->koneksi)->table($t);
        $cari = trim($cari);
        if ($cari !== '') {
            $pola = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $cari).'%';
            $q->where(function ($w) use ($kolom, $pola) {
                foreach ($kolom as $c) {
                    $w->orWhere($c, 'like', $pola);
                }
            });
        }
        $total = (clone $q)->count();
        $urut = in_array($urut, $kolom, true) ? $urut : (in_array('id', $semua, true) ? 'id' : null);
        if ($urut) {
            $q->orderBy($urut, $turun ? 'desc' : 'asc');
        }
        $halaman = max(1, $halaman);
        $baris = $q->offset(($halaman - 1) * $per)->limit($per)->get($kolom);

        return ['kolom' => $kolom, 'baris' => $baris, 'total' => $total, 'halaman' => $halaman, 'per' => $per, 'urut' => $urut, 'turun' => $turun];
    }

    public function satu(string $t, string $id): ?array
    {
        $kolom = $this->kolom($t);
        if (! in_array('id', Schema::connection($this->koneksi)->getColumnListing($t), true)) {
            return null;
        }
        $r = DB::connection($this->koneksi)->table($t)->where('id', $id)->first($kolom);

        return $r ? (array) $r : null;
    }
}
