<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class BuatAdmin extends Command
{
    protected $signature = 'bm:buat-admin {username} {--nama=Administrator} {--password=} {--reset-2fa : Hapus verifikasi dua langkah (HP hilang)}';

    protected $description = 'Buat admin atau ganti passwordnya';

    public function handle(): int
    {
        $username = strtolower(trim((string) $this->argument('username')));
        if ($this->option('reset-2fa')) {
            $u = User::where('username', $username)->first();
            if (! $u) {
                $this->error('Akun tidak ada.');

                return self::FAILURE;
            }
            $u->update(['totp_secret' => null, 'totp_aktif_pada' => null]);
            \App\Services\Audit::catat('Reset 2FA', 'konsol', $username);
            $this->info("Verifikasi dua langkah \"$username\" dihapus; atur ulang saat login berikutnya.");

            return self::SUCCESS;
        }
        $password = (string) ($this->option('password') ?: $this->secret('Password (minimal 12 karakter)'));
        if (mb_strlen($password) < 12) {
            $this->error('Password minimal 12 karakter.');

            return self::FAILURE;
        }
        $u = User::firstOrNew(['username' => $username]);
        $u->fill(['nama' => $u->exists ? $u->nama : (string) $this->option('nama'), 'roles' => ['admin'], 'aktif' => true, 'password' => $password])->save();
        \App\Services\Audit::catat('Buat/ubah admin', 'konsol', $username);
        $this->info("Admin \"$username\" siap.");

        return self::SUCCESS;
    }
}
