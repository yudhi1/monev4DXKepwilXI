<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WigTarget extends Model
{
    protected $fillable = ['wig_id', 'cabang_id', 'nilai_awal', 'nilai_target', 'satuan', 'tanggal_target'];

    protected $casts = [
        'nilai_awal' => 'decimal:2',
        'nilai_target' => 'decimal:2',
        'tanggal_target' => 'date',
    ];

    public function wig()
    {
        return $this->belongsTo(Wig::class);
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function nilaiSekarang(): float
    {
        $base = (float) $this->nilai_awal;
        $kontribusi = LeadMeasureRealisasi::whereHas('leadMeasure', fn ($q) => $q->where('wig_id', $this->wig_id))
            ->where('cabang_id', $this->cabang_id)
            ->sum('realisasi');

        return $base + (float) $kontribusi;
    }

    public function persenProgres(): float
    {
        $range = (float) $this->nilai_target - (float) $this->nilai_awal;
        if ($range <= 0) {
            return 0;
        }
        $pct = (($this->nilaiSekarang() - (float) $this->nilai_awal) / $range) * 100;

        return round(max(0, $pct), 2);
    }
}
