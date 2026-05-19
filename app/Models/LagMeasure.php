<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LagMeasure extends Model
{
    use LogsActivity;

    protected $fillable = ['kode_lag', 'wig_id', 'cabang_id', 'nama_lag', 'tanggal_target', 'tahun'];

    protected $casts = [
        'tanggal_target' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function wig()
    {
        return $this->belongsTo(Wig::class);
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function leadMeasures()
    {
        return $this->hasMany(LeadMeasure::class);
    }
}
