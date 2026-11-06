<?php

namespace App\Http\Controllers;

use App\Models\Sumber;
use App\Services\Backup\Notifikasi;
use App\Services\Backup\Status;
use Illuminate\Http\Request;

/** API untuk WOL Panel: status backup per aplikasi (hanya ringkasan, tanpa isi data) dan penerus notifikasi Telegram. */
class ApiController extends Controller
{
    public function status()
    {
        $host = Sumber::where('jenis', 'aplikasi')->pluck('host', 'kode');
        $hasil = [];
        foreach (Status::semua() as $r) {
            $b = $r['tanpa_db'] ? $r['arsip'] : $r['db'];
            $hasil[] = [
                'kode' => $r['a']->kode, 'nama' => $r['a']->nama, 'host' => (string) ($host[$r['a']->kode] ?? ''),
                'terbaru' => $b?->waktu?->toIso8601String(), 'umur_jam' => $r['umur'], 'jenis' => $r['tanpa_db'] ? 'arsip' : 'db',
                'utuh' => $b?->utuh, 'masalah' => $r['masalah'], 'uji_pulih' => $r['uji']?->status,
            ];
        }

        return response()->json(['ok' => true, 'lokasi' => config('bm.lokasi'), 'waktu' => now()->toIso8601String(), 'aplikasi' => $hasil]);
    }

    public function notifikasi(Request $request)
    {
        $d = $request->validate(['teks' => 'required|string|max:1500', 'dari' => 'nullable|string|max:40']);
        $dari = preg_replace('/[^\pL\pN ._-]/u', '', (string) ($d['dari'] ?? 'WOL Panel')) ?: 'WOL Panel';
        if (! Notifikasi::aktif()) {
            return response()->json(['ok' => false, 'error' => 'Telegram belum diatur di Backup Master (BM_TELEGRAM_TOKEN & BM_TELEGRAM_CHAT).'], 409);
        }
        $terkirim = Notifikasi::kirim("$dari: ".$d['teks']);

        return response()->json(['ok' => $terkirim, 'error' => $terkirim ? null : 'Telegram menolak atau tidak terjangkau.'], $terkirim ? 200 : 502);
    }
}
