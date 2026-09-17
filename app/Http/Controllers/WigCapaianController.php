<?php

namespace App\Http\Controllers;

use App\Exports\WigCapaianExport;
use App\Models\Cabang;
use App\Models\User;
use App\Models\Wig;
use App\Models\WigRealisasi;
use App\Models\WigTarget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Target dan realisasi WIG dalam satu halaman.
 *
 * Menggabungkan dua menu lama (Input Target WIG dan Input Realisasi Bulanan)
 * supaya capaian per bulan bisa dibaca berdampingan dengan rencananya —
 * baik terhadap target tahunan maupun terhadap target bulan berjalan.
 *
 * Petugas mengisi angka bulan berjalan saja; akumulasi s.d. bulan dihitung
 * di sisi tampilan agar tidak mungkin tidak konsisten.
 */
class WigCapaianController extends Controller
{
    private const BULAN = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    private const SATUAN = ['Rp', '%', 'orang', 'unit', 'transaksi', 'kasus'];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $tahun = (int) $request->query('tahun', date('Y'));
        $bulanAcuan = min(max((int) $request->query('bulan', date('n')), 1), 12);

        $wigs = Wig::query()
            ->when($this->wilayahTerbatas($user), fn ($q, $wilayahId) => $q->where('wilayah_id', $wilayahId))
            ->orderByDesc('tahun')
            ->orderBy('kode_wig')
            ->get(['id', 'kode_wig', 'nama_wig', 'bidang', 'tahun', 'sifat_capaian', 'arah']);

        $wigId = (int) ($request->query('wig_id') ?: $wigs->first()?->id);
        $wig = $wigs->firstWhere('id', $wigId);

        return Inertia::render('Wig/Capaian', [
            'wigs' => $wigs,
            'wig' => $wig,
            'baris' => $wig ? $this->baris($user, $wigId, $tahun) : [],
            'namaBulan' => self::BULAN,
            'daftarSatuan' => self::SATUAN,
            'sifat' => config('wig.sifat'),
            'filter' => ['wig_id' => $wig?->id, 'tahun' => $tahun, 'bulan' => $bulanAcuan],
            // Kantor cabang hanya boleh mengisi realisasi; target ditetapkan wilayah.
            'bisaUbahTarget' => (bool) $user?->hasAnyRole(['admin', 'kedeputian_wilayah']),
        ]);
    }

    /** Unduhan memakai filter yang sama dengan tampilan agar isinya sepadan. */
    public function excel(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        $tahun = (int) $request->query('tahun', date('Y'));
        $bulanAcuan = min(max((int) $request->query('bulan', date('n')), 1), 12);

        $wig = Wig::query()
            ->when($this->wilayahTerbatas($user), fn ($q, $wilayahId) => $q->where('wilayah_id', $wilayahId))
            ->findOrFail((int) $request->query('wig_id'));

        $nama = Str::slug("capaian-wig-{$wig->kode_wig}-{$tahun}").'.xlsx';

        return Excel::download(
            new WigCapaianExport(
                $this->baris($user, $wig->id, $tahun),
                self::BULAN,
                $tahun,
                $bulanAcuan,
                $wig->kode_wig,
                $wig->nama_wig,
                $wig->sifat_capaian,
                $wig->arah,
            ),
            $nama
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'wig_id' => ['required', 'exists:wigs,id'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'baris' => ['required', 'array'],
            'baris.*.cabang_id' => ['required', 'exists:cabangs,id'],
            'baris.*.nilai_awal' => ['nullable', 'numeric'],
            'baris.*.nilai_target' => ['nullable', 'numeric'],
            'baris.*.satuan' => ['nullable', 'string', 'max:50'],
            'baris.*.tanggal_target' => ['nullable', 'date'],
            'baris.*.bulan' => ['required', 'array', 'size:12'],
            'baris.*.bulan.*.bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'baris.*.bulan.*.target' => ['nullable', 'numeric', 'min:0'],
            'baris.*.bulan.*.realisasi' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user = $request->user();
        $bisaUbahTarget = $user->hasAnyRole(['admin', 'kedeputian_wilayah']);
        $diizinkan = $this->cabangTerpilih($user)->pluck('id');

        foreach ($data['baris'] as $baris) {
            if (! $diizinkan->contains($baris['cabang_id'])) {
                continue;
            }

            if ($bisaUbahTarget) {
                WigTarget::updateOrCreate(
                    ['wig_id' => $data['wig_id'], 'cabang_id' => $baris['cabang_id']],
                    [
                        'nilai_awal' => (float) ($baris['nilai_awal'] ?? 0),
                        'nilai_target' => (float) ($baris['nilai_target'] ?? 0),
                        'satuan' => $baris['satuan'] ?: 'Rp',
                        'tanggal_target' => $baris['tanggal_target'] ?: null,
                    ]
                );
            }

            foreach ($baris['bulan'] as $bulan) {
                $isian = ['nilai' => (float) ($bulan['realisasi'] ?? 0), 'created_by' => $user->id];

                if ($bisaUbahTarget) {
                    $isian['target'] = (float) ($bulan['target'] ?? 0);
                }

                WigRealisasi::updateOrCreate(
                    [
                        'wig_id' => $data['wig_id'],
                        'cabang_id' => $baris['cabang_id'],
                        'tahun' => $data['tahun'],
                        'bulan' => $bulan['bulan'],
                    ],
                    $isian
                );
            }
        }

        return back()->with('success', 'Target dan realisasi berhasil disimpan.');
    }

    /**
     * Satu baris per unit kerja, berisi target tahunan beserta target dan
     * realisasi dua belas bulan.
     */
    private function baris(?User $user, int $wigId, int $tahun): array
    {
        $targets = WigTarget::where('wig_id', $wigId)->get()->keyBy('cabang_id');

        $bulanan = WigRealisasi::where('wig_id', $wigId)
            ->where('tahun', $tahun)
            ->get()
            ->groupBy('cabang_id');

        return $this->cabangTerpilih($user)
            ->map(function (Cabang $cabang) use ($targets, $bulanan) {
                $target = $targets->get($cabang->id);
                $perBulan = ($bulanan->get($cabang->id) ?? collect())->keyBy('bulan');

                $bulan = [];

                for ($b = 1; $b <= 12; $b++) {
                    $baris = $perBulan->get($b);

                    $bulan[] = [
                        'bulan' => $b,
                        'target' => (float) ($baris?->target ?? 0),
                        'realisasi' => (float) ($baris?->nilai ?? 0),
                    ];
                }

                return [
                    'cabang_id' => $cabang->id,
                    'cabang_nama' => $cabang->nama,
                    'nilai_awal' => (float) ($target?->nilai_awal ?? 0),
                    'nilai_target' => (float) ($target?->nilai_target ?? 0),
                    'satuan' => $target?->satuan ?? 'Rp',
                    'tanggal_target' => $target?->tanggal_target?->format('Y-m-d') ?? '',
                    'bulan' => $bulan,
                ];
            })
            ->values()
            ->all();
    }

    private function cabangTerpilih(?User $user)
    {
        return Cabang::query()
            ->when(
                $user?->hasRole('kantor_cabang') && $user->cabang_id,
                fn ($q) => $q->where('id', $user->cabang_id),
                fn ($q) => $q->when($this->wilayahTerbatas($user), fn ($sub, $wilayahId) => $sub->where('wilayah_id', $wilayahId))
            )
            ->orderBy('nama')
            ->get();
    }

    private function wilayahTerbatas(?User $user): ?int
    {
        if (! $user || $user->hasRole('admin')) {
            return null;
        }

        return $user->wilayah_id;
    }
}
