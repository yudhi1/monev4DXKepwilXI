<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitKerjaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        $unit = $this->route('unit_kerja');

        return [
            /*
             | Kode unik per kantor, bukan unik global: "PMU" ada di setiap
             | kantor cabang, dan SDMUK di wilayah berdampingan dengan SDMU
             | di cabang.
             */
            'kode' => [
                'required', 'string', 'max:20',
                Rule::unique('unit_kerjas', 'kode')
                    ->where('cabang_id', $this->input('tingkat') === 'cabang' ? $this->input('cabang_id') : null)
                    ->ignore($unit?->id),
            ],
            'nama' => ['required', 'string', 'max:100'],
            'tingkat' => ['required', Rule::in(['wilayah', 'cabang'])],

            // Bidang cabang wajib menyebut kantornya; bidang wilayah tidak.
            'cabang_id' => [
                Rule::requiredIf(fn () => $this->input('tingkat') === 'cabang'),
                'nullable',
                'exists:cabangs,id',
            ],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'kode' => 'kode bidang',
            'nama' => 'nama bidang',
            'cabang_id' => 'kantor cabang',
        ];
    }

    public function messages(): array
    {
        return [
            'kode.unique' => 'Kode bidang ini sudah dipakai di kantor yang sama.',
            'cabang_id.required' => 'Bidang tingkat cabang harus menyebut kantor cabangnya.',
        ];
    }
}
