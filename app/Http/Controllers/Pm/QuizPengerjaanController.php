<?php

namespace App\Http\Controllers\Pm;

use App\Http\Controllers\Controller;
use App\Models\Pm\Quiz;
use App\Models\Pm\QuizOpsi;
use App\Models\Pm\QuizPercobaan;
use App\Models\Pm\QuizSoal;
use App\Support\Pm\PengerjaanQuiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Sisi peserta: memulai, menjawab, menutup, dan melihat hasil quiz.
 *
 * Kunci jawaban tidak pernah dikirim ke peserta selama pengerjaan — penilaian
 * seluruhnya di server. Pembahasan baru ikut pada halaman hasil, itu pun bila
 * pembuat quiz mengizinkannya.
 */
class QuizPengerjaanController extends Controller
{
    /** Memulai percobaan, atau melanjutkan yang masih berjalan. */
    public function mulai(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('kerjakan', $quiz);

        $user = $request->user();

        $berjalan = $quiz->percobaans()
            ->where('user_id', $user->id)
            ->where('status', 'berjalan')
            ->latest()
            ->first();

        if ($berjalan) {
            // Yang kedaluwarsa ditutup dulu supaya tidak dilanjutkan diam-diam.
            $berjalan = PengerjaanQuiz::tutupBilaHabis($berjalan);

            if ($berjalan->berjalan()) {
                return redirect("/pm/quiz/percobaan/{$berjalan->id}");
            }
        }

        if (! $quiz->siapTerbit()) {
            return back()->with('error', 'Quiz ini belum punya soal.');
        }

        $terpakai = $quiz->percobaans()
            ->where('user_id', $user->id)
            ->where('status', 'selesai')
            ->count();

        if ($quiz->maks_percobaan && $terpakai >= $quiz->maks_percobaan) {
            return back()->with('error', "Anda sudah memakai seluruh {$quiz->maks_percobaan} kesempatan untuk quiz ini.");
        }

        $percobaan = PengerjaanQuiz::mulai($quiz, $user);

        return redirect("/pm/quiz/percobaan/{$percobaan->id}");
    }

    /** Lembar pengerjaan. */
    public function kerjakan(Request $request, QuizPercobaan $percobaan): Response|RedirectResponse
    {
        abort_unless($percobaan->user_id === $request->user()->id, 403);

        $percobaan = PengerjaanQuiz::tutupBilaHabis($percobaan);

        if (! $percobaan->berjalan()) {
            return redirect("/pm/quiz/percobaan/{$percobaan->id}/hasil");
        }

        $quiz = $percobaan->quiz;
        $soals = QuizSoal::with('opsis')->whereIn('id', $percobaan->urutan_soal)->get()->keyBy('id');
        $jawaban = $percobaan->jawabans->pluck('opsi_id', 'soal_id');

        // Soal disusun ulang mengikuti urutan acak yang dibekukan saat mulai.
        $daftar = collect($percobaan->urutan_soal)
            ->map(function ($soalId, $i) use ($soals, $percobaan, $jawaban) {
                $soal = $soals->get($soalId);

                if (! $soal) {
                    return null;
                }

                $urutanOpsi = $percobaan->urutan_opsi[$soalId] ?? $soal->opsis->pluck('id')->all();

                return [
                    'nomor' => $i + 1,
                    'id' => $soal->id,
                    'pertanyaan' => $soal->pertanyaan,
                    'poin' => $soal->poin,
                    'dijawab' => $jawaban[$soal->id] ?? null,
                    // `benar` sengaja tidak ikut: kunci jawaban tidak boleh sampai ke browser.
                    'opsis' => collect($urutanOpsi)
                        ->map(fn ($id) => $soal->opsis->firstWhere('id', $id))
                        ->filter()
                        ->map(fn ($o) => ['id' => $o->id, 'teks' => $o->teks])
                        ->values(),
                ];
            })
            ->filter()
            ->values();

        return Inertia::render('Pm/Quiz/Kerjakan', [
            'percobaan' => [
                'id' => $percobaan->id,
                'quiz_id' => $quiz->id,
                'judul' => $quiz->judul,
                'jumlah_soal' => $daftar->count(),
                'terjawab' => $jawaban->filter(fn ($v) => $v !== null)->count(),
                // Sisa waktu datang dari server; browser hanya menghitung mundur dari angka ini.
                'sisa_detik' => $percobaan->sisaDetik(),
                'durasi_menit' => $quiz->durasi_menit,
            ],
            'soals' => $daftar,
        ]);
    }

    /**
     * Menyimpan satu jawaban.
     *
     * Disimpan per soal begitu dipilih, bukan sekaligus di akhir: peserta yang
     * kehabisan waktu atau kehilangan koneksi tetap membawa jawaban yang sudah
     * terisi, dan progresnya bisa dilanjutkan dari perangkat lain.
     */
    public function jawab(Request $request, QuizPercobaan $percobaan): RedirectResponse
    {
        abort_unless($percobaan->user_id === $request->user()->id, 403);

        $percobaan = PengerjaanQuiz::tutupBilaHabis($percobaan);

        if (! $percobaan->berjalan()) {
            return redirect("/pm/quiz/percobaan/{$percobaan->id}/hasil")
                ->with('error', 'Waktu pengerjaan sudah habis.');
        }

        $data = $request->validate([
            'soal_id' => ['required', 'integer'],
            'opsi_id' => ['nullable', 'integer'],
        ]);

        // Soal di luar lembar percobaan ini ditolak, termasuk milik quiz lain.
        abort_unless(in_array((int) $data['soal_id'], $percobaan->urutan_soal, true), 422);

        $opsi = $data['opsi_id']
            ? QuizOpsi::where('soal_id', $data['soal_id'])->find($data['opsi_id'])
            : null;

        abort_if($data['opsi_id'] && ! $opsi, 422);

        $percobaan->jawabans()->updateOrCreate(
            ['soal_id' => $data['soal_id']],
            ['opsi_id' => $opsi?->id, 'benar' => (bool) $opsi?->benar],
        );

        return back();
    }

