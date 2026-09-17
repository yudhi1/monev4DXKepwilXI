<?php

namespace App\Http\Requests\Kinerja;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FileCapaianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'kedeputian_wilayah']) ?? false;
    }

    public function rules(): array
    {
        return [
            /*
             | Kategori ikut divalidasi walau yang disimpan hanya indikatornya:
             | formulir memilih kategori dulu, jadi kesalahan pilih harus
             | tampil di field kategori, bukan berubah jadi galat indikator.
             */
            'kinerja_kategori_id' => ['required', 'exists:kinerja_kategoris,id'],
            'kinerja_indikator_id' => [
                'required',
                Rule::exists('kinerja_indikators', 'id')
                    ->where('kinerja_kategori_id', $this->input('kinerja_kategori_id')),
            ],
            'nama' => ['required', 'string', 'max:150'],

            // Berkas wajib saat menambah, opsional saat mengubah keterangan saja.
            'file' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png',
                'max:2048',
            ],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'kinerja_kategori_id' => 'kategori capaian',
            'kinerja_indikator_id' => 'indikator',
            'nama' => 'nama file',
            'file' => 'file',
        ];
    }

    public function messages(): array
    {
        return [
            'file.max' => 'Ukuran file maksimal 2 MB.',
            'file.mimes' => 'Format file harus PDF, Word, Excel, PowerPoint, atau gambar (JPG/PNG).',
            'kinerja_indikator_id.exists' => 'Indikator yang dipilih bukan milik kategori tersebut.',
        ];
    }
}
