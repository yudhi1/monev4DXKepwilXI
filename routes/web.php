<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use App\Livewire\Dashboard;
use App\Livewire\KepwilDashboard;
use App\Livewire\UserManagement;
use App\Livewire\WilayahManagement;
use App\Livewire\CabangManagement;
use App\Livewire\WigManagement;
use App\Livewire\WigTargetManagement;
use App\Livewire\LagManagement;
use App\Livewire\LeadManagement;
use App\Livewire\RealisasiInput;
use App\Livewire\WigRealisasiInput;
use App\Livewire\IuranMonitoring;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/dashboard'));

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
        Route::get('/wilayahs', WilayahManagement::class)->name('wilayahs');
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

    Route::get('/panduan', fn() => view('panduan'))->name('panduan');

    Route::get('/lead-measures', LeadManagement::class)->name('leads');
    Route::get('/realisasi', RealisasiInput::class)->name('realisasi');

    Route::middleware('role:admin,kedeputian_wilayah,kantor_cabang')->group(function () {
        Route::get('/wig-realisasi', WigRealisasiInput::class)->name('wig-realisasi');
        Route::get('/monitoring-prioritas/iuran', IuranMonitoring::class)->name('monitoring-prioritas.iuran');
    });
});
