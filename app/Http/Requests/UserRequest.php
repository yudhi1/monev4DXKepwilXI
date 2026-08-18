<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'name')->ignore($user?->id),
            ],
            // Saat edit, password kosong berarti "jangan diubah".
            'password' => [$user ? 'nullable' : 'required', 'min:6'],
            'role' => ['required', Rule::in(['admin', 'kedeputian_wilayah', 'kantor_cabang'])],
            'wilayah_id' => ['nullable', 'exists:wilayahs,id'],
            'cabang_id' => ['nullable', 'exists:cabangs,id'],
            'is_active' => ['boolean'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama unit kerja',
            'wilayah_id' => 'wilayah',
            'cabang_id' => 'cabang',
        ];
    }
}
