<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

// API untuk WOL Panel (kunci BM_API_KUNCI, lihat docs/PASANG.md). Prefix /api otomatis.
Route::middleware(['kunci.api', 'throttle:60,1'])->group(function () {
    Route::get('/status', [ApiController::class, 'status']);
    Route::post('/notifikasi', [ApiController::class, 'notifikasi'])->middleware('throttle:20,1');
});
