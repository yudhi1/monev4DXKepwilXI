<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WigRealisasi extends Model
{
    use LogsActivity;

    protected $table = 'wig_realisasis';

    protected $fillable = ['wig_id', 'cabang_id', 'tahun', 'bulan', 'nilai', 'catatan', 'created_by'];

    protected $casts = [
        'nilai' => 'decimal:2',
        'tahun' => 'integer',
        'bulan' => 'integer',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
