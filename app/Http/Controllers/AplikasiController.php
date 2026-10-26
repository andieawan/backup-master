<?php

namespace App\Http\Controllers;

use App\Exceptions\UserError;
use App\Models\Aplikasi;
use App\Models\Berkas;
use App\Models\UjiPulih;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AplikasiController extends Controller
{
    public function show(Request $request, Aplikasi $aplikasi)
    {
        $jenis = array_key_exists((string) $request->query('jenis'), Berkas::JENIS) ? $request->query('jenis') : null;

        return view('aplikasi.show', ['a' => $aplikasi,
            'berkas' => Berkas::where('aplikasi', $aplikasi->kode)->when($jenis, fn ($q) => $q->where('jenis', $jenis))->orderByDesc('waktu')->orderBy('nama')->paginate(50)->withQueryString(),
            'uji' => UjiPulih::where('aplikasi', $aplikasi->kode)->with('berkas')->orderByDesc('id')->limit(10)->get(), 'jenis' => $jenis]);
    }

    public function update(Request $request, Aplikasi $aplikasi)
    {
        $rahasia = array_values(array_filter(array_map(fn ($t) => preg_replace('/[^A-Za-z0-9_]/', '', trim($t)), explode(',', (string) $request->input('rahasia')))));
        $nama = trim((string) $request->input('nama')) ?: throw new UserError('Nama wajib diisi.');
        $aplikasi->update(['nama' => mb_substr($nama, 0, 191), 'rahasia' => $rahasia,
            'app_dir' => trim((string) $request->input('app_dir')) ?: null, 'db' => preg_replace('/[^A-Za-z0-9_]/', '', (string) $request->input('db')) ?: null]);
        Cache::put('minta_pindai', true, 3600); // tulis ulang info.json agar salinan ikut tahu
        Audit::catat('Ubah aplikasi', null, $aplikasi->kode.': rahasia = '.implode(', ', $rahasia));

        return back()->with('success', 'Disimpan.');
    }

    public function uji(Aplikasi $aplikasi)
    {
        Cache::put('minta_uji:'.$aplikasi->kode, true, 3600);
        Audit::catat('Minta uji pulih', null, $aplikasi->nama);

        return back()->with('success', 'Uji pulih dijadwalkan dan mulai dalam ±1 menit (database besar bisa beberapa menit).');
    }
}
