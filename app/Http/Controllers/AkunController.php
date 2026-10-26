<?php

namespace App\Http\Controllers;

use App\Exceptions\UserError;
use App\Services\Audit;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/** Akun saya: ganti password & atur verifikasi dua langkah. */
class AkunController extends Controller
{
    public function index(Request $request)
    {
        $u = $this->me();
        $rahasia = null;
        if (! $u->punya2fa()) {
            $rahasia = $request->session()->get('totp_baru') ?: Totp::rahasiaBaru();
            $request->session()->put('totp_baru', $rahasia);
        }

        return view('akun', ['u' => $u, 'rahasia' => $rahasia,
            'uri' => $rahasia ? Totp::uri($rahasia, $u->username, 'Backup Master '.config('bm.lokasi')) : null]);
    }

    public function aktifkan2fa(Request $request)
    {
        $rahasia = (string) $request->session()->get('totp_baru');
        if ($rahasia === '' || ! Totp::cocok($rahasia, (string) $request->input('kode'))) {
            throw new UserError('Kode tidak cocok. Pastikan kunci sudah dimasukkan ke aplikasi authenticator dan jam HP tepat.');
        }
        $this->me()->update(['totp_secret' => $rahasia, 'totp_aktif_pada' => now()]);
        $request->session()->forget('totp_baru');
        Audit::catat('Aktifkan 2FA');

        return redirect()->route('beranda')->with('success', 'Verifikasi dua langkah aktif. Mulai sekarang login butuh kode dari HP.');
    }

    public function password(Request $request)
    {
        $u = $this->me();
        if (! Hash::check((string) $request->input('lama'), (string) $u->password)) {
            throw new UserError('Password lama salah.');
        }
        $baru = (string) $request->input('baru');
        if (mb_strlen($baru) < 12 || $baru !== (string) $request->input('ulang')) {
            throw new UserError('Password baru minimal 12 karakter dan harus sama dengan ulangannya.');
        }
        $u->update(['password' => $baru]);
        Audit::catat('Ganti password');

        return back()->with('success', 'Password diganti.');
    }
}
