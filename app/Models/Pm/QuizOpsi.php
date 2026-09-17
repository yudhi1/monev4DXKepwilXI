<?php

namespace App\Models\Pm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizOpsi extends Model
{
    protected $table = 'pm_quiz_opsis';

    protected $fillable = ['soal_id', 'teks', 'benar', 'urutan'];

    protected $casts = ['benar' => 'boolean'];

    public function soal(): BelongsTo
    {
        return $this->belongsTo(QuizSoal::class, 'soal_id');
    }
}
