<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonevIuranRealisasi extends Model
{
    protected $table = 'monev_iuran_realisasis';

    protected $fillable = [
        'cabang_id', 'segmen_id', 'tahun', 'bulan',
        'mg1', 'mg2', 'mg3', 'mg4',
        'realisasi_sd_bulan_lalu', 'keterangan',
        'status_periode', 'locked_at', 'locked_by', 'created_by',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'bulan' => 'integer',
        'mg1' => 'decimal:2',
        'mg2' => 'decimal:2',
        'mg3' => 'decimal:2',
        'mg4' => 'decimal:2',
        'realisasi_sd_bulan_lalu' => 'decimal:2',
        'locked_at' => 'datetime',
    ];

    public const BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function segmen()
    {
        return $this->belongsTo(MonevSegmen::class, 'segmen_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function getTotalBulanIniAttribute()
    {
        return (float) ($this->mg1 + $this->mg2 + $this->mg3 + $this->mg4);
    }

    public function getTotalSdBulanIniAttribute()
    {
        return (float) ($this->realisasi_sd_bulan_lalu + $this->total_bulan_ini);
    }
}
