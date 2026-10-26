<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarikan extends Model
{
    protected $table = 'tarikan';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['mulai' => 'datetime', 'selesai' => 'datetime'];
    }

    public function sumber()
    {
        return $this->belongsTo(Sumber::class);
    }
}
