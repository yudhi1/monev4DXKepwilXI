<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, LogsActivity, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'wilayah_id', 'cabang_id', 'unit_kerja_id',
        'jabatan', 'is_active', 'alamat',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'wilayah_id', 'cabang_id', 'unit_kerja_id', 'jabatan', 'is_active', 'alamat'])
            ->logOnlyDirty();
    }

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function unitKerja()
    {
        return $this->belongsTo(UnitKerja::class);
    }

    /** Pegawai perorangan = punya unit kerja. Akun institusi lama tidak. */
    public function scopePegawai($query)
    {
        return $query->whereNotNull('unit_kerja_id');
    }
}
