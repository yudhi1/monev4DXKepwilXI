<?php

namespace App\Http\Requests\Pm;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Milestone sengaja longgar: hanya namanya yang wajib.
 *
 * Tahapan biasanya dicatat lebih dulu sebagai nama saja, tanggalnya menyusul
 * setelah jadwal disepakati. Mewajibkan target tanggal di sini akan memaksa
 * PM mengarang tanggal hanya agar formnya lolos.
 */
class MilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Kewenangan diperiksa controller lewat ProjectPolicy.
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'target_tanggal' => ['nullable', 'date'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama' => 'nama milestone',
            'target_tanggal' => 'target tanggal',
        ];
    }
}
