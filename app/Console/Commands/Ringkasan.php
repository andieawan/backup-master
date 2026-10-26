<?php

namespace App\Console\Commands;

use App\Services\Backup\Notifikasi;
use App\Services\Backup\Status;
use Illuminate\Console\Command;

class Ringkasan extends Command
{
    protected $signature = 'bm:ringkasan {--kirim : kirim ke Telegram}';

    protected $description = 'Tampilkan (dan kirim) ringkasan kesehatan semua backup';

    public function handle(): int
    {
        $teks = Status::ringkasan();
        $this->line($teks);
        if ($this->option('kirim')) {
            $this->line(Notifikasi::kirim($teks) ? 'Terkirim ke Telegram.' : 'Telegram belum diatur / gagal.');
        }

        return self::SUCCESS;
    }
}
