<?php

namespace App\Http\Controllers\Kinerja;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kinerja\FileCapaianRequest;
use App\Models\Kinerja\FileCapaian;
use App\Models\Kinerja\Indikator;
use App\Models\Kinerja\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Berkas capaian kinerja: unggah, lihat, dan unduh.
 *
 * Admin dan Kedeputian Wilayah yang mengunggah; kantor cabang membuka dan
 * mengunduhnya — itulah tujuan modul ini, menyampaikan berkas capaian.
 */
class FileCapaianController extends Controller
{
    private const BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function index(Request $request): Response
    {
        $cari = trim((string) $request->query('cari', ''));
        $kategori = $request->query('kategori');
        $indikator = $request->query('indikator');
        $bulan = $request->query('bulan');
        $tahun = (int) $request->query('tahun', date('Y'));

        /*
         | Berkas diambil sekaligus untuk satu tahun, tanpa paginasi, lalu
         | dikelompokkan kategori → indikator di bawah. Halaman ini memang
         | bertumpuk, dan paginasi mendatar akan memotong sebuah kelompok di
         | tengah — kartu kategori yang isinya "sebagian" lebih menyesatkan
         | daripada berguna. Saringan tahun selalu aktif, jadi jumlahnya
         | terbatas pada indikator × 12 bulan.
         */
        $files = FileCapaian::query()
            ->with(['indikator:id,nama,urutan,kinerja_kategori_id', 'indikator.kategori:id,nama,urutan', 'pengunggah:id,name'])
            ->when($cari !== '', fn ($q) => $q->where(
                fn ($sub) => $sub->where('nama', 'like', "%{$cari}%")
                    ->orWhere('keterangan', 'like', "%{$cari}%")
            ))
            ->when($kategori, fn ($q) => $q->whereHas(
                'indikator',
                fn ($sub) => $sub->where('kinerja_kategori_id', $kategori)
            ))
            ->when($indikator, fn ($q) => $q->where('kinerja_indikator_id', $indikator))
            ->when($bulan, fn ($q) => $q->where('bulan', $bulan))
            ->where('tahun', $tahun)
            ->orderByDesc('bulan')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Kinerja/File', [
            'kelompok' => $this->kelompokkan($files),
            'jumlahFile' => $files->count(),
            'kategoris' => Kategori::aktif()->urutTampil()->get(['id', 'nama']),

            /*
             | Seluruh indikator dikirim sekaligus, bukan diambil ulang lewat
             | permintaan terpisah tiap kali kategori berganti: jumlahnya kecil
             | dan pemilihan indikator jadi seketika.
             */
            'indikators' => Indikator::aktif()->urutTampil()->get(['id', 'nama', 'kinerja_kategori_id']),
            'daftarBulan' => collect(self::BULAN)->map(fn ($nama, $nomor) => [
                'nomor' => $nomor,
                'nama' => $nama,
            ])->values(),
            'filter' => [
                'cari' => $cari,
                'kategori' => $kategori,
                'indikator' => $indikator,
                'bulan' => $bulan,
                'tahun' => $tahun,
            ],
            'bisaKelola' => $request->user()?->hasAnyRole(['admin', 'kedeputian_wilayah']) ?? false,
            'tahunBawaan' => (int) date('Y'),
            'bulanBawaan' => (int) date('n'),
        ]);
    }

    /**
     * Susun berkas menjadi kategori → indikator → file.
     *
     * Pengelompokan dikerjakan di sini, bukan di Vue, supaya urutan tampil
     * kategori dan indikator mengikuti kolom `urutan` yang sudah diatur
     * pengguna — sesuatu yang hilang begitu datanya dikelompokkan ulang di
     * sisi klien.
     *
     * @param  Collection<int, FileCapaian>  $files
     */
    private function kelompokkan($files): array
    {
        return $files
            ->groupBy(fn (FileCapaian $f) => $f->indikator?->kinerja_kategori_id ?? 0)
            ->map(fn ($milikKategori) => [
                'id' => $milikKategori->first()->indikator?->kategori?->id ?? 0,
                'nama' => $milikKategori->first()->indikator?->kategori?->nama ?? 'Tanpa kategori',
                'urutan' => $milikKategori->first()->indikator?->kategori?->urutan ?? 0,
                'jumlahFile' => $milikKategori->count(),
                'indikators' => $milikKategori
                    ->groupBy('kinerja_indikator_id')
                    ->map(fn ($milikIndikator) => [
                        'id' => $milikIndikator->first()->kinerja_indikator_id,
                        'nama' => $milikIndikator->first()->indikator?->nama ?? '-',
                        'urutan' => $milikIndikator->first()->indikator?->urutan ?? 0,
                        'files' => $milikIndikator->map(fn (FileCapaian $f) => [
                            'id' => $f->id,
                            'nama' => $f->nama,
                            'nama_asli' => $f->nama_asli,
                            'ukuran' => $f->ukuran_terbaca,
                            'keterangan' => $f->keterangan,
                            'bulan' => $f->bulan,
                            'namaBulan' => self::BULAN[$f->bulan] ?? '-',
                            'tahun' => $f->tahun,
                            'kinerja_indikator_id' => $f->kinerja_indikator_id,
                            'kinerja_kategori_id' => $f->indikator?->kinerja_kategori_id,
                            'indikator' => $f->indikator?->nama ?? '-',
                            'kategori' => $f->indikator?->kategori?->nama ?? '-',
                            'pengunggah' => $f->pengunggah?->name ?? '-',
                            'diunggah' => $f->created_at?->format('d/m/Y H:i'),
                        ])->values()->all(),
                    ])
                    ->sortBy([['urutan', 'asc'], ['nama', 'asc']])
                    ->values()
                    ->all(),
            ])
            ->sortBy([['urutan', 'asc'], ['nama', 'asc']])
            ->values()
            ->all();
    }

    public function store(FileCapaianRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $berkas = $request->file('file');

        FileCapaian::create([
            'kinerja_indikator_id' => $data['kinerja_indikator_id'],
            'nama' => $data['nama'],
            'file_path' => $berkas->store('kinerja-files'),
            'nama_asli' => $berkas->getClientOriginalName(),
            'mime' => $berkas->getClientMimeType(),
            'ukuran' => $berkas->getSize(),
            'keterangan' => $data['keterangan'] ?? null,
            'bulan' => $data['bulan'],
            'tahun' => $data['tahun'],
            'diunggah_oleh' => $request->user()->id,
        ]);

        return back()->with('success', 'File capaian berhasil diunggah.');
    }

    /** Berkas boleh diganti; kalau tidak diunggah ulang, yang lama dipertahankan. */
    public function update(FileCapaianRequest $request, FileCapaian $file): RedirectResponse
    {
        $data = $request->validated();

        $atribut = [
            'kinerja_indikator_id' => $data['kinerja_indikator_id'],
            'nama' => $data['nama'],
            'keterangan' => $data['keterangan'] ?? null,
            'bulan' => $data['bulan'],
            'tahun' => $data['tahun'],
        ];

        if ($berkas = $request->file('file')) {
            Storage::delete($file->file_path);

            $atribut += [
                'file_path' => $berkas->store('kinerja-files'),
                'nama_asli' => $berkas->getClientOriginalName(),
                'mime' => $berkas->getClientMimeType(),
                'ukuran' => $berkas->getSize(),
            ];
        }

        $file->update($atribut);

        return back()->with('success', 'File capaian berhasil diperbarui.');
    }

    public function unduh(FileCapaian $file): StreamedResponse
    {
        abort_unless(Storage::exists($file->file_path), 404);

        return Storage::download($file->file_path, $file->nama_asli);
    }

    public function destroy(Request $request, FileCapaian $file): RedirectResponse
    {
        if (! $request->user()?->hasAnyRole(['admin', 'kedeputian_wilayah'])) {
            abort(403);
        }

        Storage::delete($file->file_path);
        $file->delete();

        return back()->with('success', 'File capaian berhasil dihapus.');
    }
}
