<?php

namespace App\Policies\Pm;

use App\Models\Pm\Quiz;
use App\Models\User;

/**
 * Siapa boleh apa atas sebuah quiz.
 *
 * Membuat dan menyunting quiz adalah kewenangan Project Manager (permission
 * `pm.quiz.kelola`, diturunkan dari pm_role lewat User::selaraskanIzinPm()).
 * Mengerjakan quiz terbuka bagi siapa pun yang boleh masuk modul PM —
 * termasuk Project Manager itu sendiri.
 */
class QuizPolicy
{
    /** Membuat quiz baru. */
    public function create(User $user): bool
    {
        return $user->can('pm.quiz.kelola');
    }

    /**
     * Menyunting quiz: pembuatnya sendiri, atau admin.
     *
     * Sesama Project Manager sengaja tidak saling bisa mengubah quiz — soal
     * dan kunci jawaban milik penyusunnya.
     */
    public function update(User $user, Quiz $quiz): bool
    {
        return $user->can('pm.kelola')
            || ($user->can('pm.quiz.kelola') && $quiz->dibuat_oleh === $user->id);
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->update($user, $quiz);
    }

    /** Melihat rekap percobaan seluruh peserta, bukan hanya milik sendiri. */
    public function lihatRekap(User $user, Quiz $quiz): bool
    {
        return $this->update($user, $quiz) || $user->can('pm.lihat-semua');
    }

    /**
     * Mengerjakan quiz.
     *
     * Tiga syarat sekaligus: quiz sudah terbit, orangnya boleh masuk modul PM,
     * dan ia termasuk sasaran quiz itu. Pengelolanya dikecualikan — ia perlu
     * bisa mencoba quiz buatannya sendiri untuk memeriksa soal, termasuk saat
     * quiz masih draf dan belum boleh dilihat peserta.
     */
    public function kerjakan(User $user, Quiz $quiz): bool
    {
        if ($this->update($user, $quiz)) {
            return $quiz->status !== 'ditutup';
        }

        return $quiz->status === 'terbit'
            && $user->can('akses-pm')
            && $quiz->menyasar($user);
    }
}
