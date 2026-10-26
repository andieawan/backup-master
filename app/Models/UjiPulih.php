<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UjiPulih extends Model
{
    protected $table = 'uji_pulih';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['waktu' => 'datetime', 'rincian' => 'array'];
    }

    public function berkas()
    {
        return $this->belongsTo(Berkas::class);
    }
}
