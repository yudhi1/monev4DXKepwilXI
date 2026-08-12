<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WilayahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'kode' => [
                'required',
                'max:20',
                Rule::unique('wilayahs', 'kode')->ignore($this->route('wilayah')?->id),
            ],
            'nama' => ['required', 'max:100'],
            'deskripsi' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'kode' => 'kode wilayah',
            'nama' => 'nama wilayah',
        ];
    }
}
