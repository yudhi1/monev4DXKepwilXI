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
        'name', 'npp', 'email', 'password', 'tipe', 'wilayah_id', 'cabang_id',
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
            ->logOnly(['name', 'npp', 'email', 'wilayah_id', 'cabang_id', 'unit_kerja_id', 'jabatan', 'is_active', 'alamat'])
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

    /** Admin mengelola pegawai di seluruh penempatan; yang lain tidak. */
    public function bisaKelolaSemuaBidang(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Bidang yang boleh dikelola akun ini, mengikuti penempatannya.
     *
     * Akun kantor cabang hanya menjangkau bidang di kantornya sendiri, dan
     * akun Kedeputian Wilayah hanya bidang tingkat wilayah. Tanpa batas ini,
     * satu akun cabang bisa mendaftarkan pegawai di cabang lain.
     *
     * @return array<int, int> id unit kerja
     */
    public function bidangTerkelola(): array
    {
        $q = UnitKerja::query();

        if ($this->bisaKelolaSemuaBidang()) {
            return $q->pluck('id')->all();
        }

        if ($this->cabang_id) {
            return $q->where('cabang_id', $this->cabang_id)->pluck('id')->all();
        }

        if ($this->wilayah_id) {
            return $q->where('tingkat', 'wilayah')->where('wilayah_id', $this->wilayah_id)->pluck('id')->all();
        }

        return [];
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
