<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Bila BM_WAJIB_2FA, admin yang belum mengatur verifikasi dua langkah diarahkan ke halaman Akun dulu. */
class Wajib2fa
{
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        if ($u && config('bm.wajib_2fa') && ! $u->punya2fa() && ! $request->routeIs('akun', 'akun.*', 'logout')) {
            return redirect()->route('akun')->with('warning', 'Atur verifikasi dua langkah dulu: data di sini berisi semua aplikasi sekolah.');
        }

        return $next($request);
    }
}
