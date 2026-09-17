<?php

namespace App\Models\Kinerja;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Indikator capaian kinerja, milik satu kategori. */
class Indikator extends Model
{
    protected $table = 'kinerja_indikators';

    protected $fillable = ['kinerja_kategori_id', 'nama', 'keterangan', 'urutan', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kinerja_kategori_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(FileCapaian::class, 'kinerja_indikator_id');
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
