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
