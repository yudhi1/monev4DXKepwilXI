<?php

namespace App\Models\Pm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizSoal extends Model
{
    protected $table = 'pm_quiz_soals';

    protected $fillable = ['quiz_id', 'pertanyaan', 'pembahasan', 'poin', 'urutan'];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    public function opsis(): HasMany
    {
        return $this->hasMany(QuizOpsi::class, 'soal_id')->orderBy('urutan');
    }

    public function opsiBenar(): ?QuizOpsi
    {
        return $this->opsis->firstWhere('benar', true);
    }
}
