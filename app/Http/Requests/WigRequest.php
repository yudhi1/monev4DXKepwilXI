<?php

namespace App\Http\Requests;

use App\Models\Wig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'kedeputian_wilayah']) ?? false;
    }

    public function rules(): array
    {
        return [
            'kode_wig' => [
                'required',
                'max:30',
                Rule::unique('wigs', 'kode_wig')->ignore($this->route('wig')?->id),
            ],
            'nama_wig' => ['required', 'max:150'],
            'indikator_output' => ['nullable', 'string'],
            'sifat_capaian' => ['required', Rule::in(Wig::daftarSifat())],
            'arah' => ['required', Rule::in(Wig::daftarArah())],
            'bidang' => ['nullable', Rule::in(Wig::BIDANG)],
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'wilayah_id' => ['nullable', 'exists:wilayahs,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'kode_wig' => 'kode WIG',
            'nama_wig' => 'nama WIG',
            'sifat_capaian' => 'sifat capaian',
        ];
    }
}
