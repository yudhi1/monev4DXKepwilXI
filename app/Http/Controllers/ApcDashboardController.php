<?php

namespace App\Http\Controllers;

use App\Imports\GenericSheetImport;
use App\Models\MonevApcUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Monitoring Kinerja (APC). Datanya tidak diinput manual, melainkan
 * diunggah sebagai berkas Excel lalu ditampilkan apa adanya.
 */
class ApcDashboardController extends Controller
{
    public function index(Request $request, string $indikator): Response
    {
        abort_unless(array_key_exists($indikator, MonevApcUpload::INDIKATOR), 404);

        $terbaru = MonevApcUpload::where('indikator', $indikator)->latest()->first();

        $riwayat = MonevApcUpload::where('indikator', $indikator)
            ->with('uploader:id,name')
            ->latest()
            ->take(15)
            ->get()
            ->map(fn (MonevApcUpload $upload) => [
                'id' => $upload->id,
                'original_name' => $upload->original_name,
                'tahun' => $upload->tahun,
                'rows_count' => $upload->rows_count,
                'uploader' => $upload->uploader?->name,
                'diunggah' => $upload->created_at?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('MonitoringKinerja/Apc', [
            'indikator' => $indikator,
            'label' => MonevApcUpload::INDIKATOR[$indikator],
            'daftarIndikator' => collect(MonevApcUpload::INDIKATOR)
                ->map(fn ($nama, $slug) => ['slug' => $slug, 'nama' => $nama])
                ->values(),
            'terbaru' => $terbaru ? [
                'id' => $terbaru->id,
                'original_name' => $terbaru->original_name,
                'tahun' => $terbaru->tahun,
                'rows_count' => $terbaru->rows_count,
                'diunggah' => $terbaru->created_at?->format('d/m/Y H:i'),
                'baris' => $this->rapikan($terbaru->sheet_json ?? []),
            ] : null,
            'riwayat' => $riwayat,
            'bisaKelola' => $this->bisaKelola($request),
            'tahunBawaan' => (int) date('Y'),
        ]);
    }

    public function upload(Request $request, string $indikator): RedirectResponse
    {
        abort_unless(array_key_exists($indikator, MonevApcUpload::INDIKATOR), 404);

        if (! $this->bisaKelola($request)) {
            return back()->with('error', 'Anda tidak punya akses untuk mengupload data.');
        }

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ], [], ['file' => 'file Excel']);

        $sheet = Excel::toArray(new GenericSheetImport, $data['file'])[0] ?? [];

        MonevApcUpload::create([
            'indikator' => $indikator,
            'tahun' => $data['tahun'] ?? (int) date('Y'),
            'original_name' => $data['file']->getClientOriginalName(),
            'file_path' => $data['file']->store('apc-uploads'),
            'sheet_json' => $sheet,
            'rows_count' => max(0, count($sheet) - 1),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'File Excel berhasil diupload.');
    }

    public function unduh(string $indikator, MonevApcUpload $upload)
    {
        abort_unless($upload->indikator === $indikator, 404);
        abort_unless(Storage::exists($upload->file_path), 404);

        return Storage::download($upload->file_path, $upload->original_name);
    }

    public function destroy(Request $request, string $indikator, MonevApcUpload $upload): RedirectResponse
    {
        abort_unless($upload->indikator === $indikator, 404);

        if (! $this->bisaKelola($request)) {
            return back()->with('error', 'Anda tidak punya akses untuk menghapus data.');
        }

        Storage::delete($upload->file_path);
        $upload->delete();

        return back()->with('success', 'Data upload berhasil dihapus.');
    }

    /**
     * Menyamakan panjang tiap baris dengan baris terlebar supaya tabel di
     * sisi Vue tidak perlu menebak jumlah kolomnya.
     */
    private function rapikan(array $sheet): array
    {
        if ($sheet === []) {
            return [];
        }

        $lebar = max(array_map('count', $sheet));

        return array_map(
            fn (array $baris) => array_map(
                fn ($sel) => $sel === null ? '' : (string) $sel,
                array_pad($baris, $lebar, '')
            ),
            $sheet
        );
    }

    private function bisaKelola(Request $request): bool
    {
        return $request->user()?->hasAnyRole(['admin', 'kedeputian_wilayah']) ?? false;
    }
}
