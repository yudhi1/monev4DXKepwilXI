<?php

namespace App\Http\Controllers;

use App\Exports\RealisasiExport;
use App\Models\Cabang;
use App\Models\LeadMeasure;
use App\Models\LeadMeasureRealisasi;
use App\Models\Wig;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    private function buildQuery(Request $request)
    {
        $u = $request->user();

        $q = LeadMeasureRealisasi::with('leadMeasure.lagMeasure.wig', 'cabang')
            ->where('tahun', (int) ($request->tahun ?? date('Y')));

        if ($request->filled('wig_id')) {
            $q->whereHas('leadMeasure', fn($l) => $l->where('wig_id', $request->wig_id));
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
                $q->whereHas('cabang', fn($c) => $c->where('wilayah_id', $u->wilayah_id));
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
        if ($request->filled('wig_id')) $leadsQ->where('wig_id', $request->wig_id);
        if ($u && ! $u->hasRole('admin') && $u->wilayah_id) {
            $leadsQ->whereHas('wig', fn($w) => $w->where('wilayah_id', $u->wilayah_id));
        }
        $leads = $leadsQ->get();

        $cabangsQ = Cabang::orderBy('nama');
        if ($u && $u->hasRole('kantor_cabang') && $u->cabang_id) {
            $cabangsQ->where('id', $u->cabang_id);
        } elseif ($u && $u->hasRole('kedeputian_wilayah') && $u->wilayah_id) {
            $cabangsQ->where('wilayah_id', $u->wilayah_id);
        }
        $cabangs = $cabangsQ->get();

        return view('laporan.index', [
            'realisasis' => $this->buildQuery($request)->paginate(25)->withQueryString(),
            'tahun' => $tahun,
            'wigs' => $wigs,
            'leads' => $leads,
            'cabangs' => $cabangs,
            'filters' => $request->only(['wig_id','lead_measure_id','cabang_id','bulan','minggu','tahun']),
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
