<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wilayah extends Model
{
    protected $fillable = ['kode', 'nama', 'deskripsi'];

    public function cabangs()
    {
        return $this->hasMany(Cabang::class);
    }

    public function wigs()
    {
        return $this->hasMany(Wig::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
