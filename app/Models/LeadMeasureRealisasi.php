<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LeadMeasureRealisasi extends Model
{
    use LogsActivity;

    protected $table = 'lead_measure_realisasis';

    protected $fillable = [
        'lead_measure_id', 'cabang_id', 'tahun', 'bulan', 'minggu_ke',
        'target', 'realisasi', 'persentase', 'catatan', 'created_by',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $r) {
            $r->persentase = $r->target > 0 ? round(($r->realisasi / $r->target) * 100, 2) : 0;
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function leadMeasure()
    {
        return $this->belongsTo(LeadMeasure::class);
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }
}
