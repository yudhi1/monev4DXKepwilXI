<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonevApcUpload extends Model
{
    protected $table = 'monev_apc_uploads';

    protected $fillable = [
        'indikator', 'tahun', 'original_name', 'file_path',
        'sheet_json', 'rows_count', 'uploaded_by',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'sheet_json' => 'array',
        'rows_count' => 'integer',
    ];

    /** Daftar indikator APC (slug => label) */
    public const INDIKATOR = [
        'total' => 'Capaian Total APC',
        'peserta-aktif' => 'APC Peserta Aktif',
        'kepuasan' => 'APC Tingkat Kepuasan Peserta',
        'penerimaan-iuran' => 'APC Jumlah Penerimaan Iuran',
        'biaya-manfaat' => 'APC Realisasi Biaya Manfaat',
        'biaya-operasional' => 'APC Biaya Operasional',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
