<?php

namespace App\Http\Controllers;

use App\Models\Aplikasi;
use App\Models\Berkas;
use App\Services\Audit;
use App\Services\Backup\IsiBerkas;
use App\Services\Backup\Panduan;
use Illuminate\Http\Request;

/** Satu berkas backup: unduh, lihat isi arsip/potret, dan langkah pemulihan. */
class BerkasController extends Controller
{
    public function show(Request $request, Berkas $berkas)
    {
        $a = Aplikasi::where('kode', $berkas->aplikasi)->first();
        $isi = $folder = null;
        $sub = trim((string) $request->query('p'), '/');
        $q = trim((string) $request->query('q'));
        if ($berkas->jenis === 'arsip') {
            $isi = collect(IsiBerkas::daftarArsip($berkas))->when($q !== '', fn ($c) => $c->filter(fn ($x) => stripos($x['nama'], $q) !== false))->values();
        } elseif ($berkas->jenis === 'cermin') {
            $folder = IsiBerkas::daftarCermin($berkas, $sub);
        }

        return view('berkas.show', ['b' => $berkas, 'a' => $a, 'isi' => $isi?->take(500), 'jumlahIsi' => $isi?->count(), 'folder' => $folder,
            'sub' => $sub, 'q' => $q, 'langkah' => Panduan::langkah($berkas)]);
    }

    public function unduh(Berkas $berkas)
    {
        abort_if($berkas->jenis === 'cermin', 404);
        abort_unless(is_file($berkas->full()), 404);
        Audit::catat('Unduh backup', null, $berkas->path);

        return response()->download($berkas->full(), $berkas->nama, ['Content-Type' => 'application/octet-stream']);
    }

    public function isi(Request $request, Berkas $berkas)
    {
        $nama = (string) $request->query('f');
        $unduh = $request->boolean('unduh');
        Audit::catat($unduh ? 'Unduh isi backup' : 'Lihat isi backup', null, $berkas->path.' → '.$nama);

        return $berkas->jenis === 'cermin' ? IsiBerkas::ambilDariCermin($berkas, $nama, $unduh) : IsiBerkas::ambilDariArsip($berkas, $nama, $unduh);
    }
}
