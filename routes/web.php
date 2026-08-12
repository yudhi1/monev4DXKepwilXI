<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WilayahController;
use App\Livewire\CabangManagement;
use App\Livewire\Dashboard;
use App\Livewire\IuranMonitoring;
use App\Livewire\KepwilDashboard;
use App\Livewire\LagManagement;
use App\Livewire\LeadManagement;
use App\Livewire\MonevIuran\MonevIuranInput;
use App\Livewire\MonevIuran\SegmenManagement;
use App\Livewire\MonitoringKinerja\ApcDashboard;
use App\Livewire\RealisasiInput;
use App\Livewire\UserManagement;
use App\Livewire\WigManagement;
use App\Livewire\WigRealisasiInput;
use App\Livewire\WigTargetManagement;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => redirect('/dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', function () {
        $u = auth()->user();
        if ($u && $u->hasRole('kedeputian_wilayah') && request('mode') !== 'cabang') {
            return redirect('/dashboard-kepwil');
        }

        return redirect('/dashboard-cabang');
    })->name('dashboard');

    Route::get('/dashboard-cabang', Dashboard::class)->name('dashboard.cabang');
    Route::get('/dashboard-kepwil', KepwilDashboard::class)->name('dashboard.kepwil');

    Route::middleware('role:admin')->group(function () {
        Route::get('/users', UserManagement::class)->name('users');
        // Fase 2 — dimigrasi ke Inertia + Vue. Komponen Livewire lama
        // (App\Livewire\WilayahManagement) sengaja belum dihapus agar mudah dibalik.
        Route::get('/wilayahs', [WilayahController::class, 'index'])->name('wilayahs');
        Route::post('/wilayahs', [WilayahController::class, 'store'])->name('wilayahs.store');
        Route::put('/wilayahs/{wilayah}', [WilayahController::class, 'update'])->name('wilayahs.update');
        Route::delete('/wilayahs/{wilayah}', [WilayahController::class, 'destroy'])->name('wilayahs.destroy');
        Route::get('/cabangs', CabangManagement::class)->name('cabangs');
    });

    Route::middleware('role:admin,kedeputian_wilayah')->group(function () {
        Route::get('/wigs', WigManagement::class)->name('wigs');
        Route::get('/wig-targets', WigTargetManagement::class)->name('wig-targets');
        Route::get('/lag-measures', LagManagement::class)->name('lags');
    });

    Route::middleware('role:admin,kedeputian_wilayah,kantor_cabang')->group(function () {
        Route::get('/laporan', [ReportController::class, 'index'])->name('laporan');
        Route::get('/laporan/excel', [ReportController::class, 'excel'])->name('laporan.excel');
        Route::get('/laporan/pdf', [ReportController::class, 'pdf'])->name('laporan.pdf');
    });

    Route::get('/panduan', fn () => view('panduan'))->name('panduan');

    Route::get('/lead-measures', LeadManagement::class)->name('leads');
    Route::get('/realisasi', RealisasiInput::class)->name('realisasi');

    Route::middleware('role:admin,kedeputian_wilayah,kantor_cabang')->group(function () {
        Route::get('/wig-realisasi', WigRealisasiInput::class)->name('wig-realisasi');
        Route::get('/monitoring-prioritas/iuran', IuranMonitoring::class)->name('monitoring-prioritas.iuran');

        Route::get('/monev-iuran/input', MonevIuranInput::class)->name('monev-iuran.input');
    });

    // Master Segmen — hanya Admin Kepwil / Admin
    Route::middleware('role:admin,kedeputian_wilayah')->group(function () {
        Route::get('/monev-iuran/segmen', SegmenManagement::class)->name('monev-iuran.segmen');
    });

    // Monitoring Kinerja (APC) — dashboard per indikator via upload Excel
    Route::middleware('role:admin,kedeputian_wilayah,kantor_cabang')->group(function () {
        Route::get('/monitoring-kinerja/{indikator}', ApcDashboard::class)
            ->where('indikator', 'total|peserta-aktif|kepuasan|penerimaan-iuran|biaya-manfaat|biaya-operasional')
            ->name('monitoring-kinerja.show');
    });
});

// Fase 1 — halaman uji rakitan Inertia + Vue + shadcn-vue.
// Tidak tertaut di navigasi; hapus setelah migrasi berjalan.
Route::middleware('auth')->get('/_inertia-check', fn () => Inertia::render('InertiaCheck'))
    ->name('inertia.check');
