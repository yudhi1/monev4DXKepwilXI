<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CabangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'wilayah_id' => ['required', 'exists:wilayahs,id'],
            'kode' => [
                'required',
                'max:20',
                Rule::unique('cabangs', 'kode')->ignore($this->route('cabang')?->id),
            ],
            'nama' => ['required', 'max:100'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'wilayah_id' => 'wilayah',
            'kode' => 'kode cabang',
            'nama' => 'nama cabang',
        ];
    }
}
