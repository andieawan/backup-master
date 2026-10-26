<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Pesan ke Telegram (opsional: BM_TELEGRAM_TOKEN + BM_TELEGRAM_CHAT). Gagal kirim tidak menghentikan proses. */
class Notifikasi
{
    public static function aktif(): bool
    {
        return config('bm.telegram_token') !== '' && config('bm.telegram_chat') !== '';
    }

    public static function kirim(string $teks): bool
    {
        if (! self::aktif()) {
            return false;
        }
        try {
            $r = Http::timeout(15)->asForm()->post('https://api.telegram.org/bot'.config('bm.telegram_token').'/sendMessage', [
                'chat_id' => config('bm.telegram_chat'), 'text' => '['.config('bm.lokasi').'] '.mb_substr($teks, 0, 3500), 'disable_web_page_preview' => 'true',
            ]);

            return $r->successful();
        } catch (\Throwable $e) {
            Log::warning('Telegram gagal: '.$e->getMessage());

            return false;
        }
    }
}
