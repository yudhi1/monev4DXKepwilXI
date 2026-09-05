<?php

namespace App\Models;

use App\Models\Pm\Task;
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
        'name', 'email', 'password', 'tipe', 'wilayah_id', 'cabang_id',
        'unit_kerja_id', 'jabatan', 'pm_role', 'is_active', 'alamat',
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

    /** Task modul PM yang ditugaskan kepada pegawai ini. */
    public function tasksPm()
    {
        return $this->belongsToMany(Task::class, 'pm_task_assignees', 'user_id', 'task_id');
    }

    /** Akun perorangan pegawai — pengguna modul Project Management. */
    public function scopePegawai($query)
    {
        return $query->where('tipe', 'pegawai');
    }

    /**
     * Akun unit kerja (kantor) — pengguna modul Monev 4DX.
     *
     * Namanya "akunUnitKerja", bukan "unitKerja", karena nama itu sudah dipakai
     * relasi belongsTo ke bidang — metode relasi akan menutupi scope-nya.
     */
    public function scopeAkunUnitKerja($query)
    {
        return $query->where('tipe', 'unit_kerja');
    }

    /** Namanya sengaja bukan pegawai(), agar tidak menutupi scopePegawai(). */
    public function adalahPegawai(): bool
    {
        return $this->tipe === 'pegawai';
    }

    /**
     * Menyelaraskan permission modul PM dengan pm_role.
     *
     * Permission PM sengaja tidak dilekatkan pada role spatie: role di sini
     * milik modul 4DX, dan kedua jenis akun memang dipisah.
     */
    public function selaraskanIzinPm(): void
    {
        $semua = collect(config('pm.role_akun'))->pluck('permissions')->flatten()->unique();
        $diberikan = collect(config("pm.role_akun.{$this->pm_role}.permissions", []));

        foreach ($semua as $izin) {
            if ($diberikan->contains($izin)) {
                $this->givePermissionTo($izin);
            } elseif ($this->hasDirectPermission($izin)) {
                $this->revokePermissionTo($izin);
            }
        }
    }
}
