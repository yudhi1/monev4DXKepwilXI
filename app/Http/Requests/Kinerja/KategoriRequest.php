<?php

namespace App\Http\Requests\Kinerja;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'kedeputian_wilayah']) ?? false;
    }

    public function rules(): array
    {
        return [
            'nama' => [
                'required', 'string', 'max:150',
                Rule::unique('kinerja_kategoris', 'nama')->ignore($this->route('kategori')?->id),
            ],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['nama' => 'nama kategori'];
    }

    public function messages(): array
    {
        return ['nama.unique' => 'Kategori dengan nama ini sudah ada.'];
    }
}
