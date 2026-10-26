<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/** Akun admin lokal Backup Master (tidak lewat Pusat, agar tetap bisa dibuka saat Pusat mati). */
class User extends Authenticatable
{
    protected $guarded = [];

    protected $hidden = ['password', 'remember_token', 'totp_secret'];

    protected function casts(): array
    {
        return ['roles' => 'array', 'aktif' => 'boolean', 'password' => 'hashed', 'totp_secret' => 'encrypted', 'totp_aktif_pada' => 'datetime'];
    }

    public function punya2fa(): bool
    {
        return $this->totp_aktif_pada !== null && $this->totp_secret;
    }
}
