<?php

namespace App\Console\Commands;

use App\Models\Sumber;
use App\Services\Backup\Penarik;
use Illuminate\Console\Command;

class Tarik extends Command
{
    protected $signature = 'bm:tarik {kode? : kode sumber; kosong = semua sumber aktif}';

    protected $description = 'Tarik backup sekarang (tanpa menunggu jadwal)';

    public function handle(): int
    {
        $q = Sumber::where('aktif', true);
        if ($k = $this->argument('kode')) {
            $q->where('kode', $k);
        }
        $daftar = $q->get();
        if ($daftar->isEmpty()) {
            $this->error('Tidak ada sumber aktif'.($k ? " dengan kode $k" : '').'.');

            return self::FAILURE;
        }
        $gagal = 0;
        foreach ($daftar as $s) {
            $this->info("Menarik {$s->nama} ({$s->alamat()}) ...");
            $t = Penarik::tarik($s);
            $gagal += $t->status === 'ok' ? 0 : 1;
            $this->line("  {$t->status}: {$t->berkas_baru} berkas baru".($t->pesan ? " — {$t->pesan}" : ''));
        }

        return $gagal ? self::FAILURE : self::SUCCESS;
    }
}
