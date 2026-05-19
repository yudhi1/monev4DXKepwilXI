<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pemda extends Model
{
    use LogsActivity;

    protected $table = 'pemdas';

    protected $fillable = ['cabang_id', 'nama', 'keterangan'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }
}
