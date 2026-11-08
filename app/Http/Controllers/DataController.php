<?php

namespace App\Http\Controllers;

use App\Models\Aplikasi;
use App\Services\Audit;
use App\Services\Backup\Gabungan;
use Illuminate\Http\Request;

/** Master App: cari di semua aplikasi & profil siswa/guru lintas aplikasi (salinan hasil uji pulih, baca saja). */
class DataController extends Controller
{
    private function gabungan(Request $r): Gabungan
    {
        // tabel rahasia yang sudah dibuka dengan alasan di Jelajah Data (30 menit) ikut tampil di sini
        return new Gabungan(fn (Aplikasi $a, string $t) => (int) $r->session()->get("izin.{$a->kode}.$t") > time());
    }

    public function cari(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $hasil = mb_strlen($q) >= 2 ? $this->gabungan($request)->cari($q) : [];
        if ($q !== '') {
            Audit::catat('Cari semua aplikasi', null, mb_substr($q, 0, 100));
        }

        return view('data.cari', ['q' => $q, 'hasil' => $hasil]);
    }

    public function profil(Request $request, string $jenis, string $uuid)
    {
        abort_unless(in_array($jenis, ['siswa', 'guru'], true) && preg_match('/^[0-9a-fA-F-]{36}$/', $uuid), 404);
        $p = $this->gabungan($request)->profil($jenis, $uuid);
        abort_if(! $p['induk'] && ! collect($p['aplikasi'])->contains('ada', true), 404);
        Audit::catat('Profil '.$jenis, null, $uuid);

        return view('data.profil', ['jenis' => $jenis, 'uuid' => $uuid, 'p' => $p]);
    }
}
