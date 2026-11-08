<?php

namespace App\Services\Backup;

use App\Models\Aplikasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master App: membaca salinan database SEMUA aplikasi sekaligus (hasil uji pulih terakhir, baca saja).
 * - cari(): satu kata dicari di tabel-tabel penting tiap aplikasi (config bm.cari)
 * - profil(): satu siswa/guru dari Pusat beserta datanya di aplikasi lain, dikaitkan lewat UUID Pusat (config bm.profil)
 * Kolom rahasia tidak pernah ikut; tabel rahasia hanya dihitung kecuali sudah dibuka dengan alasan.
 */
class Gabungan
{
    public const BATAS_CARI = 10;

    public const BATAS_BAGIAN = 50;

    /** @var array<string,?Penjelajah> */
    private array $j = [];

    /** @param callable(Aplikasi,string):bool $bolehRahasia */
    public function __construct(private $bolehRahasia) {}

    public function penjelajah(string $kode): ?Penjelajah
    {
        if (! array_key_exists($kode, $this->j)) {
            $a = Aplikasi::where('kode', $kode)->first();
            $this->j[$kode] = $a ? Penjelajah::untuk($a) : null;
        }

        return $this->j[$kode];
    }

    /** @return string[] kolom yang ada & tidak rahasia */
    private function kolomAda(Penjelajah $p, string $t, array $mau): array
    {
        if (! Schema::connection($p->koneksi)->hasTable($t)) {
            return [];
        }
        $ada = Schema::connection($p->koneksi)->getColumnListing($t);

        return array_values(array_filter($mau, fn ($c) => in_array($c, $ada, true) && ! Penjelajah::kolomRahasia($c)));
    }

    private function adaKolom(Penjelajah $p, string $t, string $c): bool
    {
        return Schema::connection($p->koneksi)->hasTable($t) && Schema::connection($p->koneksi)->hasColumn($t, $c);
    }

