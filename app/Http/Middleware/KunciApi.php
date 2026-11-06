<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** API khusus WOL Panel: kunci Bearer (BM_API_KUNCI) + daftar IP (BM_API_IP). API mati bila kunci kosong. */
class KunciApi
{
    public function handle(Request $request, Closure $next)
    {
        $kunci = (string) config('bm.api_kunci');
        if ($kunci === '') {
            return response()->json(['ok' => false, 'error' => 'API tidak aktif.'], 404);
        }
        $ip = array_filter(array_map('trim', explode(',', (string) config('bm.api_ip'))));
        if ($ip && ! in_array($request->ip(), $ip, true)) {
            return response()->json(['ok' => false, 'error' => 'IP tidak diizinkan.'], 403);
        }
        if (! hash_equals($kunci, (string) $request->bearerToken())) {
            return response()->json(['ok' => false, 'error' => 'Kunci API salah.'], 401);
        }

        return $next($request);
    }
}
