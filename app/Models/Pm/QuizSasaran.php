<?php

namespace App\Models\Pm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris sasaran quiz.
 *
 * Arti `sasaran_id` ditentukan `pm_quizzes.sasaran_tipe`: id cabang, id unit
 * kerja, atau id pegawai.
 */
class QuizSasaran extends Model
{
    protected $table = 'pm_quiz_sasarans';

    protected $fillable = ['quiz_id', 'sasaran_id'];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }
}
