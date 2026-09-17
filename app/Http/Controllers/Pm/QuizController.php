<?php

namespace App\Http\Controllers\Pm;

use App\Exports\Pm\NilaiQuizExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pm\QuizRequest;
use App\Models\Pm\Quiz;
use App\Models\Pm\QuizPercobaan;
use App\Models\User;
use App\Support\Pm\CakupanSasaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Daftar dan pengelolaan quiz modul Project Management.
 *
 * Membuat quiz adalah kewenangan Project Manager; mengerjakannya terbuka bagi
 * semua pengguna modul. Karena itu satu halaman daftar melayani dua sudut
 * pandang, dibedakan lewat prop `izin`.
 */
class QuizController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $quizzes = Quiz::query()
            ->withCount('soals')
            ->with(['pembuat:id,name', 'unitKerja:id,nama', 'sasarans'])
            /*
             | Penyaringan berlapis. Admin melihat semuanya; selain itu sebuah
             | quiz hanya muncul kalau dibuat orang ini sendiri, atau memang
             | ditujukan kepadanya. Tanpa lapisan sasaran, quiz khusus satu
             | cabang akan terpampang di layar seluruh pegawai.
             */
            ->when(
                ! $user->can('pm.kelola'),
                fn ($q) => $q->where(fn ($sub) => $sub
                    ->where('dibuat_oleh', $user->id)
                    ->orWhere(fn ($p) => $p
                        ->whereIn('status', ['terbit', 'ditutup'])
                        ->untukPeserta($user)))
            )
            ->latest()
            ->get();

        // Percobaan milik user sendiri, untuk menandai mana yang sudah dikerjakan.
        $percobaanSaya = QuizPercobaan::where('user_id', $user->id)
            ->whereIn('quiz_id', $quizzes->pluck('id'))
            ->get()
            ->groupBy('quiz_id');

        return Inertia::render('Pm/Quiz/Index', [
            'quizzes' => $quizzes->map(function (Quiz $q) use ($percobaanSaya, $user) {
                $milik = $percobaanSaya->get($q->id) ?? collect();
                $selesai = $milik->where('status', 'selesai');

                return [
                    'id' => $q->id,
                    'judul' => $q->judul,
                    'deskripsi' => $q->deskripsi,
                    'status' => $q->status,
                    'durasi_menit' => $q->durasi_menit,
                    'jumlah_soal' => $q->jumlahSoalDipakai(),
                    'bank_soal' => $q->soals_count,
                    'nilai_lulus' => $q->nilai_lulus,
                    'maks_percobaan' => $q->maks_percobaan,
                    'acak_soal' => $q->acak_soal,
                    'sasaran' => $q->labelSasaran(),
                    'pembuat' => $q->pembuat?->name,
                    'unitKerja' => $q->unitKerja?->nama,
                    'milikSaya' => $q->dibuat_oleh === $user->id,
                    // Ringkasan pengerjaan sendiri: dipakai kartu untuk memilih tombol.
                    'percobaanSaya' => $selesai->count(),
                    'skorTerbaik' => $selesai->max('skor'),
                    'sedangBerjalan' => $milik->firstWhere('status', 'berjalan')?->id,
                    'bisaDikerjakan' => $user->can('kerjakan', $q),
                    'bisaDikelola' => $user->can('update', $q),
                ];
            }),
            'izin' => [
                'buat' => $user->can('create', Quiz::class),
            ],
            // Daftar pegawai hanya dikirim kepada yang boleh membuat quiz.
            'pilihanSasaran' => $user->can('create', Quiz::class) ? CakupanSasaran::pilihan($user) : null,
            'tipeSasaran' => CakupanSasaran::tipe($user),
            'opsi' => ['status' => config('pm.status_quiz')],
        ]);
    }

    public function store(QuizRequest $request): RedirectResponse
    {
        $this->authorize('create', Quiz::class);

        $data = $request->validated();

        $quiz = Quiz::create([
            ...collect($data)->except('sasaran')->all(),
            'dibuat_oleh' => $request->user()->id,
            // Asal quiz mengikuti bidang pembuatnya, tidak dipilih manual.
            'unit_kerja_id' => $request->user()->unit_kerja_id,
            'status' => 'draf',
        ]);

        $this->simpanSasaran($quiz, $data);

        return redirect("/pm/quiz/{$quiz->id}/kelola")
            ->with('success', 'Quiz dibuat. Tambahkan soalnya sekarang.');
    }

    /** Halaman penyusunan soal — hanya untuk pengelola quiz. */
    public function kelola(Request $request, Quiz $quiz): Response
    {
        $this->authorize('update', $quiz);

        $quiz->load(['soals.opsis', 'pembuat:id,name', 'sasarans']);

        return Inertia::render('Pm/Quiz/Kelola', [
            'quiz' => [
                'id' => $quiz->id,
                'judul' => $quiz->judul,
                'deskripsi' => $quiz->deskripsi,
                'status' => $quiz->status,
                'durasi_menit' => $quiz->durasi_menit,
                'acak_soal' => $quiz->acak_soal,
                'acak_opsi' => $quiz->acak_opsi,
                'jumlah_soal' => $quiz->jumlah_soal,
                'nilai_lulus' => $quiz->nilai_lulus,
                'maks_percobaan' => $quiz->maks_percobaan,
                'tampilkan_pembahasan' => $quiz->tampilkan_pembahasan,
                'sasaran_tipe' => $quiz->sasaran_tipe,
                'sasaran' => $quiz->sasarans->pluck('sasaran_id'),
                'labelSasaran' => $quiz->labelSasaran(),
                // Berapa pegawai yang benar-benar terjangkau susunan sasaran ini.
                'jumlahPeserta' => $quiz->pesertaSasaran()->count(),
                'pembuat' => $quiz->pembuat?->name,
                'soalDipakai' => $quiz->jumlahSoalDipakai(),
                'siapTerbit' => $quiz->siapTerbit(),
                'soals' => $quiz->soals->map(fn ($s) => [
                    'id' => $s->id,
                    'pertanyaan' => $s->pertanyaan,
                    'pembahasan' => $s->pembahasan,
                    'poin' => $s->poin,
                    'urutan' => $s->urutan,
                    'opsis' => $s->opsis->map(fn ($o) => [
                        'id' => $o->id,
                        'teks' => $o->teks,
                        'benar' => $o->benar,
                    ]),
                ]),
            ],
            'jumlahPercobaan' => $quiz->percobaans()->count(),
            'pilihanSasaran' => CakupanSasaran::pilihan($request->user()),
            'tipeSasaran' => CakupanSasaran::tipe($request->user()),
            'opsi' => ['status' => config('pm.status_quiz')],
        ]);
    }

    public function update(QuizRequest $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        $data = $request->validated();

        $quiz->update(collect($data)->except('sasaran')->all());
        $this->simpanSasaran($quiz, $data);

        return back()->with('success', 'Pengaturan quiz diperbarui.');
    }

    /**
     * Menerbitkan atau menutup quiz.
     *
     * Quiz tanpa soal ditolak di sini, bukan hanya disembunyikan tombolnya:
     * peserta yang memulainya akan mendapat lembar kosong.
     */
    public function status(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(config('pm.status_quiz')))],
        ]);

        if ($data['status'] === 'terbit' && ! $quiz->siapTerbit()) {
            return back()->with('error', 'Quiz belum punya soal, jadi belum bisa diterbitkan.');
        }

        $quiz->update($data);

        return back()->with('success', 'Status quiz diubah menjadi '.config("pm.status_quiz.{$data['status']}.label").'.');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $this->authorize('delete', $quiz);

        // Soal, opsi, percobaan, dan jawaban ikut terhapus lewat cascade FK.
        $quiz->delete();

        return redirect('/pm/quiz')->with('success', 'Quiz berhasil dihapus.');
    }

    /**
     * Papan peringkat.
     *
     * Satu baris satu peserta: percobaan terbaiknya. Kalau semua percobaan
     * ditampilkan, peserta yang boleh mengulang akan memenuhi papan sendirian.
     */
    public function peringkat(Request $request, Quiz $quiz): Response
    {
        $user = $request->user();
        $this->authorize('kerjakan', $quiz);

        $quiz->load('sasarans');

        $percobaans = $this->peringkatTerbaik($quiz);

        return Inertia::render('Pm/Quiz/Peringkat', [
            'quiz' => [
                'id' => $quiz->id,
                'judul' => $quiz->judul,
                'status' => $quiz->status,
                'nilai_lulus' => $quiz->nilai_lulus,
            ],
            'peringkat' => $percobaans->map(fn (QuizPercobaan $p, $i) => [
                'posisi' => $i + 1,
                'user_id' => $p->user_id,
                'nama' => $p->peserta?->name,
                'unitKerja' => $p->peserta?->unitKerja?->nama,
                'skor' => $p->skor,
                'benar' => $p->jumlah_benar,
                'jumlah_soal' => $p->jumlah_soal,
                'durasi_detik' => $p->durasi_detik,
                'selesai_pada' => $p->selesai_pada?->toDateTimeString(),
                'saya' => $p->user_id === $user->id,
            ]),
            'jumlahPeserta' => $percobaans->count(),
            'rerata' => $percobaans->isEmpty() ? 0 : (int) round($percobaans->avg('skor')),
            /*
             | Yang belum mengerjakan dihitung dari pegawai yang DISASAR quiz
             | ini, bukan dari seluruh pegawai. Kalau tidak, quiz untuk satu
             | bidang akan selalu terlihat seperti diabaikan puluhan orang
             | yang memang tidak pernah ditugasi mengerjakannya.
             */
            'belumMengerjakan' => $user->can('lihatRekap', $quiz)
                ? $quiz->pesertaSasaran()->whereNotIn('id', $percobaans->pluck('user_id'))->count()
                : null,
            'sasaran' => $quiz->labelSasaran(),
            // Nilai peserta lain hanya boleh diunduh pengelola quiz.
            'bisaEkspor' => $user->can('lihatRekap', $quiz),
        ]);
    }

    /* ---------------------------------------------------------------- */

    /**
     * Menyimpan sasaran quiz.
     *
     * Ditulis ulang setiap kali, bukan ditambahkan: mengubah tujuan quiz
     * berarti menggantinya, dan sisa sasaran lama akan diam-diam memperluas
     * jangkauan quiz melebihi yang terlihat di layar.
     *
     * @param  array<string, mixed>  $data
     */
    private function simpanSasaran(Quiz $quiz, array $data): void
    {
        $quiz->sasarans()->delete();

        if ($quiz->sasaran_tipe === 'semua') {
            return;
        }

        foreach (array_unique($data['sasaran'] ?? []) as $id) {
            $quiz->sasarans()->create(['sasaran_id' => $id]);
        }

        $quiz->load('sasarans');
    }

    /**
     * Unduh nilai quiz sebagai Excel.
     *
     * Hanya untuk pengelola quiz: isinya nilai seluruh peserta, bukan nilai
     * sendiri, jadi tidak layak dibuka untuk semua orang yang bisa melihat
     * papan peringkat.
     */
    public function ekspor(Request $request, Quiz $quiz): BinaryFileResponse
    {
        $this->authorize('lihatRekap', $quiz);

        $quiz->load('sasarans');

        $peringkat = $this->peringkatTerbaik($quiz);

        // Yang disasar tapi belum mengerjakan ikut dibawa, dengan keterangan.
        $belum = $quiz->pesertaSasaran()
            ->whereNotIn('id', $peringkat->pluck('user_id'))
            ->with('unitKerja:id,nama', 'cabang:id,nama')
            ->orderBy('name')
            ->get();

        $berkas = 'nilai-quiz-'.Str::slug($quiz->judul).'-'.now()->format('Ymd-Hi').'.xlsx';

        return Excel::download(new NilaiQuizExport($quiz, $peringkat, $belum), $berkas);
    }

    /* ---------------------------------------------------------------- */

    /**
     * Percobaan terbaik tiap peserta, terurut untuk papan peringkat.
     *
     * Satu baris satu orang: kalau semua percobaan ikut, peserta yang boleh
     * mengulang akan memenuhi papan sendirian. Skor tertinggi menang; bila
     * seri, yang paling cepat.
     *
     * @return Collection<int, QuizPercobaan>
     */
    private function peringkatTerbaik(Quiz $quiz)
    {
        return $quiz->percobaans()
            ->where('status', 'selesai')
            ->with([
                'peserta:id,name,npp,unit_kerja_id,cabang_id',
                'peserta.unitKerja:id,nama',
                'peserta.cabang:id,nama',
            ])
            ->get()
            ->sortBy([['skor', 'desc'], ['durasi_detik', 'asc']])
            ->groupBy('user_id')
            ->map(fn ($milik) => $milik->first())
            ->sortBy([['skor', 'desc'], ['durasi_detik', 'asc']])
            ->values();
    }
}
