<?php

namespace App\Http\Controllers;

use App\Exceptions\UserError;
use App\Models\Aplikasi;
use App\Services\Audit;
use App\Services\Backup\Penjelajah;
use Illuminate\Http\Request;

/** Jelajah Data: isi database hasil uji pulih terakhir, per aplikasi (baca saja). */
class JelajahController extends Controller
{
    private const IZIN_MENIT = 30;

    private function penjelajah(Aplikasi $a): Penjelajah
    {
        return Penjelajah::untuk($a) ?? throw new UserError("Belum ada uji pulih yang berhasil untuk {$a->nama}. Jalankan Uji pulih dulu.");
    }

    public function index()
    {
        return view('jelajah.index', ['list' => Aplikasi::with('ujiTerakhir.berkas')->orderBy('nama')->get()]);
    }

    public function aplikasi(Aplikasi $aplikasi)
    {
        $j = $this->penjelajah($aplikasi);

        return view('jelajah.aplikasi', ['a' => $aplikasi, 'tabel' => $j->tabel(), 'uji' => $aplikasi->ujiTerakhir]);
    }

    private function izin(Request $r, Aplikasi $a, string $t): bool
    {
        return ! $a->tabelRahasia($t) || (int) $r->session()->get("izin.{$a->kode}.$t") > time();
    }

    public function tabel(Request $request, Aplikasi $aplikasi, string $tabel)
    {
        $j = $this->penjelajah($aplikasi);
        $j->pastikanTabel($tabel);
        if (! $this->izin($request, $aplikasi, $tabel)) {
            return view('jelajah.alasan', ['a' => $aplikasi, 'tabel' => $tabel]);
        }
        $data = $j->baris($tabel, (string) $request->query('q'), (int) $request->query('hal', 1), 50, $request->query('urut'), $request->query('arah') !== 'naik');
        $kunci = "dilihat.{$aplikasi->kode}.$tabel";
        if (! $request->session()->has($kunci)) {
            $request->session()->put($kunci, true);
            Audit::catat('Jelajah tabel', null, "{$aplikasi->kode}.$tabel");
        }

        return view('jelajah.tabel', ['a' => $aplikasi, 'tabel' => $tabel, 'd' => $data, 'q' => (string) $request->query('q'),
            'adaId' => in_array('id', $data['kolom'], true), 'uji' => $aplikasi->ujiTerakhir]);
    }

    public function baris(Request $request, Aplikasi $aplikasi, string $tabel, string $id)
    {
        $j = $this->penjelajah($aplikasi);
        $j->pastikanTabel($tabel);
        abort_unless($this->izin($request, $aplikasi, $tabel), 403);
        $r = $j->satu($tabel, $id) ?? abort(404);
        Audit::catat('Jelajah baris', null, "{$aplikasi->kode}.$tabel#$id");

        return view('jelajah.baris', ['a' => $aplikasi, 'tabel' => $tabel, 'r' => $r]);
    }

    public function alasan(Request $request, Aplikasi $aplikasi, string $tabel)
    {
        $alasan = trim((string) $request->input('alasan'));
        if (mb_strlen($alasan) < 10) {
            throw new UserError('Tulis alasan membuka data rahasia ini (minimal 10 karakter).');
        }
        $request->session()->put("izin.{$aplikasi->kode}.$tabel", time() + self::IZIN_MENIT * 60);
        Audit::catat('Buka tabel rahasia', null, "{$aplikasi->kode}.$tabel — alasan: $alasan");

        return redirect()->route('jelajah.tabel', [$aplikasi, $tabel]);
    }
}
