<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonevSegmen extends Model
{
    protected $table = 'monev_segmens';

    protected $fillable = [
        'nama', 'urutan', 'is_active',
    ];

    protected $casts = [
        'urutan' => 'integer',
        'is_active' => 'boolean',
    ];

    public function realisasis()
    {
        return $this->hasMany(MonevIuranRealisasi::class, 'segmen_id');
    }
}
