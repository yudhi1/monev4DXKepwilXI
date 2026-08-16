<?php

namespace App\Models\Pm;

use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $table = 'pm_projects';

    protected $fillable = [
        'kode', 'nama', 'deskripsi', 'status', 'prioritas',
        'tanggal_mulai', 'tanggal_selesai', 'pemilik_id', 'unit_kerja_id',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function pemilik(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pemilik_id');
    }

    /** Bidang pemilik project — boleh berbeda dari bidang para anggotanya. */
    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'unit_kerja_id');
    }

    public function anggotas(): HasMany
    {
        return $this->hasMany(ProjectMember::class, 'project_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pm_project_members', 'project_id', 'user_id')
            ->withPivot('peran')
            ->withTimestamps();
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class, 'project_id')->orderBy('urutan');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    /**
     * Batasi ke project yang boleh dilihat user.
     *
     * Pemegang `pm.lihat-semua` (admin & pimpinan) melihat semua. Selain itu:
     * project yang dia miliki, yang dia ikuti sebagai anggota, atau yang
     * dimiliki unit kerjanya sendiri — sehingga rekan satu bidang tetap dapat
     * memantau pekerjaan bidangnya tanpa harus didaftarkan satu per satu.
     */
    public function scopeBisaDilihat(Builder $query, User $user): Builder
    {
        if ($user->can('pm.lihat-semua')) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('pemilik_id', $user->id)
                ->orWhereHas('anggotas', fn (Builder $a) => $a->where('user_id', $user->id));

            if ($user->unit_kerja_id) {
                $q->orWhere('unit_kerja_id', $user->unit_kerja_id);
            }
        });
    }

    /** Peran user di project ini, atau null kalau bukan anggota. */
    public function peranUser(User $user): ?string
    {
        if ($this->pemilik_id === $user->id) {
            return 'manager';
        }

        return $this->anggotas->firstWhere('user_id', $user->id)?->peran;
    }

    /**
     * Progress project: rata-rata progress task yang ditimbang bobotnya.
     * Kalau seluruh bobot nol, jatuh ke rata-rata biasa agar tidak bagi nol.
     */
    public function progress(): int
    {
        $tasks = $this->relationLoaded('tasks') ? $this->tasks : $this->tasks()->get();

        if ($tasks->isEmpty()) {
            return 0;
        }

        $totalBobot = (float) $tasks->sum('bobot');

        if ($totalBobot <= 0) {
            return (int) round($tasks->avg('progress'));
        }

        $terbobot = $tasks->sum(fn (Task $t) => (float) $t->bobot * $t->progress);

        return (int) round($terbobot / $totalBobot);
    }

    /**
     * Kesehatan project: on_track / at_risk / critical.
     *
     * Dibandingkan terhadap ekspektasi jadwal — proporsi waktu yang sudah
     * berlalu antara tanggal mulai dan selesai.
     */
    public function health(): string
    {
        $progress = $this->progress();

        if ($this->status === 'selesai') {
            return 'on_track';
        }

        $adaOverdue = ($this->relationLoaded('tasks') ? $this->tasks : $this->tasks()->get())
            ->contains(fn (Task $t) => $t->terlambat());

        // Tanpa rentang tanggal, satu-satunya sinyal adalah task yang lewat deadline.
        if (! $this->tanggal_mulai || ! $this->tanggal_selesai) {
            return $adaOverdue ? 'at_risk' : 'on_track';
        }

        $mulai = $this->tanggal_mulai->startOfDay();
        $selesai = $this->tanggal_selesai->endOfDay();
        $sekarang = now();

        if ($sekarang->greaterThan($selesai)) {
            return $progress >= 100 ? 'on_track' : 'critical';
        }

        $totalHari = max(1, $mulai->diffInDays($selesai));
        $berlalu = max(0, $mulai->diffInDays($sekarang));
        $ekspektasi = min(100, $berlalu / $totalHari * 100);

        $tertinggal = $ekspektasi - $progress;

        if ($tertinggal >= config('pm.health.ambang_kritis', 15)) {
            return 'critical';
        }

        if ($tertinggal > 0 || $adaOverdue) {
            return 'at_risk';
        }

        return 'on_track';
    }
}
