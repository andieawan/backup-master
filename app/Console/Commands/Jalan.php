<?php

namespace App\Console\Commands;

use App\Models\Aplikasi;
use App\Models\Sumber;
use App\Services\Backup\Katalog;
use App\Services\Backup\Pemulih;
use App\Services\Backup\Penarik;
use App\Services\Backup\Status;
use App\Services\Backup\Notifikasi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/** Dijalankan penjadwal tiap menit: tarik sumber yang jatuh tempo + kerjakan permintaan dari tombol di layar. */
class Jalan extends Command
{
    protected $signature = 'bm:jalan';

    protected $description = 'Tarik backup yang jatuh tempo dan uji pulih yang diminta (dipanggil penjadwal tiap menit)';

    public function handle(): int
    {
        foreach (Penarik::jatuhTempo() as $s) {
            $t = Penarik::tarik($s);
            $this->line("{$s->nama}: {$t->status} ({$t->berkas_baru} berkas baru)".($t->pesan ? " — {$t->pesan}" : ''));
        }
        foreach (Aplikasi::pluck('kode') as $kode) {
            if (Cache::pull('minta_uji:'.$kode)) {
                $u = Pemulih::ujiTerbaru($kode);
                $this->line("Uji pulih $kode: ".($u?->status ?? 'tidak ada backup database'));
            }
        }
        if (Cache::pull('minta_pindai')) {
            Katalog::pindai();
            Katalog::tulisManifest();
        }

        return self::SUCCESS;
    }
}
