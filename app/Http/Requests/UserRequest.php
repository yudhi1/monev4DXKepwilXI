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
            // Diisi untuk pegawai perorangan; akun institusi lama dibiarkan kosong.
            'unit_kerja_id' => ['nullable', 'exists:unit_kerjas,id'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            // Penanda pimpinan: boleh melihat seluruh project modul PM.
            'lihat_semua_project' => ['boolean'],
            'is_active' => ['boolean'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama user',
            'wilayah_id' => 'wilayah',
            'cabang_id' => 'cabang',
            'unit_kerja_id' => 'unit kerja',
        ];
    }
}
