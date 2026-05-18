<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    protected $fillable = ['wilayah_id', 'kode', 'nama', 'alamat'];

    public function wilayah() { return $this->belongsTo(Wilayah::class); }
    public function users() { return $this->hasMany(User::class); }
    public function realisasis() { return $this->hasMany(LeadMeasureRealisasi::class); }
}
