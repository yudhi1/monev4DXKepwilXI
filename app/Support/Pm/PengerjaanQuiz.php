<?php

namespace App\Support\Pm;

use App\Models\Pm\Quiz;
use App\Models\Pm\QuizPercobaan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Aturan pengerjaan quiz: memulai percobaan, dan menutupnya dengan skor.
 *
 * Dipisahkan dari controller karena dipanggil dari dua arah — peserta yang
 * menekan "Selesai", dan penutupan otomatis saat waktunya habis (yang bisa
 * terjadi pada permintaan mana pun, termasuk saat peserta hanya memuat ulang
 * halaman).
 */
class PengerjaanQuiz
{
    /**
     * Membuat percobaan baru sekaligus membekukan susunan soalnya.
     *
     * Urutan soal dan opsi diacak sekali di sini lalu disimpan. Kalau diacak
     * setiap kali halaman dibuka, peserta yang menyegarkan layar akan melihat
     * soal berpindah-pindah sementara jawaban yang sudah tersimpan menempel
     * pada soal lamanya.
     */
    public static function mulai(Quiz $quiz, User $peserta): QuizPercobaan
    {
        $soals = $quiz->soals()->with('opsis')->get();

        if ($quiz->acak_soal) {
            $soals = $soals->shuffle();
        }

        if ($quiz->jumlah_soal) {
            $soals = $soals->take($quiz->jumlah_soal);
        }

        $urutanOpsi = [];

        foreach ($soals as $soal) {
            $opsi = $quiz->acak_opsi ? $soal->opsis->shuffle() : $soal->opsis;
            $urutanOpsi[$soal->id] = $opsi->pluck('id')->all();
        }

        $mulai = now();

        return QuizPercobaan::create([
            'quiz_id' => $quiz->id,
            'user_id' => $peserta->id,
            'status' => 'berjalan',
            'mulai_pada' => $mulai,
            'batas_pada' => $quiz->durasi_menit ? $mulai->copy()->addMinutes($quiz->durasi_menit) : null,
            'urutan_soal' => $soals->pluck('id')->all(),
            'urutan_opsi' => $urutanOpsi,
            'jumlah_soal' => $soals->count(),
            'poin_maksimal' => (int) $soals->sum('poin'),
        ]);
    }

    /**
     * Menutup percobaan dan menghitung skornya.
     *
     * Soal yang tidak dijawab dihitung salah, bukan diabaikan — skor harus
     * mencerminkan seluruh soal yang keluar, kalau tidak peserta yang
     * menjawab satu soal benar lalu berhenti akan tercatat 100.
     */
    public static function selesaikan(QuizPercobaan $percobaan): QuizPercobaan
    {
        if (! $percobaan->berjalan()) {
            return $percobaan;
        }

        return DB::transaction(function () use ($percobaan) {
            $benar = $percobaan->jawabans()->where('benar', true)->pluck('soal_id');

            $poin = (int) $percobaan->quiz->soals()
                ->whereIn('id', $benar)
                ->sum('poin');

            $maksimal = max(1, (int) $percobaan->poin_maksimal);

            /*
             | Waktu berakhir dipatok pada batas timer bila peserta melewatinya
             | — misalnya menutup laptop lalu kembali sejam kemudian. Tanpa ini
             | durasinya tercatat satu jam dan peringkatnya jadi kacau.
             */
            $selesai = $percobaan->batas_pada && now()->greaterThan($percobaan->batas_pada)
                ? $percobaan->batas_pada
                : now();

            /*
             | Jangan sampai waktu selesai mendahului waktu mulai. Itu terjadi
             | kalau batas percobaan digeser ke masa lalu — dan durasi negatif
             | ditolak kolomnya yang unsigned, sehingga percobaan gagal ditutup
             | dan tersangkut sebagai "berjalan" selamanya.
             */
            if ($selesai->lessThan($percobaan->mulai_pada)) {
                $selesai = $percobaan->mulai_pada;
            }

            $percobaan->update([
                'status' => 'selesai',
                'selesai_pada' => $selesai,
                'jumlah_benar' => $benar->count(),
                'poin_didapat' => $poin,
                'skor' => (int) round($poin / $maksimal * 100),
                'durasi_detik' => (int) max(0, round($percobaan->mulai_pada->diffInSeconds($selesai))),
            ]);

            return $percobaan->refresh();
        });
    }

    /**
     * Menutup percobaan yang waktunya sudah lewat.
     *
     * Dipanggil di awal setiap aksi pengerjaan: tanpa penjaga ini, peserta
     * yang meninggalkan halaman terbuka akan menyimpan percobaan "berjalan"
     * selamanya dan bisa melanjutkannya kapan saja.
     */
    public static function tutupBilaHabis(QuizPercobaan $percobaan): QuizPercobaan
    {
        return $percobaan->berjalan() && $percobaan->waktuHabis()
            ? self::selesaikan($percobaan)
            : $percobaan;
    }
}
