<?php

namespace App\Http\Requests\Kinerja;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndikatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'kedeputian_wilayah']) ?? false;
    }

    public function rules(): array
    {
        return [
            'kinerja_kategori_id' => ['required', 'exists:kinerja_kategoris,id'],

            // Unik per kategori: indikator bernama sama boleh ada di kategori lain.
            'nama' => [
                'required', 'string', 'max:150',
                Rule::unique('kinerja_indikators', 'nama')
                    ->where('kinerja_kategori_id', $this->input('kinerja_kategori_id'))
                    ->ignore($this->route('indikator')?->id),
            ],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'kinerja_kategori_id' => 'kategori capaian',
            'nama' => 'nama indikator',
        ];
    }

    public function messages(): array
    {
        return ['nama.unique' => 'Indikator dengan nama ini sudah ada di kategori tersebut.'];
    }
}