    /**
     * Menutup percobaan atas permintaan peserta.
     *
     * Seluruh soal harus terjawab dulu. Penjaga ini ada di server, bukan
     * hanya berupa tombol yang dimatikan: menutup lembar yang masih kosong
     * berarti soal-soal itu dihitung salah, dan itu bukan sesuatu yang layak
     * terjadi karena salah pencet.
     *
     * Habis waktu adalah satu-satunya pengecualian — penutupannya sudah
     * dikerjakan tutupBilaHabis() di bawah, sebelum penjaga ini diperiksa.
     */
    public function selesai(Request $request, QuizPercobaan $percobaan): RedirectResponse
    {
        abort_unless($percobaan->user_id === $request->user()->id, 403);

        $percobaan = PengerjaanQuiz::tutupBilaHabis($percobaan);

        if ($percobaan->berjalan()) {
            $terjawab = $percobaan->jawabans()->whereNotNull('opsi_id')->count();
            $kurang = $percobaan->jumlah_soal - $terjawab;

            if ($kurang > 0) {
                return back()->with('error', "Masih ada {$kurang} soal yang belum dijawab. "
                    .'Lengkapi dulu sebelum menyelesaikan quiz.');
            }

            PengerjaanQuiz::selesaikan($percobaan);
        }

        return redirect("/pm/quiz/percobaan/{$percobaan->id}/hasil");
    }

    /** Hasil satu percobaan: skor, rincian jawaban, dan posisi peringkat. */
    public function hasil(Request $request, QuizPercobaan $percobaan): Response|RedirectResponse
    {
        $user = $request->user();
        abort_unless($percobaan->user_id === $user->id || $user->can('lihatRekap', $percobaan->quiz), 403);

        $percobaan = PengerjaanQuiz::tutupBilaHabis($percobaan);

        if ($percobaan->berjalan()) {
            return redirect("/pm/quiz/percobaan/{$percobaan->id}");
        }

        $quiz = $percobaan->quiz;
        $soals = QuizSoal::with('opsis')->whereIn('id', $percobaan->urutan_soal)->get()->keyBy('id');
        $jawaban = $percobaan->jawabans->keyBy('soal_id');

        // Pembahasan dan kunci baru boleh tampil setelah percobaan ditutup.
        $bolehBahas = $quiz->tampilkan_pembahasan;

        $rincian = collect($percobaan->urutan_soal)
            ->map(function ($soalId, $i) use ($soals, $jawaban, $bolehBahas) {
                $soal = $soals->get($soalId);

                if (! $soal) {
                    return null;
                }

                $milik = $jawaban->get($soalId);

                return [
                    'nomor' => $i + 1,
                    'pertanyaan' => $soal->pertanyaan,
                    'poin' => $soal->poin,
                    'benar' => (bool) $milik?->benar,
                    'dijawab' => $milik?->opsi_id ? $soal->opsis->firstWhere('id', $milik->opsi_id)?->teks : null,
                    'kunci' => $bolehBahas ? $soal->opsiBenar()?->teks : null,
                    'pembahasan' => $bolehBahas ? $soal->pembahasan : null,
                ];
            })
            ->filter()
            ->values();

        // Posisi peringkat dihitung dari percobaan terbaik tiap peserta.
        $terbaik = $quiz->percobaans()
            ->where('status', 'selesai')
            ->get()
            ->sortBy([['skor', 'desc'], ['durasi_detik', 'asc']])
            ->groupBy('user_id')
            ->map(fn ($m) => $m->first())
            ->sortBy([['skor', 'desc'], ['durasi_detik', 'asc']])
            ->values();

        return Inertia::render('Pm/Quiz/Hasil', [
            'hasil' => [
                'id' => $percobaan->id,
                'quiz_id' => $quiz->id,
                'judul' => $quiz->judul,
                'nama_peserta' => $percobaan->peserta?->name,
                'skor' => $percobaan->skor,
                'nilai_lulus' => $quiz->nilai_lulus,
                'lulus' => $percobaan->skor >= $quiz->nilai_lulus,
                'benar' => $percobaan->jumlah_benar,
                'jumlah_soal' => $percobaan->jumlah_soal,
                'poin_didapat' => $percobaan->poin_didapat,
                'poin_maksimal' => $percobaan->poin_maksimal,
                'durasi_detik' => $percobaan->durasi_detik,
                'selesai_pada' => $percobaan->selesai_pada?->toDateTimeString(),
                'posisi' => $terbaik->search(fn ($p) => $p->user_id === $percobaan->user_id),
                'jumlahPeserta' => $terbaik->count(),
                'adaPembahasan' => $bolehBahas,
                'sisaPercobaan' => $quiz->maks_percobaan
                    ? max(0, $quiz->maks_percobaan - $quiz->percobaans()
                        ->where('user_id', $percobaan->user_id)
                        ->where('status', 'selesai')
                        ->count())
                    : null,
            ],
            'rincian' => $rincian,
        ]);
    }
}
