<?php

namespace App\Exports\Pm;

use App\Models\Pm\Quiz;
use App\Models\Pm\QuizPercobaan;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Ekspor nilai quiz.
 *
 * Isinya sama dengan papan peringkat di layar: satu baris satu peserta,
 * diambil dari percobaan terbaiknya. Peserta yang disasar tetapi belum
 * mengerjakan ikut dicantumkan di bawah dengan keterangan — daftar nilai
 * biasanya dipakai untuk menagih yang belum, jadi menghilangkan mereka
 * justru membuang informasi yang paling dicari.
 */
class NilaiQuizExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    private int $nomor = 0;

    /**
     * @param  Collection<int, QuizPercobaan>  $peringkat  percobaan terbaik tiap peserta, sudah terurut
     * @param  Collection<int, User>  $belum  peserta tersasar yang belum mengerjakan
     */
    public function __construct(
        private Quiz $quiz,
        private Collection $peringkat,
        private Collection $belum,
    ) {}

    public function title(): string
    {
        // Nama sheet Excel dibatasi 31 karakter dan menolak beberapa tanda baca.
        return mb_substr(preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', '-', $this->quiz->judul), 0, 31);
    }

    public function collection(): Collection
    {
        return $this->peringkat->concat($this->belum);
    }

    public function headings(): array
    {
        return [
            'Peringkat', 'NPP', 'Nama Peserta', 'Bidang', 'Kantor',
            'Skor', 'Keterangan', 'Benar', 'Jumlah Soal', 'Poin', 'Poin Maksimal',
            'Waktu Pengerjaan (menit)', 'Selesai Pada',
        ];
    }

    /** @param  QuizPercobaan|User  $baris */
    public function map($baris): array
    {
        return $baris instanceof QuizPercobaan
            ? $this->barisNilai($baris)
            : $this->barisBelum($baris);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function barisNilai(QuizPercobaan $p): array
    {
        $peserta = $p->peserta;

        return [
            ++$this->nomor,
            $peserta?->npp,
            $peserta?->name,
            $peserta?->unitKerja?->nama,
            $peserta?->cabang?->nama ?? 'Kedeputian Wilayah',
            // Angka, bukan teks, supaya bisa dirata-rata langsung di Excel.
            (int) $p->skor,
            $p->skor >= $this->quiz->nilai_lulus ? 'Lulus' : 'Belum Lulus',
            (int) $p->jumlah_benar,
            (int) $p->jumlah_soal,
            (int) $p->poin_didapat,
            (int) $p->poin_maksimal,
            $p->durasi_detik !== null ? round($p->durasi_detik / 60, 1) : null,
            $p->selesai_pada?->format('d/m/Y H:i'),
        ];
    }

    /**
     * Baris peserta yang belum mengerjakan.
     *
     * Kolom skor sengaja dikosongkan, bukan diisi 0: nol adalah nilai yang
     * pernah diperoleh, sedangkan ini belum dinilai sama sekali — dan nol
     * palsu akan menyeret turun rata-rata bila kolomnya dijumlah.
     */
    private function barisBelum($peserta): array
    {
        return [
            null,
            $peserta->npp,
            $peserta->name,
            $peserta->unitKerja?->nama,
            $peserta->cabang?->nama ?? 'Kedeputian Wilayah',
            null,
            'Belum Mengerjakan',
            null, null, null, null, null, null,
        ];
    }
}
