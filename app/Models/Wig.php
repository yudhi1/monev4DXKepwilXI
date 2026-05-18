<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Wig extends Model
{
    use LogsActivity;

    public const BIDANG = ['JPK', 'KML', 'PIKEU', 'SDMUK'];

    protected $fillable = ['kode_wig', 'nama_wig', 'indikator_output', 'bidang', 'tahun', 'wilayah_id', 'created_by'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function wilayah() { return $this->belongsTo(Wilayah::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function lagMeasures() { return $this->hasMany(LagMeasure::class); }
    public function leadMeasures() { return $this->hasMany(LeadMeasure::class); }
    public function targets() { return $this->hasMany(WigTarget::class); }
}
