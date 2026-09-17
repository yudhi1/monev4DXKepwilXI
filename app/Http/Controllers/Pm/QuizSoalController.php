<?php

namespace App\Http\Controllers\Pm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pm\QuizSoalRequest;
use App\Models\Pm\Quiz;
use App\Models\Pm\QuizSoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Bank soal sebuah quiz.
 *
 * Soal dan opsinya selalu disimpan bersama dalam satu transaksi: opsi yang
 * tertinggal tanpa kunci jawaban akan membuat soal itu mustahil dijawab benar.
 */
class QuizSoalController extends Controller
{
    public function store(QuizSoalRequest $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        $data = $request->validated();

        DB::transaction(function () use ($quiz, $data) {
            $soal = $quiz->soals()->create([
                'pertanyaan' => $data['pertanyaan'],
                'pembahasan' => $data['pembahasan'] ?? null,
                'poin' => $data['poin'] ?? 1,
                'urutan' => (int) $quiz->soals()->max('urutan') + 1,
            ]);

            $this->simpanOpsi($soal, $data);
        });

        return back()->with('success', 'Soal berhasil ditambahkan.');
    }

    public function update(QuizSoalRequest $request, Quiz $quiz, QuizSoal $soal): RedirectResponse
    {
        $this->authorize('update', $quiz);
        abort_unless($soal->quiz_id === $quiz->id, 404);

        $data = $request->validated();

        DB::transaction(function () use ($soal, $data) {
            $soal->update([
                'pertanyaan' => $data['pertanyaan'],
                'pembahasan' => $data['pembahasan'] ?? null,
                'poin' => $data['poin'] ?? 1,
            ]);

            /*
             | Opsi ditulis ulang, bukan dicocokkan satu per satu. Jawaban
             | percobaan lama menunjuk opsi lewat FK nullOnDelete, jadi yang
             | hilang hanya rincian pilihannya — skor yang sudah tercatat
             | pada percobaan selesai tidak ikut berubah.
             */
            $soal->opsis()->delete();
            $this->simpanOpsi($soal, $data);
        });

        return back()->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroy(Quiz $quiz, QuizSoal $soal): RedirectResponse
    {
        $this->authorize('update', $quiz);
        abort_unless($soal->quiz_id === $quiz->id, 404);

        $soal->delete();

        return back()->with('success', 'Soal berhasil dihapus.');
    }

    /** @param  array<string, mixed>  $data */
    private function simpanOpsi(QuizSoal $soal, array $data): void
    {
        foreach (array_values($data['opsis']) as $i => $opsi) {
            $soal->opsis()->create([
                'teks' => $opsi['teks'],
                'benar' => $i === (int) $data['kunci'],
                'urutan' => $i + 1,
            ]);
        }
    }
}
