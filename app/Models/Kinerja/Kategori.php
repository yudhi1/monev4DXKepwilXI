<?php

namespace App\Models\Kinerja;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Kategori capaian kinerja; menaungi banyak indikator. */
class Kategori extends Model
{
    protected $table = 'kinerja_kategoris';

    protected $fillable = ['nama', 'keterangan', 'urutan', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function indikators(): HasMany
    {
        return $this->hasMany(Indikator::class, 'kinerja_kategori_id');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeUrutTampil(Builder $query): Builder
    {
        return $query->orderBy('urutan')->orderBy('nama');
    }
}
