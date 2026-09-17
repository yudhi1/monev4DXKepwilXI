<?php

namespace App\Models\Kinerja;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Berkas capaian kinerja sebuah indikator pada bulan tertentu.
 *
 * Disebut FileCapaian, bukan File, supaya tidak bertabrakan dengan
 * Illuminate\Http\File saat keduanya dipakai di berkas yang sama.
 */
class FileCapaian extends Model
{
    protected $table = 'kinerja_files';

    protected $fillable = [
        'kinerja_indikator_id', 'nama', 'file_path', 'nama_asli',
        'mime', 'ukuran', 'keterangan', 'bulan', 'tahun', 'diunggah_oleh',
    ];

    protected $casts = [
        'bulan' => 'integer',
        'tahun' => 'integer',
        'ukuran' => 'integer',
    ];

    public function indikator(): BelongsTo
    {
        return $this->belongsTo(Indikator::class, 'kinerja_indikator_id');
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diunggah_oleh');
    }

    /** Ukuran berkas dalam satuan yang enak dibaca, mis. "1,4 MB". */
    public function getUkuranTerbacaAttribute(): string
    {
        if ($this->ukuran < 1024) {
            return "{$this->ukuran} B";
        }

        if ($this->ukuran < 1048576) {
            return number_format($this->ukuran / 1024, 0, ',', '.').' KB';
        }

        return number_format($this->ukuran / 1048576, 1, ',', '.').' MB';
    }
}
