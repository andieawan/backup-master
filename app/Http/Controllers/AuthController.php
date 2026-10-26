<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Audit;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/** Login admin lokal: password, lalu kode 6 angka dari aplikasi authenticator (bila sudah diatur). */
class AuthController extends Controller
{
    public function form()
    {
        return Auth::check() ? redirect()->route('beranda') : view('auth.login');
    }

    private function batasi(Request $request, string $kunci): string
    {
        $key = 'login:'.sha1($request->ip().'|'.$kunci);
        if (RateLimiter::tooManyAttempts($key, 8)) {
            throw ValidationException::withMessages(['username' => 'Terlalu banyak percobaan gagal. Coba lagi dalam '.ceil(RateLimiter::availableIn($key) / 60).' menit.']);
        }

        return $key;
    }

    public function login(Request $request)
    {
        $data = $request->validate(['username' => 'required|string|max:191', 'password' => 'required|string|max:255']);
        $username = strtolower(trim($data['username']));
        $key = $this->batasi($request, $username);
        $user = User::where('username', $username)->first();
        $ok = Hash::check($data['password'], $user?->password ?: Hash::make(bin2hex(random_bytes(8)))) && $user && $user->password && $user->aktif;
        if (! $ok) {
            RateLimiter::hit($key, 15 * 60);
            Audit::catat('Login gagal', $username, 'password salah / akun nonaktif');
            throw ValidationException::withMessages(['username' => 'Username atau password salah, atau akun nonaktif.']);
        }
        RateLimiter::clear($key);
        if ($user->punya2fa()) {
            $request->session()->put('2fa_user', $user->id);
            $request->session()->put('2fa_sampai', time() + 300);

            return redirect()->route('login.2fa');
        }

        return $this->masuk($request, $user, 'password');
    }

    public function form2fa(Request $request)
    {
        return $request->session()->has('2fa_user') ? view('auth.2fa') : redirect()->route('login');
    }

    public function cek2fa(Request $request)
    {
        $id = $request->session()->get('2fa_user');
        if (! $id || time() > (int) $request->session()->get('2fa_sampai')) {
            $request->session()->forget(['2fa_user', '2fa_sampai']);

            return redirect()->route('login')->with('error', 'Waktu habis, masuk lagi.');
        }
        $key = $this->batasi($request, '2fa:'.$id);
        $user = User::find($id);
        if (! $user || ! $user->aktif || ! Totp::cocok((string) $user->totp_secret, (string) $request->input('kode'))) {
            RateLimiter::hit($key, 15 * 60);
            Audit::catat('Login gagal', $user?->username, 'kode verifikasi salah');
            throw ValidationException::withMessages(['kode' => 'Kode salah. Pastikan jam HP tepat, lalu coba kode terbaru.']);
        }
        RateLimiter::clear($key);
        $request->session()->forget(['2fa_user', '2fa_sampai']);

        return $this->masuk($request, $user, 'password + 2FA');
    }

    private function masuk(Request $request, User $user, string $cara)
    {
        Auth::login($user);
        $request->session()->regenerate();
        Audit::catat('Login', $user->username, $cara);

        return redirect()->intended(route('beranda'));
    }

    public function logout(Request $request)
    {
        Audit::catat('Logout', Auth::user()?->username);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
