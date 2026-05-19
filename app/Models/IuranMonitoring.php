<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class IuranMonitoring extends Model
{
    use LogsActivity;

    protected $table = 'iuran_monitorings';

    protected $fillable = [
        'cabang_id', 'tahun', 'bulan', 'minggu', 'no_urut',
        'nama_pemda', 'tagihan', 'status_bayar', 'outstanding',
        'pic', 'kendala', 'keterangan', 'target_penyelesaian', 'created_by',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'bulan' => 'integer',
        'minggu' => 'integer',
        'no_urut' => 'integer',
        'tagihan' => 'decimal:2',
        'outstanding' => 'decimal:2',
        'target_penyelesaian' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }
}
