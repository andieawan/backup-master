<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aplikasi extends Model
{
    protected $table = 'aplikasi';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['rahasia' => 'array'];
    }

    public function berkas()
    {
        return $this->hasMany(Berkas::class, 'aplikasi', 'kode');
    }

    public function ujiTerakhir()
    {
        return $this->hasOne(UjiPulih::class, 'aplikasi', 'kode')->latestOfMany('id');
    }

    public function tabelRahasia(string $tabel): bool
    {
        return in_array($tabel, $this->rahasia ?? [], true);
    }

    /** Pastikan baris aplikasi ada (nama dari templat, atau info.json dari Backup Master lain). */
    public static function pastikan(string $kode, array $info = []): self
    {
        $t = (array) config("bm.templat.$kode", []);
        $a = self::firstOrNew(['kode' => $kode]);
        if (! $a->exists || $info) {
            $a->fill([
                'nama' => $info['nama'] ?? ($a->nama ?: ($t['nama'] ?? ucfirst($kode))),
                'rahasia' => $info['rahasia'] ?? ($a->rahasia ?? ($t['rahasia'] ?? [])),
                'app_dir' => $info['app_dir'] ?? ($a->app_dir ?? ($t['app_dir'] ?? null)),
                'db' => $info['db'] ?? ($a->db ?? ($t['db'] ?? null)),
            ])->save();
        }

        return $a;
    }
}
