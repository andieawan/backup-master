<?php

use App\Http\Controllers\AkunController;
use App\Http\Controllers\AplikasiController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\BerkasController;
use App\Http\Controllers\JelajahController;
use App\Http\Controllers\SumberController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'form'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/login/kode', [AuthController::class, 'form2fa'])->name('login.2fa');
    Route::post('/login/kode', [AuthController::class, 'cek2fa'])->middleware('throttle:10,1');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'akun.aktif', 'wajib2fa'])->group(function () {
    Route::get('/', BerandaController::class)->name('beranda');

    Route::get('/akun', [AkunController::class, 'index'])->name('akun');
    Route::post('/akun/2fa', [AkunController::class, 'aktifkan2fa'])->middleware('throttle:10,1')->name('akun.2fa');
    Route::put('/akun/password', [AkunController::class, 'password'])->middleware('throttle:10,1')->name('akun.password');

    Route::get('/sumber', [SumberController::class, 'index'])->name('sumber.index');
    Route::post('/sumber', [SumberController::class, 'store'])->name('sumber.store');
    Route::get('/sumber/{sumber}', [SumberController::class, 'show'])->whereNumber('sumber')->name('sumber.show');
    Route::put('/sumber/{sumber}', [SumberController::class, 'update'])->whereNumber('sumber')->name('sumber.update');
    Route::delete('/sumber/{sumber}', [SumberController::class, 'destroy'])->whereNumber('sumber')->name('sumber.destroy');
    Route::post('/sumber/{sumber}/tarik', [SumberController::class, 'tarik'])->whereNumber('sumber')->middleware('throttle:20,1')->name('sumber.tarik');

    Route::get('/aplikasi/{aplikasi:kode}', [AplikasiController::class, 'show'])->name('aplikasi.show');
    Route::put('/aplikasi/{aplikasi:kode}', [AplikasiController::class, 'update'])->name('aplikasi.update');
    Route::post('/aplikasi/{aplikasi:kode}/uji', [AplikasiController::class, 'uji'])->middleware('throttle:10,1')->name('aplikasi.uji');

    Route::get('/berkas/{berkas}', [BerkasController::class, 'show'])->whereNumber('berkas')->name('berkas.show');
    Route::get('/berkas/{berkas}/unduh', [BerkasController::class, 'unduh'])->whereNumber('berkas')->middleware('throttle:30,1')->name('berkas.unduh');
    Route::get('/berkas/{berkas}/isi', [BerkasController::class, 'isi'])->whereNumber('berkas')->middleware('throttle:240,1')->name('berkas.isi');

    Route::get('/jelajah', [JelajahController::class, 'index'])->name('jelajah.index');
    Route::get('/jelajah/{aplikasi:kode}', [JelajahController::class, 'aplikasi'])->name('jelajah.aplikasi');
    Route::get('/jelajah/{aplikasi:kode}/{tabel}', [JelajahController::class, 'tabel'])->where('tabel', '[A-Za-z0-9_]+')->name('jelajah.tabel');
    Route::post('/jelajah/{aplikasi:kode}/{tabel}/alasan', [JelajahController::class, 'alasan'])->where('tabel', '[A-Za-z0-9_]+')->name('jelajah.alasan');
    Route::get('/jelajah/{aplikasi:kode}/{tabel}/{id}', [JelajahController::class, 'baris'])->where('tabel', '[A-Za-z0-9_]+')->name('jelajah.baris');

    Route::get('/audit', AuditController::class)->name('audit');
});
