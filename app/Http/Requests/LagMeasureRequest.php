<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LagMeasureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'kedeputian_wilayah']) ?? false;
    }

    public function rules(): array
    {
        return [
            'wig_id' => ['required', 'exists:wigs,id'],
            'cabang_id' => ['required', 'exists:cabangs,id'],
            'kode_lag' => [
                'required',
                'max:50',
                Rule::unique('lag_measures', 'kode_lag')->ignore($this->route('lag_measure')?->id),
            ],
            'nama_lag' => ['required', 'string', 'max:2000'],
            'tanggal_target' => ['nullable', 'date'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'wig_id' => 'WIG',
            'cabang_id' => 'cabang',
            'kode_lag' => 'kode lag',
            'nama_lag' => 'nama lag',
        ];
    }
}
