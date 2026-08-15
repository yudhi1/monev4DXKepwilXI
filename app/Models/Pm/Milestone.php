<?php

namespace App\Models\Pm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Milestone extends Model
{
    protected $table = 'pm_milestones';

    protected $fillable = ['project_id', 'nama', 'deskripsi', 'target_tanggal', 'urutan'];

    protected $casts = [
        'target_tanggal' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'milestone_id');
    }
}
