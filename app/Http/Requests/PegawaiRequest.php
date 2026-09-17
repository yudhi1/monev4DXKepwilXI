<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PegawaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('akses-master') ?? false;
    }

    public function rules(): array
    {
        $pegawai = $this->route('pegawai');

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('users', 'name')->ignore($pegawai?->id),
            ],
            /*
             | NPP adalah identitas login pegawai ke modul Project Management,
             | jadi wajib dan tidak boleh kembar.
             */
            'npp' => [
                'required', 'string', 'max:20',
                Rule::unique('users', 'npp')->ignore($pegawai?->id),
            ],

            // Saat edit, password kosong berarti "jangan diubah".
            'password' => [$pegawai ? 'nullable' : 'required', 'min:6'],

            /*
             | Bidang dibatasi pada penempatan si pengelola. Memeriksanya di
             | sini, bukan hanya menyembunyikan pilihannya di layar: tanpa itu,
             | mengirim id bidang cabang lain lewat request sudah cukup untuk
             | menembus batas.
             */
            'unit_kerja_id' => [
                'required',
                Rule::in($this->user()->bidangTerkelola()),
            ],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'pm_role' => ['required', Rule::in(array_keys(config('pm.role_akun')))],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'unit_kerja_id.in' => 'Anda hanya dapat mengelola pegawai pada bidang di penempatan Anda.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama pegawai',
            'npp' => 'NPP',
            'unit_kerja_id' => 'unit kerja',
            'pm_role' => 'role',
        ];
    }
}
