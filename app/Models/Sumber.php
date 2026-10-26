<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sumber extends Model
{
    protected $table = 'sumber';

    protected $guarded = [];

    public const JENIS = ['aplikasi' => 'VM aplikasi', 'master' => 'Backup Master lain'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean', 'perlu_tarik' => 'boolean', 'port' => 'integer',
            'terakhir_mulai' => 'datetime', 'terakhir_berhasil' => 'datetime'];
    }

    public function tarikan()
    {
        return $this->hasMany(Tarikan::class);
    }

    public function lokal(): bool
    {
        return trim((string) $this->host) === '';
    }

    public function alamat(): string
    {
        return $this->lokal() ? 'lokal: '.$this->folder : "{$this->pengguna}@{$this->host}".($this->port !== 22 ? ":{$this->port}" : '');
    }
}
