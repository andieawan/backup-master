<?php

use Illuminate\Support\Facades\Schedule;

// Cron wajib: * * * * * www-data cd /var/www/backup-master && php artisan schedule:run >> /dev/null 2>&1
// Tiap menit: tarik sumber yang jatuh tempo (jam tarik tiap sumber diatur di layar; yang gagal diulang tiap jam).
Schedule::command('bm:jalan')->everyMinute()->withoutOverlapping(360)->runInBackground();
// Ringkasan pagi ke Telegram (bila diatur).
Schedule::command('bm:ringkasan --kirim')->dailyAt('07:00')->timezone(config('bm.timezone'));
