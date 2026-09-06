<?php

namespace App\Models\Pm;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    protected $table = 'pm_tasks';

    protected $fillable = [
        'project_id', 'milestone_id', 'judul', 'deskripsi', 'status',
        'prioritas', 'deadline', 'progress', 'bobot', 'satuan', 'target',
        'realisasi', 'urutan', 'dibuat_oleh',
    ];

    protected $casts = [
        'deadline' => 'date',
        'progress' => 'integer',
        'bobot' => 'decimal:2',
        'target' => 'decimal:2',
        'realisasi' => 'decimal:2',
    ];

    /** Task yang kemajuannya diukur angka, bukan ditaksir sendiri oleh PIC. */
    public function pakaiTarget(): bool
    {
        return $this->target !== null && (float) $this->target > 0;
    }

    /**
     * Progress yang seharusnya, dihitung dari realisasi terhadap target.
     *
     * Dipanggil saat menyimpan sehingga kolom `progress` tetap menjadi satu-
     * satunya sumber bagi perhitungan lain (progress project, kontribusi
     * anggota) — semuanya tidak perlu tahu soal target.
     */
    public function progressDariTarget(): int
    {
        if (! $this->pakaiTarget()) {
            return $this->progress;
        }

        $persen = (float) $this->realisasi / (float) $this->target * 100;

        return (int) round(max(0, min(100, $persen)));
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class, 'milestone_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pm_task_assignees', 'task_id', 'user_id')
            ->withTimestamps();
    }

    public function selesai(): bool
    {
        return $this->status === config('pm.status_selesai', 'done');
    }

    /**
     * Selisih hari tenggat terhadap hari ini; negatif berarti sudah lewat.
     *
     * Dihitung dari awal hari supaya "besok" selalu 1, tidak bergeser oleh jam
     * berapa halaman dibuka.
     */
    public function sisaHari(): ?int
    {
        if ($this->deadline === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->deadline->startOfDay(), false);
    }

    /**
     * Tenggat dalam kata-kata: "3 hari lagi", "Terlambat 5 hari".
     *
     * Ditaruh di model, bukan di layar atau di export, agar tabel dan berkas
     * unduhan tidak bisa berbeda kalimat untuk task yang sama.
     */
    public function keteranganTenggat(): string
    {
        if ($this->selesai()) {
            return 'Selesai';
        }

        if ($this->deadline === null) {
            return 'Tanpa tenggat';
        }

        $sisa = $this->sisaHari();

        if ($sisa < 0) {
            return 'Terlambat '.abs($sisa).' hari';
        }

        return match ($sisa) {
            0 => 'Jatuh tempo hari ini',
            1 => 'Besok',
            default => "{$sisa} hari lagi",
        };
    }

    /**
     * Ditandai selesai, tetapi realisasinya belum menutup target.
     *
     * Task bertarget yang digeser ke Done sengaja tidak dipaksa 100% supaya
     * angka realisasi yang sudah diisi tidak tertimpa. Akibatnya kolom Kanban
     * bisa berkata "selesai" sementara progress project tetap rendah — tanpa
     * penanda, selisih itu terbaca seperti salah hitung.
     */
    public function realisasiTertinggal(): bool
    {
        return $this->selesai() && $this->pakaiTarget() && $this->progress < 100;
    }

    /** Lewat deadline dan belum selesai. */
    public function terlambat(): bool
    {
        return $this->deadline !== null
            && ! $this->selesai()
            && $this->deadline->endOfDay()->isPast();
    }

    public function scopeTerlambat(Builder $query): Builder
    {
        return $query->whereNotNull('deadline')
            ->whereDate('deadline', '<', now()->toDateString())
            ->where('status', '!=', config('pm.status_selesai', 'done'));
    }

    public function scopeBelumSelesai(Builder $query): Builder
    {
        return $query->where('status', '!=', config('pm.status_selesai', 'done'));
    }

    /** Task yang di-assign ke user tertentu. */
    public function scopeMilik(Builder $query, User $user): Builder
    {
        return $query->whereHas('assignees', fn (Builder $q) => $q->where('users.id', $user->id));
    }
}
