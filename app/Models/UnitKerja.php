<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bidang/unit kerja — di Kedeputian Wilayah (tingkat 'wilayah') maupun di
 * kantor cabang (tingkat 'cabang').
 *
 * Bidang di kantor cabang berdiri sendiri per cabang, jadi "Bidang PMU
 * KC Denpasar" dan "Bidang PMU KC Kupang" adalah dua baris berbeda.
 */
class UnitKerja extends Model
{
    protected $fillable = ['kode', 'nama', 'tingkat', 'wilayah_id', 'cabang_id', 'urutan', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Nama lengkap termasuk kantor induknya, mis. "Bidang PMU — KC Denpasar". */
    public function getNamaLengkapAttribute(): string
    {
        $induk = $this->tingkat === 'cabang'
            ? ($this->cabang?->nama ?? 'Kantor Cabang')
            : ($this->wilayah?->nama ?? 'Kedeputian Wilayah');

        return "{$this->nama} — {$induk}";
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Urut yang mudah dibaca: Kedeputian Wilayah dulu, lalu per kantor cabang. */
    public function scopeUrutTampil(Builder $query): Builder
    {
        return $query->orderByRaw("tingkat = 'cabang'")
            ->orderBy('cabang_id')
            ->orderBy('urutan');
    }
}
