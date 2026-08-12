<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadMeasureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'kedeputian_wilayah', 'kantor_cabang']) ?? false;
    }

    public function rules(): array
    {
        return [
            'wig_id' => ['required', 'exists:wigs,id'],
            'cabang_id' => ['required', 'exists:cabangs,id'],
            'lag_measure_id' => ['required', 'exists:lag_measures,id'],
            'kode_lead' => [
                'required',
                'max:50',
                Rule::unique('lead_measures', 'kode_lead')->ignore($this->route('lead_measure')?->id),
            ],
            'nama_lead' => ['required', 'string', 'max:2000'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'wig_id' => 'WIG',
            'cabang_id' => 'cabang',
            'lag_measure_id' => 'lag measure',
            'kode_lead' => 'kode lead',
            'nama_lead' => 'nama lead',
        ];
    }
}
