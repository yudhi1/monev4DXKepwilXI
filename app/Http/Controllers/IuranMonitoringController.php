<?php

namespace App\Http\Controllers;

use App\Exports\IuranExport;
use App\Http\Requests\IuranMonitoringRequest;
use App\Models\Cabang;
use App\Models\IuranMonitoring;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Monitoring iuran Pemda per minggu. Tiap cabang dibatasi maksimal
 * tiga Pemda per minggu dan nama Pemda tidak boleh berulang di minggu sama.
 */
class IuranMonitoringController extends Controller
{
    public const MAKS_PEMDA_PER_MINGGU = 3;

    private const BULAN = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $tahun = (int) $request->query('tahun', date('Y'));
        $bulan = min(max((int) $request->query('bulan', date('n')), 1), 12);

        $cabangs = $this->cabangTerpilih($user);
        $filter = $this->filter($request, $user, $cabangs);

        return Inertia::render('IuranMonitoring/Index', [
            'cabangs' => $cabangs,
            'items' => $this->daftar($cabangs, $tahun, $bulan, $filter),
            'namaBulan' => self::BULAN,
            'maksPemdaPerMinggu' => self::MAKS_PEMDA_PER_MINGGU,
            'filter' => [
                'tahun' => $tahun,
                'bulan' => $bulan,
                'cabang_id' => $filter['cabang_id'],
                'minggu' => $filter['minggu'],
                'status' => $filter['status'],
            ],
            'terkunciCabang' => (bool) $user?->hasRole('kantor_cabang'),
        ]);
    }

    public function store(IuranMonitoringRequest $request): RedirectResponse
    {
        $data = $this->siapkan($request);

        $this->pastikanBolehMenulis($request, $data['cabang_id']);
        $this->pastikanTidakMelanggarBatas($data);

        IuranMonitoring::create([
            ...$data,
            'no_urut' => $this->jumlahSeminggu($data) + 1,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Data iuran berhasil ditambahkan.');
    }

    public function update(IuranMonitoringRequest $request, IuranMonitoring $iuran): RedirectResponse
    {
        $data = $this->siapkan($request);

        $this->pastikanBolehMenulis($request, $data['cabang_id']);
        $this->pastikanTidakMelanggarBatas($data, $iuran);

        $iuran->update($data);

        return back()->with('success', 'Data iuran berhasil diperbarui.');
    }

    public function destroy(Request $request, IuranMonitoring $iuran): RedirectResponse
    {
        $this->pastikanBolehMenulis($request, $iuran->cabang_id);

        $iuran->delete();

        return back()->with('success', 'Data iuran berhasil dihapus.');
    }

    public function excel(Request $request)
    {
        [$items, $tahun, $bulan] = $this->dataTerfilter($request);

        $nama = 'monitoring-iuran-'.$tahun.'-'.str_pad((string) $bulan, 2, '0', STR_PAD_LEFT).'.xlsx';

        return Excel::download(new IuranExport($items, $this->bulanBerindeksSatu()), $nama);
    }

    public function pdf(Request $request)
    {
        [$items, $tahun, $bulan, $filter] = $this->dataTerfilter($request);

        $periode = self::BULAN[$bulan - 1].' '.$tahun.($filter['minggu'] ? ' · Minggu '.$filter['minggu'] : '');

        $pdf = Pdf::loadView('reports.iuran-pdf', [
            'items' => $items,
            'bulanLabels' => $this->bulanBerindeksSatu(),
            'periode' => $periode,
            'cabangFilter' => $filter['cabang_id'] ? Cabang::find($filter['cabang_id'])?->nama : '',
        ])->setPaper('a4', 'landscape');

        $nama = 'monitoring-iuran-'.$tahun.'-'.str_pad((string) $bulan, 2, '0', STR_PAD_LEFT).'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $nama);
    }

    /** View PDF dan IuranExport mengharapkan array bulan berbasis 1. */
    private function bulanBerindeksSatu(): array
    {
        return array_combine(range(1, 12), self::BULAN);
    }

    private function dataTerfilter(Request $request): array
    {
        $user = $request->user();
        $tahun = (int) $request->query('tahun', date('Y'));
        $bulan = min(max((int) $request->query('bulan', date('n')), 1), 12);

        $cabangs = $this->cabangTerpilih($user);
        $filter = $this->filter($request, $user, $cabangs);

        return [$this->kueri($cabangs, $tahun, $bulan, $filter)->get(), $tahun, $bulan, $filter];
    }

    private function daftar($cabangs, int $tahun, int $bulan, array $filter): array
    {
        return $this->kueri($cabangs, $tahun, $bulan, $filter)
            ->get()
            ->map(fn (IuranMonitoring $item) => [
                'id' => $item->id,
                'cabang_id' => $item->cabang_id,
                'cabang' => $item->cabang?->nama,
                'minggu' => $item->minggu,
                'no_urut' => $item->no_urut,
                'nama_pemda' => $item->nama_pemda,
                'tagihan' => (float) $item->tagihan,
                'status_bayar' => $item->status_bayar,
                'outstanding' => (float) $item->outstanding,
                'pic' => (string) $item->pic,
                'kendala' => (string) $item->kendala,
                'keterangan' => (string) $item->keterangan,
                'target_penyelesaian' => $item->target_penyelesaian?->format('Y-m-d'),
            ])
            ->all();
    }

    private function kueri($cabangs, int $tahun, int $bulan, array $filter)
    {
        return IuranMonitoring::with('cabang:id,nama')
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->whereIn('cabang_id', $cabangs->pluck('id'))
            ->when($filter['cabang_id'], fn ($q, $id) => $q->where('cabang_id', $id))
            ->when($filter['minggu'], fn ($q, $m) => $q->where('minggu', $m))
            ->when($filter['status'], fn ($q, $s) => $q->where('status_bayar', $s))
            ->orderBy('minggu')
            ->orderBy('cabang_id')
            ->orderBy('no_urut')
            ->orderBy('id');
    }

    private function filter(Request $request, ?User $user, $cabangs): array
    {
        // Kantor cabang selalu terkunci pada cabangnya sendiri.
        if ($user?->hasRole('kantor_cabang') && $user->cabang_id) {
            $cabangId = $user->cabang_id;
        } else {
            $diminta = $request->query('cabang_id');
            $cabangId = ($diminta && $cabangs->contains('id', (int) $diminta)) ? (int) $diminta : null;
        }

        $minggu = $request->query('minggu');
        $status = $request->query('status');

        return [
            'cabang_id' => $cabangId,
            'minggu' => $minggu ? (int) $minggu : null,
            'status' => in_array($status, ['sudah', 'sebagian', 'belum'], true) ? $status : null,
        ];
    }

    /**
     * Outstanding diturunkan dari status bayar, sama seperti perilaku lama:
     * lunas → 0, belum bayar → sebesar tagihan, sebagian → sesuai isian.
     */
    private function siapkan(IuranMonitoringRequest $request): array
    {
        $data = $request->validated();
        $data['nama_pemda'] = trim($data['nama_pemda']);

        if ($data['status_bayar'] === 'sudah') {
            $data['outstanding'] = 0;
        } elseif ($data['status_bayar'] === 'belum') {
            $data['outstanding'] = $data['tagihan'];
        }

        return $data;
    }

    private function pastikanBolehMenulis(Request $request, int $cabangId): void
    {
        $user = $request->user();

        if ($user->hasRole('kantor_cabang') && $user->cabang_id && $cabangId !== $user->cabang_id) {
            throw ValidationException::withMessages([
                'cabang_id' => 'Tidak diizinkan mengubah data cabang lain.',
            ]);
        }
    }

    private function pastikanTidakMelanggarBatas(array $data, ?IuranMonitoring $kecuali = null): void
    {
        $seminggu = $this->kueriSeminggu($data, $kecuali);

        $duplikat = (clone $seminggu)
            ->whereRaw('LOWER(nama_pemda) = ?', [mb_strtolower($data['nama_pemda'])])
            ->exists();

        if ($duplikat) {
            throw ValidationException::withMessages([
                'nama_pemda' => "Pemda \"{$data['nama_pemda']}\" sudah ada di Minggu {$data['minggu']}.",
            ]);
        }

        if ($seminggu->count() >= self::MAKS_PEMDA_PER_MINGGU) {
            throw ValidationException::withMessages([
                'nama_pemda' => 'Maksimal '.self::MAKS_PEMDA_PER_MINGGU.' Pemda per minggu sudah tercapai.',
            ]);
        }
    }

    private function jumlahSeminggu(array $data): int
    {
        return $this->kueriSeminggu($data)->count();
    }

    private function kueriSeminggu(array $data, ?IuranMonitoring $kecuali = null)
    {
        return IuranMonitoring::where('cabang_id', $data['cabang_id'])
            ->where('tahun', $data['tahun'])
            ->where('bulan', $data['bulan'])
            ->where('minggu', $data['minggu'])
            ->when($kecuali, fn ($q) => $q->where('id', '!=', $kecuali->id));
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
            ->get(['id', 'kode', 'nama']);
    }

    private function wilayahTerbatas(?User $user): ?int
    {
        if (! $user || $user->hasRole('admin')) {
            return null;
        }

        return $user->wilayah_id;
    }
}
