<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CabangController;
use App\Http\Controllers\DashboardCabangController;
use App\Http\Controllers\DashboardKepwilController;
use App\Http\Controllers\IuranMonitoringController;
use App\Http\Controllers\LagMeasureController;
use App\Http\Controllers\LeadMeasureController;
use App\Http\Controllers\MonevIuranController;
use App\Http\Controllers\MonevSegmenController;
use App\Http\Controllers\RealisasiLeadController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WigController;
use App\Http\Controllers\WigRealisasiController;
use App\Http\Controllers\WigTargetController;
use App\Http\Controllers\WilayahController;
use App\Livewire\MonitoringKinerja\ApcDashboard;
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

    Route::get('/dashboard-cabang', [DashboardCabangController::class, 'index'])->name('dashboard.cabang');
    Route::get('/dashboard-kepwil', [DashboardKepwilController::class, 'index'])->name('dashboard.kepwil');

    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        // Fase 2 — dimigrasi ke Inertia + Vue. Komponen Livewire lama
        // (App\Livewire\WilayahManagement) sengaja belum dihapus agar mudah dibalik.
        Route::get('/wilayahs', [WilayahController::class, 'index'])->name('wilayahs');
        Route::post('/wilayahs', [WilayahController::class, 'store'])->name('wilayahs.store');
        Route::put('/wilayahs/{wilayah}', [WilayahController::class, 'update'])->name('wilayahs.update');
        Route::delete('/wilayahs/{wilayah}', [WilayahController::class, 'destroy'])->name('wilayahs.destroy');
        Route::get('/cabangs', [CabangController::class, 'index'])->name('cabangs');
        Route::post('/cabangs', [CabangController::class, 'store'])->name('cabangs.store');
        Route::put('/cabangs/{cabang}', [CabangController::class, 'update'])->name('cabangs.update');
        Route::delete('/cabangs/{cabang}', [CabangController::class, 'destroy'])->name('cabangs.destroy');
    });

    Route::middleware('role:admin,kedeputian_wilayah')->group(function () {
        Route::get('/wigs', [WigController::class, 'index'])->name('wigs');
        Route::post('/wigs', [WigController::class, 'store'])->name('wigs.store');
        Route::put('/wigs/{wig}', [WigController::class, 'update'])->name('wigs.update');
        Route::delete('/wigs/{wig}', [WigController::class, 'destroy'])->name('wigs.destroy');

        Route::get('/wig-targets', [WigTargetController::class, 'index'])->name('wig-targets');
        Route::post('/wig-targets', [WigTargetController::class, 'store'])->name('wig-targets.store');
        Route::get('/lag-measures', [LagMeasureController::class, 'index'])->name('lags');
        Route::get('/lag-measures/kode', [LagMeasureController::class, 'kodeSaran'])->name('lags.kode');
        Route::post('/lag-measures', [LagMeasureController::class, 'store'])->name('lags.store');
        Route::put('/lag-measures/{lag_measure}', [LagMeasureController::class, 'update'])->name('lags.update');
        Route::delete('/lag-measures/{lag_measure}', [LagMeasureController::class, 'destroy'])->name('lags.destroy');
    });

    Route::middleware('role:admin,kedeputian_wilayah,kantor_cabang')->group(function () {
        Route::get('/laporan', [ReportController::class, 'index'])->name('laporan');
        Route::get('/laporan/excel', [ReportController::class, 'excel'])->name('laporan.excel');
        Route::get('/laporan/pdf', [ReportController::class, 'pdf'])->name('laporan.pdf');
    });

    Route::get('/panduan', fn () => view('panduan'))->name('panduan');

    Route::get('/lead-measures', [LeadMeasureController::class, 'index'])->name('leads');
    Route::get('/lead-measures/kode', [LeadMeasureController::class, 'kodeSaran'])->name('leads.kode');
    Route::post('/lead-measures', [LeadMeasureController::class, 'store'])->name('leads.store');
    Route::put('/lead-measures/{lead_measure}', [LeadMeasureController::class, 'update'])->name('leads.update');
    Route::patch('/lead-measures/{lead_measure}/toggle', [LeadMeasureController::class, 'toggle'])->name('leads.toggle');
    Route::delete('/lead-measures/{lead_measure}', [LeadMeasureController::class, 'destroy'])->name('leads.destroy');
    Route::get('/realisasi', [RealisasiLeadController::class, 'index'])->name('realisasi');
    Route::post('/realisasi', [RealisasiLeadController::class, 'store'])->name('realisasi.store');

    Route::middleware('role:admin,kedeputian_wilayah,kantor_cabang')->group(function () {
        Route::get('/wig-realisasi', [WigRealisasiController::class, 'index'])->name('wig-realisasi');
        Route::post('/wig-realisasi', [WigRealisasiController::class, 'store'])->name('wig-realisasi.store');
        Route::get('/monitoring-prioritas/iuran', [IuranMonitoringController::class, 'index'])->name('monitoring-prioritas.iuran');
        Route::get('/monitoring-prioritas/iuran/excel', [IuranMonitoringController::class, 'excel'])->name('monitoring-prioritas.iuran.excel');
        Route::get('/monitoring-prioritas/iuran/pdf', [IuranMonitoringController::class, 'pdf'])->name('monitoring-prioritas.iuran.pdf');
        Route::post('/monitoring-prioritas/iuran', [IuranMonitoringController::class, 'store'])->name('monitoring-prioritas.iuran.store');
        Route::put('/monitoring-prioritas/iuran/{iuran}', [IuranMonitoringController::class, 'update'])->name('monitoring-prioritas.iuran.update');
        Route::delete('/monitoring-prioritas/iuran/{iuran}', [IuranMonitoringController::class, 'destroy'])->name('monitoring-prioritas.iuran.destroy');

        Route::get('/monev-iuran/input', [MonevIuranController::class, 'index'])->name('monev-iuran.input');
        Route::post('/monev-iuran/input', [MonevIuranController::class, 'store'])->name('monev-iuran.store');
        Route::post('/monev-iuran/kunci', [MonevIuranController::class, 'kunci'])->name('monev-iuran.kunci');
        Route::post('/monev-iuran/buka-kunci', [MonevIuranController::class, 'bukaKunci'])->name('monev-iuran.buka-kunci');
    });

    // Master Segmen — hanya Admin Kepwil / Admin
    Route::middleware('role:admin,kedeputian_wilayah')->group(function () {
        Route::get('/monev-iuran/segmen', [MonevSegmenController::class, 'index'])->name('monev-iuran.segmen');
        Route::post('/monev-iuran/segmen', [MonevSegmenController::class, 'store'])->name('monev-iuran.segmen.store');
        Route::put('/monev-iuran/segmen/{segmen}', [MonevSegmenController::class, 'update'])->name('monev-iuran.segmen.update');
        Route::delete('/monev-iuran/segmen/{segmen}', [MonevSegmenController::class, 'destroy'])->name('monev-iuran.segmen.destroy');
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
