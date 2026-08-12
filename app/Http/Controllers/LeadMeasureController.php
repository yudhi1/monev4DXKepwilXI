<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeadMeasureRequest;
use App\Models\Cabang;
use App\Models\LagMeasure;
use App\Models\LeadMeasure;
use App\Models\User;
use App\Models\Wig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadMeasureController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tahun = (int) $request->query('tahun', date('Y'));
        $wigId = $request->query('wig_id');

        // User kantor cabang selalu terkunci pada cabangnya sendiri.
        $cabangId = $user?->hasRole('kantor_cabang')
            ? $user->cabang_id
            : $request->query('cabang_id');

        $leads = collect();
        $lags = collect();

        // Daftar baru terisi setelah WIG dan cabang dipilih — sama seperti versi Livewire.
        if ($wigId && $cabangId) {
            $leads = LeadMeasure::with('lagMeasure:id,kode_lag,nama_lag')
                ->where('wig_id', $wigId)
                ->where('cabang_id', $cabangId)
                ->orderBy('kode_lead')
                ->get();

            $lags = LagMeasure::where('wig_id', $wigId)
                ->where(fn ($q) => $q->whereNull('cabang_id')->orWhere('cabang_id', $cabangId))
                ->orderBy('kode_lag')
                ->get(['id', 'kode_lag', 'nama_lag']);
        }

        return Inertia::render('LeadMeasure/Index', [
            'leads' => $leads,
            'lags' => $lags,
            'wigs' => Wig::query()
                ->when($this->wilayahTerbatas($user), fn ($q, $wilayahId) => $q->where('wilayah_id', $wilayahId))
                ->orderBy('kode_wig')
                ->get(['id', 'kode_wig', 'nama_wig', 'bidang']),
            'cabangs' => $this->cabangTerpilih($user),
            'filter' => [
                'wig_id' => $wigId,
                'cabang_id' => $cabangId,
                'tahun' => $tahun,
            ],
            'terkunciCabang' => (bool) $user?->hasRole('kantor_cabang'),
        ]);
    }

    /**
     * Konteks untuk form tambah/edit: usulan kode dan daftar Lag Measure
     * yang tersedia pada kombinasi WIG + cabang. Dikirim sekaligus supaya
     * memilih WIG/cabang di dalam dialog hanya perlu satu permintaan.
     */
    public function konteks(Request $request): JsonResponse
    {
        $cabang = Cabang::find($request->query('cabang_id'));
        $wig = Wig::find($request->query('wig_id'));
        $tahun = (int) $request->query('tahun', date('Y'));

        if (! $cabang || ! $wig) {
            return response()->json(['kode' => '', 'lags' => []]);
        }

        return response()->json([
            'kode' => $this->buatKode($cabang, $wig, $tahun),
            'lags' => LagMeasure::where('wig_id', $wig->id)
                ->where(fn ($q) => $q->whereNull('cabang_id')->orWhere('cabang_id', $cabang->id))
                ->orderBy('kode_lag')
                ->get(['id', 'kode_lag', 'nama_lag']),
        ]);
    }

    public function store(LeadMeasureRequest $request): RedirectResponse
    {
        LeadMeasure::create($request->validated());

        return back()->with('success', 'Lead Measure berhasil ditambahkan.');
    }

    public function update(LeadMeasureRequest $request, LeadMeasure $leadMeasure): RedirectResponse
    {
        $leadMeasure->update($request->validated());

        return back()->with('success', 'Lead Measure berhasil diperbarui.');
    }

    public function toggle(LeadMeasure $leadMeasure): RedirectResponse
    {
        $leadMeasure->update(['is_active' => ! $leadMeasure->is_active]);

        return back()->with(
            'success',
            $leadMeasure->is_active ? 'Lead Measure diaktifkan.' : 'Lead Measure dinonaktifkan.'
        );
    }

    public function destroy(LeadMeasure $leadMeasure): RedirectResponse
    {
        if ($leadMeasure->realisasis()->exists()) {
            return back()->with('error', 'Lead Measure tidak dapat dihapus karena sudah memiliki data realisasi.');
        }

        $leadMeasure->delete();

        return back()->with('success', 'Lead Measure berhasil dihapus.');
    }

    private function wilayahTerbatas(?User $user): ?int
    {
        if (! $user || $user->hasRole('admin')) {
            return null;
        }

        return $user->wilayah_id;
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

    private function buatKode(Cabang $cabang, Wig $wig, int $tahun): string
    {
        $bidang = strtoupper(trim((string) $wig->bidang)) ?: 'UMUM';
        $dasar = preg_replace('/^KC-?/i', '', $cabang->kode);
        $awalan = 'LEAD-'.$bidang.'-'.$dasar.'-';

        $urutan = LeadMeasure::where('cabang_id', $cabang->id)
            ->where('tahun', $tahun)
            ->where('kode_lead', 'like', $awalan.'%')
            ->count() + 1;

        do {
            $kode = $awalan.str_pad((string) $urutan, 2, '0', STR_PAD_LEFT).'-'.$tahun;
            $urutan++;
        } while (LeadMeasure::where('kode_lead', $kode)->exists());

        return $kode;
    }
}
