<?php

namespace App\Http\Requests\Pm;

use Illuminate\Foundation\Http\FormRequest;

class QuizSoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Kewenangan diperiksa controller lewat QuizPolicy.
    }

    public function rules(): array
    {
        return [
            'pertanyaan' => ['required', 'string', 'max:2000'],
            'pembahasan' => ['nullable', 'string', 'max:2000'],
            'poin' => ['nullable', 'integer', 'min:1', 'max:100'],

            // Minimal dua pilihan — satu pilihan bukan soal, itu pernyataan.
            'opsis' => ['required', 'array', 'min:2', 'max:6'],
            'opsis.*.teks' => ['required', 'string', 'max:500'],

            /*
             | Kunci jawaban dikirim sebagai indeks pada array opsi, bukan
             | sebagai flag per opsi: bentuk ini membuat "tepat satu jawaban
             | benar" mustahil dilanggar dari sisi request.
             */
            'kunci' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $opsis = $this->input('opsis', []);
            $kunci = $this->input('kunci');

            if (is_array($opsis) && ! array_key_exists((int) $kunci, array_values($opsis))) {
                $validator->errors()->add('kunci', 'Pilih salah satu opsi sebagai jawaban benar.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'opsis' => 'pilihan jawaban',
            'kunci' => 'jawaban benar',
        ];
    }
}
