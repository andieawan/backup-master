<?php

namespace App\Services;

use App\Models\AuditLog;

/** Jejak siapa membuka/mengunduh/mengubah apa. Disimpan 50.000 baris terbaru. */
class Audit
{
    public static function catat(string $aksi, ?string $pelaku = null, string $rincian = ''): void
    {
        AuditLog::create([
            'waktu' => now('UTC')->format('Y-m-d H:i:s'), 'aksi' => $aksi,
            'pelaku' => $pelaku ?? (auth()->user()?->username ?? 'sistem'), 'ip' => app()->runningInConsole() ? null : request()->ip(),
            'rincian' => mb_substr($rincian, 0, 1000),
        ]);
        if (random_int(1, 50) === 1 && ($batas = AuditLog::orderByDesc('id')->skip(49999)->value('id'))) {
            AuditLog::where('id', '<', $batas)->delete();
        }
    }
}