    private static function pola(string $q): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
    }

    /**
     * @return list<array{a:Aplikasi, tabel:string, rahasia:bool, total:int, kolom:string[], baris:list<array>, adaId:bool, profil:?string}>
     */
    public function cari(string $q): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return [];
        }
        $hasil = [];
        foreach ((array) config('bm.cari') as $kode => $tabel) {
            $p = $this->penjelajah($kode);
            if (! $p) {
                continue;
            }
            foreach ($tabel as $t => $kolomCari) {
                $kolom = $this->kolomAda($p, $t, $kolomCari);
                if (! $kolom) {
                    continue;
                }
                $query = DB::connection($p->koneksi)->table($t)->where(function ($w) use ($kolom, $q) {
                    foreach ($kolom as $c) {
                        $w->orWhere($c, 'like', self::pola($q));
                    }
                });
                $total = (clone $query)->count();
                if (! $total) {
                    continue;
                }
                $rahasia = $p->a->tabelRahasia($t) && ! ($this->bolehRahasia)($p->a, $t);
                $adaId = $this->adaKolom($p, $t, 'id');
                $profil = $this->kunciProfil($kode, $t);
                $ambil = array_values(array_unique(array_merge($adaId ? ['id'] : [], $kolom, $profil ? [$profil[1]] : [])));
                $baris = $rahasia ? [] : $query->limit(self::BATAS_CARI)->get($ambil)->map(fn ($r) => (array) $r)->all();
                $hasil[] = ['a' => $p->a, 'tabel' => $t, 'rahasia' => $rahasia, 'total' => $total, 'kolom' => $kolom,
                    'baris' => $baris, 'adaId' => $adaId, 'profil' => $profil ? $profil[0] : null, 'kunci' => $profil[1] ?? null];
            }
        }

        return $hasil;
    }

    /** Tabel ini = tabel orang (siswa/guru) di profil? → [jenis, kolom kunci UUID Pusat]. */
    private function kunciProfil(string $kode, string $tabel): ?array
    {
        foreach ((array) config('bm.profil') as $jenis => $apps) {
            if (($apps[$kode]['tabel'] ?? null) === $tabel) {
                $p = $this->penjelajah($kode);

                return $p && $this->adaKolom($p, $tabel, $apps[$kode]['kunci']) ? [$jenis, $apps[$kode]['kunci']] : null;
            }
        }

        return null;
    }

    /**
     * @return array{induk:?array, pusat:bool, aplikasi:list<array{a:Aplikasi, ada:bool, id:mixed, bagian:list<array>}>}
     */
    public function profil(string $jenis, string $uuid): array
    {
        $conf = (array) config("bm.profil.$jenis");
        $hasil = ['induk' => null, 'pusat' => false, 'aplikasi' => []];
        foreach ($conf as $kode => $c) {
            $p = $this->penjelajah($kode);
            if (! $p || ! $this->adaKolom($p, $c['tabel'], $c['kunci'])) {
                continue;
            }
            $orang = DB::connection($p->koneksi)->table($c['tabel'])->where($c['kunci'], $uuid)->first();
            if ($kode === 'pusat') {
                $hasil['pusat'] = true;
                if ($orang) {
                    $hasil['induk'] = ['a' => $p->a, 'data' => array_intersect_key((array) $orang, array_flip($this->kolomAda($p, $c['tabel'], $c['tampil'] ?? []))),
                        'id' => $orang->id ?? null, 'tabel' => $c['tabel']];
                }

                continue;
            }
            $baris = ['a' => $p->a, 'ada' => (bool) $orang, 'id' => $orang->id ?? null, 'tabel' => $c['tabel'], 'bagian' => []];
            if ($orang && isset($orang->id)) {
                foreach ($c['bagian'] ?? [] as $b) {
                    $bagian = $this->bagian($p, $b, $orang->id);
                    if ($bagian) {
                        $baris['bagian'][] = $bagian;
                    }
                }
            }
            $hasil['aplikasi'][] = $baris;
        }

        return $hasil;
    }

    private function bagian(Penjelajah $p, array $b, mixed $idOrang): ?array
    {
        $kolom = $this->kolomAda($p, $b['tabel'], $b['kolom']);
        if (! $kolom || ! $this->adaKolom($p, $b['tabel'], $b['fk'])) {
            return null;
        }
        $db = DB::connection($p->koneksi);
        if (isset($b['via'])) {
            [$vt, $vk] = $b['via'];
            if (! $this->adaKolom($p, $vt, $vk)) {
                return null;
            }
            $q = $db->table($b['tabel'])->whereIn($b['fk'], $db->table($vt)->where($vk, $idOrang)->select('id'));
        } else {
            $q = $db->table($b['tabel'])->where($b['fk'], $idOrang);
        }
        $rahasia = $p->a->tabelRahasia($b['tabel']) && ! ($this->bolehRahasia)($p->a, $b['tabel']);
        $total = (clone $q)->count();
        $hasil = ['judul' => $b['judul'], 'tabel' => $b['tabel'], 'rahasia' => $rahasia, 'total' => $total, 'kolom' => $kolom, 'baris' => []];
        if ($rahasia || ! $total) {
            return $hasil;
        }
        if (! empty($b['urut']) && in_array($b['urut'], $kolom, true)) {
            $q->orderByDesc($b['urut']);
        }
        $ambil = $kolom;
        if (isset($b['ambil'])) {
            [$at, $afk, $akol] = $b['ambil'];
            if ($this->adaKolom($p, $b['tabel'], $afk) && $akol = $this->kolomAda($p, $at, $akol)) {
                $ambil[] = $afk;
            } else {
                unset($b['ambil']);
            }
        }
        $baris = $q->limit(self::BATAS_BAGIAN)->get(array_unique($ambil))->map(fn ($r) => (array) $r)->all();
        if (isset($b['ambil'])) {  // label dari tabel lain (mis. nama kegiatan nilai, nama DUDI)
            $label = $db->table($at)->whereIn('id', array_filter(array_column($baris, $afk)))->get(array_merge(['id'], $akol))->keyBy('id');
            foreach ($baris as &$r) {
                $l = $label[$r[$afk]] ?? null;
                foreach (array_reverse($akol) as $k) {
                    $r = [$at.'.'.$k => $l->{$k} ?? null] + $r;
                }
                unset($r[$afk]);
            }
            unset($r);
            $hasil['kolom'] = array_merge(array_map(fn ($k) => $at.'.'.$k, $akol), $kolom);
        }
        $hasil['baris'] = $baris;

        return $hasil;
    }
}
