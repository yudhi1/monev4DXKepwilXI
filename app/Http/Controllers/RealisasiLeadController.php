<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\LeadMeasure;
use App\Models\LeadMeasureRealisasi;
use App\Models\User;
use App\Models\Wig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Input realisasi mingguan Lead Measure: satu WIG + cabang + bulan,
 * tiap Lead Measure diisi untuk seluruh minggu dalam bulan itu sekaligus.
 */
class RealisasiLeadController extends Controller
{
    private const BULAN = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $tahun = (int) $request->query('tahun', date('Y'));
        $bulan = min(max((int) $request->query('bulan', date('n')), 1), 12);

        $wigs = Wig::query()
            ->when($this->wilayahTerbatas($user), fn ($q, $wilayahId) => $q->where('wilayah_id', $wilayahId))
            ->orderBy('kode_wig')
            ->get(['id', 'kode_wig', 'nama_wig', 'bidang', 'tahun']);

        $cabangs = $this->cabangTerpilih($user);

        $wigId = $request->query('wig_id') ? (int) $request->query('wig_id') : null;
        $cabangId = $this->tentukanCabang($request, $user, $cabangs);

        return Inertia::render('Realisasi/Index', [
            'wigs' => $wigs,
            'cabangs' => $cabangs,
            'leads' => $this->daftarLead($wigId, $cabangId, $tahun, $bulan),
            'namaBulan' => self::BULAN,
            'jumlahMinggu' => LeadMeasureRealisasi::JUMLAH_MINGGU,
            'filter' => [
                'wig_id' => $wigId,
                'cabang_id' => $cabangId,
                'tahun' => $tahun,
                'bulan' => $bulan,
            ],
            'terkunciCabang' => (bool) $user?->hasRole('kantor_cabang'),
        ]);
    }

    /**
     * Menyimpan satu Lead Measure untuk seluruh minggunya sekaligus.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lead_measure_id' => ['required', 'exists:lead_measures,id'],
            'cabang_id' => ['required', 'exists:cabangs,id'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'minggu' => ['required', 'array', 'size:'.LeadMeasureRealisasi::JUMLAH_MINGGU],
            'minggu.*.minggu_ke' => ['required', 'integer', 'min:1', 'max:'.LeadMeasureRealisasi::JUMLAH_MINGGU],
            'minggu.*.target' => ['nullable', 'numeric'],
            'minggu.*.realisasi' => ['nullable', 'numeric'],
            'minggu.*.keterangan' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        if ($user->hasRole('kantor_cabang') && $user->cabang_id && (int) $data['cabang_id'] !== $user->cabang_id) {
            return back()->with('error', 'Tidak diizinkan input untuk cabang lain.');
        }

        // Lead Measure harus benar milik cabang yang dikirim.
        $lead = LeadMeasure::find($data['lead_measure_id']);

        if ($lead->cabang_id !== (int) $data['cabang_id']) {
            return back()->with('error', 'Lead Measure tidak sesuai dengan cabang yang dipilih.');
        }

        foreach ($data['minggu'] as $baris) {
            LeadMeasureRealisasi::updateOrCreate(
                [
                    'lead_measure_id' => $data['lead_measure_id'],
                    'cabang_id' => $data['cabang_id'],
                    'tahun' => $data['tahun'],
                    'bulan' => $data['bulan'],
                    'minggu_ke' => $baris['minggu_ke'],
                ],
                [
                    'target' => (float) ($baris['target'] ?? 0),
                    'realisasi' => (float) ($baris['realisasi'] ?? 0),
                    'catatan' => $baris['keterangan'] ?? null,
                    'created_by' => $user->id,
                ]
            );
        }

        return back()->with('success', "Realisasi {$lead->kode_lead} berhasil disimpan.");
    }

    private function daftarLead(?int $wigId, ?int $cabangId, int $tahun, int $bulan): array
    {
        if (! $wigId || ! $cabangId) {
            return [];
        }

        $leads = LeadMeasure::with('lagMeasure:id,kode_lag,nama_lag')
            ->where('wig_id', $wigId)
            ->where('cabang_id', $cabangId)
            ->where('is_active', true)
            ->orderBy('kode_lead')
            ->get();

        $tersimpan = LeadMeasureRealisasi::whereIn('lead_measure_id', $leads->pluck('id'))
            ->where('cabang_id', $cabangId)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->get()
            ->groupBy('lead_measure_id');

        return $leads->map(function (LeadMeasure $lead) use ($tersimpan) {
            $perMinggu = ($tersimpan->get($lead->id) ?? collect())->keyBy('minggu_ke');

            $minggu = [];

            for ($m = 1; $m <= LeadMeasureRealisasi::JUMLAH_MINGGU; $m++) {
                $r = $perMinggu->get($m);

                $minggu[] = [
                    'minggu_ke' => $m,
                    'target' => (float) ($r?->target ?? 0),
                    'realisasi' => (float) ($r?->realisasi ?? 0),
                    'keterangan' => (string) ($r?->catatan ?? ''),
                    'persentase' => (float) ($r?->persentase ?? 0),
                ];
            }

            return [
                'id' => $lead->id,
                'kode_lead' => $lead->kode_lead,
                'nama_lead' => $lead->nama_lead,
                'satuan' => $lead->satuan,
                'lag' => $lead->lagMeasure?->kode_lag,
                'minggu' => $minggu,
            ];
        })->all();
    }

    private function tentukanCabang(Request $request, ?User $user, $cabangs): ?int
    {
        if ($user?->hasRole('kantor_cabang') && $user->cabang_id) {
            return $user->cabang_id;
        }

        $diminta = $request->query('cabang_id');

        return ($diminta && $cabangs->contains('id', (int) $diminta)) ? (int) $diminta : null;
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
