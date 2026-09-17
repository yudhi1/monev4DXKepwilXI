<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Wig extends Model
{
    use LogsActivity;

    public const BIDANG = ['JPK', 'KML', 'PIKEU', 'SDMUK'];

    protected $fillable = [
        'kode_wig', 'nama_wig', 'indikator_output', 'sifat_capaian', 'arah',
        'bidang', 'tahun', 'wilayah_id', 'created_by',
    ];

    /** Kunci sifat pengukuran yang dikenal, dari config/wig.php. */
    public static function daftarSifat(): array
    {
        return array_keys(config('wig.sifat'));
    }

    /** Kunci arah keberhasilan yang dikenal, dari config/wig.php. */
    public static function daftarArah(): array
    {
        return array_keys(config('wig.arah'));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lagMeasures()
    {
        return $this->hasMany(LagMeasure::class);
    }

    public function leadMeasures()
    {
        return $this->hasMany(LeadMeasure::class);
    }

    public function targets()
    {
        return $this->hasMany(WigTarget::class);
    }
}
