<?php

namespace App\Console\Commands;

use App\Models\Aplikasi;
use App\Services\Backup\Pemulih;
use Illuminate\Console\Command;

class Uji extends Command
{
    protected $signature = 'bm:uji {kode? : kode aplikasi; kosong = semua}';

    protected $description = 'Uji pulih backup database terbaru (dimuat ke database bmj_<kode>, bukan database aplikasi)';

    public function handle(): int
    {
        $kode = $this->argument('kode');
        $gagal = 0;
        foreach (Aplikasi::when($kode, fn ($q) => $q->where('kode', $kode))->get() as $a) {
            $u = Pemulih::ujiTerbaru($a->kode);
            $this->line("{$a->nama}: ".($u ? "{$u->status} — {$u->tabel} tabel, {$u->baris} baris".($u->pesan ? " ({$u->pesan})" : '') : 'belum ada backup database'));
            $gagal += $u && $u->status === 'gagal' ? 1 : 0;
        }

        return $gagal ? self::FAILURE : self::SUCCESS;
    }
}
