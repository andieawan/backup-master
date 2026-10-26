<?php

namespace App\Http\Controllers;

use App\Models\Sumber;
use App\Models\Tarikan;
use App\Services\Backup\Status;

class BerandaController extends Controller
{
    public function __invoke()
    {
        return view('beranda', ['status' => Status::semua(), 'disk' => Status::disk(),
            'sumber' => Sumber::orderBy('nama')->get(), 'riwayat' => Tarikan::with('sumber')->orderByDesc('id')->limit(15)->get()]);
    }
}
