<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Berkas extends Model
{
    protected $table = 'berkas';

    protected $guarded = [];

    public const JENIS = ['db' => 'Database', 'arsip' => 'Arsip berkas', 'cermin' => 'Potret folder'];

    protected function casts(): array
    {
        return ['waktu' => 'datetime', 'utuh' => 'boolean', 'ukuran' => 'integer'];
    }

    public function full(): string
    {
        return rtrim((string) config('bm.data_dir'), '/').'/'.$this->path;
    }
}
