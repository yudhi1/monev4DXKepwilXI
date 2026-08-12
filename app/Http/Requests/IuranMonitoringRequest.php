<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IuranMonitoringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'kedeputian_wilayah', 'kantor_cabang']) ?? false;
    }

    public function rules(): array
    {
        return [
            'cabang_id' => ['required', 'exists:cabangs,id'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'minggu' => ['required', 'integer', 'min:1', 'max:5'],
            'nama_pemda' => ['required', 'string', 'max:255'],
            'tagihan' => ['required', 'numeric', 'min:0'],
            'status_bayar' => ['required', Rule::in(['sudah', 'sebagian', 'belum'])],
            'outstanding' => ['required', 'numeric', 'min:0'],
            'pic' => ['nullable', 'string', 'max:255'],
            'kendala' => ['nullable', 'string'],
            'keterangan' => ['nullable', 'string'],
            'target_penyelesaian' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'cabang_id' => 'cabang',
            'nama_pemda' => 'nama pemda',
            'status_bayar' => 'status bayar',
            'target_penyelesaian' => 'target penyelesaian',
        ];
    }
}
