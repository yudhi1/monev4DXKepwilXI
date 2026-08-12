<?php

namespace App\Http\Controllers;

use App\Exports\RealisasiExport;
use App\Models\Cabang;
use App\Models\LeadMeasure;
use App\Models\LeadMeasureRealisasi;
use App\Models\Wig;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    private const BULAN = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    private function buildQuery(Request $request)
    {
        $u = $request->user();

        $q = LeadMeasureRealisasi::with('leadMeasure.lagMeasure.wig', 'cabang')
            ->where('tahun', (int) ($request->tahun ?? date('Y')));

        if ($request->filled('wig_id')) {
            $q->whereHas('leadMeasure', fn ($l) => $l->where('wig_id', $request->wig_id));
        }
        if ($request->filled('lead_measure_id')) {
            $q->where('lead_measure_id', $request->lead_measure_id);
        }
        if ($request->filled('bulan')) {
            $q->where('bulan', (int) $request->bulan);
        }
        if ($request->filled('minggu')) {
            $q->where('minggu_ke', (int) $request->minggu);
        }
        if ($request->filled('cabang_id')) {
            $q->where('cabang_id', (int) $request->cabang_id);
        }

        if ($u && ! $u->hasRole('admin')) {
            if ($u->cabang_id) {
                $q->where('cabang_id', $u->cabang_id);
            } elseif ($u->wilayah_id) {
                $q->whereHas('cabang', fn ($c) => $c->where('wilayah_id', $u->wilayah_id));
            }
        }

        return $q->orderBy('bulan')->orderBy('minggu_ke');
    }

    public function index(Request $request)
    {
        $tahun = (int) ($request->tahun ?? date('Y'));
        $u = $request->user();

        $wigsQ = Wig::orderBy('kode_wig');
        if ($u && ! $u->hasRole('admin') && $u->wilayah_id) {
            $wigsQ->where('wilayah_id', $u->wilayah_id);
        }
        $wigs = $wigsQ->get();

        $leadsQ = LeadMeasure::with('lagMeasure.wig')->orderBy('kode_lead');
        if ($request->filled('wig_id')) {
            $leadsQ->where('wig_id', $request->wig_id);
        }
        if ($u && ! $u->hasRole('admin') && $u->wilayah_id) {
            $leadsQ->whereHas('wig', fn ($w) => $w->where('wilayah_id', $u->wilayah_id));
        }
        $leads = $leadsQ->get();

        $cabangsQ = Cabang::orderBy('nama');
        if ($u && $u->hasRole('kantor_cabang') && $u->cabang_id) {
            $cabangsQ->where('id', $u->cabang_id);
        } elseif ($u && $u->hasRole('kedeputian_wilayah') && $u->wilayah_id) {
            $cabangsQ->where('wilayah_id', $u->wilayah_id);
        }
        $cabangs = $cabangsQ->get();

        $realisasis = $this->buildQuery($request)
            ->paginate(25)
            ->withQueryString()
            ->through(fn (LeadMeasureRealisasi $r) => [
                'id' => $r->id,
                'cabang' => $r->cabang?->nama,
                'wig' => $r->leadMeasure?->lagMeasure?->wig?->kode_wig,
                'lag' => $r->leadMeasure?->lagMeasure?->kode_lag,
                'kode_lead' => $r->leadMeasure?->kode_lead,
                'nama_lead' => $r->leadMeasure?->nama_lead,
                'bulan' => $r->bulan,
                'minggu_ke' => $r->minggu_ke,
                'target' => (float) $r->target,
                'realisasi' => (float) $r->realisasi,
                'persentase' => (float) $r->persentase,
                'catatan' => (string) ($r->catatan ?? ''),
            ]);

        return Inertia::render('Laporan/Index', [
            'realisasis' => $realisasis,
            'wigs' => $wigs->map(fn (Wig $w) => [
                'id' => $w->id,
                'kode_wig' => $w->kode_wig,
                'nama_wig' => $w->nama_wig,
            ]),
            'leads' => $leads->map(fn (LeadMeasure $l) => [
                'id' => $l->id,
                'kode_lead' => $l->kode_lead,
                'nama_lead' => $l->nama_lead,
            ]),
            'cabangs' => $cabangs->map(fn (Cabang $c) => ['id' => $c->id, 'nama' => $c->nama]),
            'namaBulan' => self::BULAN,
            'filter' => [
                'tahun' => $tahun,
                'wig_id' => $request->query('wig_id'),
                'lead_measure_id' => $request->query('lead_measure_id'),
                'cabang_id' => $request->query('cabang_id'),
                'bulan' => $request->query('bulan'),
                'minggu' => $request->query('minggu'),
            ],
        ]);
    }

    public function excel(Request $request)
    {
        $tahun = (int) ($request->tahun ?? date('Y'));

        return Excel::download(
            new RealisasiExport($this->buildQuery($request)->get()),
            "laporan-realisasi-{$tahun}.xlsx"
        );
    }

    public function pdf(Request $request)
    {
        $tahun = (int) ($request->tahun ?? date('Y'));
        $pdf = Pdf::loadView('laporan.pdf', [
            'realisasis' => $this->buildQuery($request)->get(),
            'tahun' => $tahun,
        ])->setPaper('a4', 'landscape');

        return $pdf->download("laporan-realisasi-{$tahun}.pdf");
    }
}
