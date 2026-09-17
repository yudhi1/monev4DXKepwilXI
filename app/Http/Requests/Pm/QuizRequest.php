<?php

namespace App\Http\Requests\Pm;

use App\Support\Pm\CakupanSasaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Kewenangan diperiksa controller lewat QuizPolicy.
    }

    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:150'],

            /*
             | Sasaran peserta. `semua` tidak memerlukan daftar; tiga tipe
             | lainnya wajib menyebut minimal satu tujuan — quiz bersasaran
             | kosong tidak akan sampai ke siapa pun.
             */
            'sasaran_tipe' => ['required', Rule::in(CakupanSasaran::tipe($this->user()))],
            'sasaran' => ['array', 'required_unless:sasaran_tipe,semua', 'exclude_if:sasaran_tipe,semua'],
            'sasaran.*' => ['integer'],

            'deskripsi' => ['nullable', 'string', 'max:2000'],

            // Null berarti tanpa timer; 1 menit adalah batas bawah yang masuk akal.
            'durasi_menit' => ['nullable', 'integer', 'min:1', 'max:480'],

            'acak_soal' => ['boolean'],
            'acak_opsi' => ['boolean'],

            /*
             | Berapa soal yang keluar per percobaan. Boleh melebihi jumlah
             | soal yang ada sekarang — bank soal masih akan bertambah, dan
             | Quiz::jumlahSoalDipakai() sudah membatasinya saat dipakai.
             */
            'jumlah_soal' => ['nullable', 'integer', 'min:1', 'max:200'],

            'nilai_lulus' => ['required', 'integer', 'min:0', 'max:100'],
            'maks_percobaan' => ['nullable', 'integer', 'min:1', 'max:100'],
            'tampilkan_pembahasan' => ['boolean'],
        ];
    }

    /**
     * Tujuan yang dikirim harus berada dalam jangkauan pengirimnya.
     *
     * Diperiksa di sini, bukan cukup dengan menyembunyikan pilihannya di
     * layar: menyisipkan id cabang lain ke dalam request sudah cukup untuk
     * mengirim quiz ke kantor yang bukan urusannya.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tipe = $this->input('sasaran_tipe');

            if ($tipe === 'semua') {
                return;
            }

            $sah = CakupanSasaran::idSah($this->user(), $tipe);
            $dikirim = array_map('intval', (array) $this->input('sasaran', []));

            if (array_diff($dikirim, $sah) !== []) {
                $validator->errors()->add(
                    'sasaran',
                    'Ada tujuan di luar jangkauan Anda. Quiz hanya dapat ditujukan ke lingkup kantor sendiri.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'sasaran.required_unless' => 'Pilih minimal satu tujuan quiz.',
            'sasaran_tipe.in' => 'Jenis tujuan itu tidak tersedia untuk akun Anda.',
        ];
    }

    public function attributes(): array
    {
        return [
            'durasi_menit' => 'durasi',
            'jumlah_soal' => 'jumlah soal per percobaan',
            'nilai_lulus' => 'nilai lulus',
            'sasaran' => 'tujuan quiz',
            'sasaran_tipe' => 'jenis tujuan',
            'maks_percobaan' => 'maksimal percobaan',
        ];
    }
}
