<?php

namespace App\Models\Pm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizPercobaan extends Model
{
    protected $table = 'pm_quiz_percobaans';

    protected $fillable = [
        'quiz_id', 'user_id', 'status', 'mulai_pada', 'selesai_pada', 'batas_pada',
        'urutan_soal', 'urutan_opsi', 'jumlah_soal', 'jumlah_benar',
        'poin_didapat', 'poin_maksimal', 'skor', 'durasi_detik',
    ];

    protected $casts = [
        'mulai_pada' => 'datetime',
        'selesai_pada' => 'datetime',
        'batas_pada' => 'datetime',
        'urutan_soal' => 'array',
        'urutan_opsi' => 'array',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jawabans(): HasMany
    {
        return $this->hasMany(QuizJawaban::class, 'percobaan_id');
    }

    public function berjalan(): bool
    {
        return $this->status === 'berjalan';
    }

    /**
     * Sisa waktu dalam detik; null bila quiz tanpa timer.
     *
     * Selalu dihitung dari jam server. Jam browser peserta hanya dipakai
     * untuk menggerakkan tampilan hitung mundur, tidak untuk menentukan
     * apakah waktunya sudah habis.
     */
    public function sisaDetik(): ?int
    {
        if (! $this->batas_pada) {
            return null;
        }

        // floor(), bukan cast langsung: diffInSeconds() mengembalikan float
        // dan pembulatan ke atas akan menampilkan satu detik lebih banyak
        // daripada yang sebenarnya diterima server.
        return (int) max(0, floor(now()->diffInSeconds($this->batas_pada, false)));
    }

    public function waktuHabis(): bool
    {
        return $this->batas_pada !== null && now()->greaterThanOrEqualTo($this->batas_pada);
    }
}
