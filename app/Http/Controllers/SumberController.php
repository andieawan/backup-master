<?php

namespace App\Http\Controllers;

use App\Exceptions\UserError;
use App\Models\Sumber;
use App\Services\Audit;
use App\Services\Backup\Katalog;
use Illuminate\Http\Request;

/** Dari mana backup ditarik: VM aplikasi atau Backup Master lain. */
class SumberController extends Controller
{
    public static function kunciPublik(): ?string
    {
        $f = config('bm.ssh_key').'.pub';

        return is_readable($f) ? trim((string) file_get_contents($f)) : null;
    }

    public function index()
    {
        return view('sumber.index', ['list' => Sumber::orderBy('jenis')->orderBy('nama')->get(), 'kunci' => self::kunciPublik(),
            'templat' => config('bm.templat')]);
    }

    public function show(Sumber $sumber)
    {
        return view('sumber.show', ['s' => $sumber, 'kunci' => self::kunciPublik(), 'riwayat' => $sumber->tarikan()->orderByDesc('id')->limit(30)->get()]);
    }

    private function data(Request $r, ?Sumber $s): array
    {
        $jenis = $r->input('jenis') === 'master' ? 'master' : 'aplikasi';
        $kode = strtolower(trim((string) $r->input('kode', $s?->kode)));
        if (! Katalog::kodeValid($kode)) {
            throw new UserError('Kode: huruf kecil/angka/garis bawah, maks. 40 karakter (mis. tu, bk, utama).');
        }
        if (Sumber::where('kode', $kode)->when($s, fn ($q) => $q->whereKeyNot($s->id))->exists()) {
            throw new UserError("Kode \"$kode\" sudah dipakai sumber lain.");
        }
        $host = trim((string) $r->input('host'));
        if ($host !== '' && ! preg_match('/^[A-Za-z0-9.\-:]+$/', $host)) {
            throw new UserError('Host berisi karakter tidak sah.');
        }
        $folder = trim((string) $r->input('folder'));
        if ($host === '' && ! str_starts_with($folder, '/')) {
            throw new UserError('Sumber lokal (tanpa host) butuh folder lengkap, mis. /var/backups/tu-pusat.');
        }
        $sub = fn ($k) => ($v = trim((string) $r->input($k), " /")) === '' ? null : (str_contains($v, '..') ? throw new UserError('Subfolder tidak boleh berisi "..".') : $v);
        $arsip = $jenis === 'aplikasi' ? $sub('arsip') : null;
        $cermin = $jenis === 'aplikasi' ? $sub('cermin') : null;
        if ($jenis === 'aplikasi' && $arsip === null && $cermin === null && $r->input('arsip') !== '.') {
            $arsip = '.';
        }
        $jam = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) $r->input('jam')) ? $r->input('jam') : '02:30';

        return ['kode' => $kode, 'nama' => mb_substr(trim((string) $r->input('nama')) ?: strtoupper($kode), 0, 191), 'jenis' => $jenis,
            'host' => $host ?: null, 'port' => max(1, min(65535, (int) $r->input('port', 22) ?: 22)),
            'pengguna' => preg_match('/^[a-z_][a-z0-9_-]{0,31}$/', (string) $r->input('pengguna')) ? $r->input('pengguna') : 'bmtarik',
            'folder' => $folder ?: '/', 'arsip' => $arsip, 'cermin' => $cermin, 'jam' => $jam, 'aktif' => $r->boolean('aktif', $s === null)];
    }

    public function store(Request $request)
    {
        $s = Sumber::create($this->data($request, null));
        Audit::catat('Tambah sumber', null, "{$s->nama} ({$s->alamat()})");

        return redirect()->route('sumber.show', $s)->with('success', 'Sumber ditambahkan. Pasang kunci di sumber (langkah di bawah), lalu klik Tarik sekarang.');
    }

    public function update(Request $request, Sumber $sumber)
    {
        $sumber->update($this->data($request, $sumber));
        Audit::catat('Ubah sumber', null, "{$sumber->nama} ({$sumber->alamat()})");

        return back()->with('success', 'Sumber diperbarui.');
    }

    public function destroy(Sumber $sumber)
    {
        $sumber->tarikan()->delete();
        $sumber->delete();
        Audit::catat('Hapus sumber', null, $sumber->nama.' (berkas backup yang sudah ada TIDAK dihapus)');

        return redirect()->route('sumber.index')->with('success', 'Sumber dihapus. Berkas backup yang sudah tertarik tetap disimpan.');
    }

    public function tarik(Sumber $sumber)
    {
        $sumber->update(['perlu_tarik' => true]);
        Audit::catat('Minta tarik sekarang', null, $sumber->nama);

        return back()->with('success', 'Penarikan dijadwalkan dan mulai dalam ±1 menit. Muat ulang halaman ini untuk melihat hasilnya.');
    }
}
