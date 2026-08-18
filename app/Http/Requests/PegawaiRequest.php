<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PegawaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        $pegawai = $this->route('pegawai');

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('users', 'name')->ignore($pegawai?->id),
            ],
            // Saat edit, password kosong berarti "jangan diubah".
            'password' => [$pegawai ? 'nullable' : 'required', 'min:6'],

            // Bidang menentukan sekaligus tingkat dan kantor induk pegawai.
            'unit_kerja_id' => ['required', 'exists:unit_kerjas,id'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'pm_role' => ['required', Rule::in(array_keys(config('pm.role_akun')))],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama pegawai',
            'unit_kerja_id' => 'unit kerja',
            'pm_role' => 'role',
        ];
    }
}
