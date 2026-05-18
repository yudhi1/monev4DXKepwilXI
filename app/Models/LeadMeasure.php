<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LeadMeasure extends Model
{
    use LogsActivity;

    protected $fillable = ['kode_lead', 'lag_measure_id', 'cabang_id', 'wig_id', 'nama_lead', 'satuan', 'is_active', 'tahun'];

    protected $casts = ['is_active' => 'boolean'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function lagMeasure() { return $this->belongsTo(LagMeasure::class); }
    public function cabang() { return $this->belongsTo(Cabang::class); }
    public function wig() { return $this->belongsTo(Wig::class); }
    public function realisasis() { return $this->hasMany(LeadMeasureRealisasi::class); }
}
