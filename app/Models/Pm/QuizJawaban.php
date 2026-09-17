<?php

namespace App\Models\Pm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizJawaban extends Model
{
    protected $table = 'pm_quiz_jawabans';

    protected $fillable = ['percobaan_id', 'soal_id', 'opsi_id', 'benar'];

    protected $casts = ['benar' => 'boolean'];

    public function percobaan(): BelongsTo
    {
        return $this->belongsTo(QuizPercobaan::class, 'percobaan_id');
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(QuizSoal::class, 'soal_id');
    }

    public function opsi(): BelongsTo
    {
        return $this->belongsTo(QuizOpsi::class, 'opsi_id');
    }
}
