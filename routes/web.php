<?php

use App\Http\Controllers\ApcDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CabangController;
use App\Http\Controllers\DashboardCabangController;
use App\Http\Controllers\DashboardKepwilController;
use App\Http\Controllers\IuranMonitoringController;
use App\Http\Controllers\LagMeasureController;
use App\Http\Controllers\LeadMeasureController;
use App\Http\Controllers\ModulController;
use App\Http\Controllers\MonevIuranController;
use App\Http\Controllers\MonevSegmenController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\Pm\DashboardController as PmDashboardController;
use App\Http\Controllers\Pm\MemberController as PmMemberController;
use App\Http\Controllers\Pm\ProjectController as PmProjectController;
use App\Http\Controllers\Pm\TaskController as PmTaskController;
use App\Http\Controllers\Pm\TugasSayaController as PmTugasSayaController;
use App\Http\Controllers\RealisasiLeadController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WigCapaianController;
use App\Http\Controllers\WigController;
use App\Http\Controllers\WilayahController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => redirect('/apps'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Pemilih modul. User yang hanya berhak atas satu modul langsung dialihkan.
    Route::get('/apps', [ModulController::class, 'index'])->name('apps');

    /*
     |--------------------------------------------------------------------
     | Modul Monev 4DX
     |--------------------------------------------------------------------
     | Seluruhnya dijaga `modul:4dx` (permission akses-4dx). Tanpa ini,
     | halaman yang rutenya kebetulan tidak memakai middleware `role:` —
     | seperti /dashboard-cabang dan /panduan — akan terbuka untuk siapa pun
     | yang login, termasuk pegawai yang hanya berhak atas modul PM.
     |
     | URL-nya masih di root; pemindahan ke /4dx ditunda (lihat
     | docs/RENCANA_RESTRUKTURISASI_MULTI_MODUL.md, Fase 2).
     */
    Route::middleware('modul:4dx')->group(function () {
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
            // Akun institusi — pengguna modul Monev 4DX.
            Route::get('/users', [UserController::class, 'index'])->name('users');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

            // Akun perorangan — pengguna modul Project Management.
            Route::get('/pegawai', [PegawaiController::class, 'index'])->name('pegawai');
            Route::get('/pegawai/impor/template', [PegawaiController::class, 'templateImpor'])->name('pegawai.impor.template');
            Route::post('/pegawai/impor', [PegawaiController::class, 'impor'])->name('pegawai.impor');
            Route::post('/pegawai', [PegawaiController::class, 'store'])->name('pegawai.store');
            Route::put('/pegawai/{pegawai}', [PegawaiController::class, 'update'])->name('pegawai.update');
            Route::delete('/pegawai/{pegawai}', [PegawaiController::class, 'destroy'])->name('pegawai.destroy');
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

            // Target WIG kini menyatu dengan realisasinya di /wig-capaian.
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

        Route::get('/panduan', fn () => Inertia::render('Panduan'))->name('panduan');

        Route::get('/lead-measures', [LeadMeasureController::class, 'index'])->name('leads');
        Route::get('/lead-measures/konteks', [LeadMeasureController::class, 'konteks'])->name('leads.konteks');
        Route::post('/lead-measures', [LeadMeasureController::class, 'store'])->name('leads.store');
        Route::put('/lead-measures/{lead_measure}', [LeadMeasureController::class, 'update'])->name('leads.update');
        Route::patch('/lead-measures/{lead_measure}/toggle', [LeadMeasureController::class, 'toggle'])->name('leads.toggle');
        Route::delete('/lead-measures/{lead_measure}', [LeadMeasureController::class, 'destroy'])->name('leads.destroy');
        Route::get('/realisasi', [RealisasiLeadController::class, 'index'])->name('realisasi');
        Route::post('/realisasi', [RealisasiLeadController::class, 'store'])->name('realisasi.store');

        Route::middleware('role:admin,kedeputian_wilayah,kantor_cabang')->group(function () {
            Route::get('/wig-capaian', [WigCapaianController::class, 'index'])->name('wig-capaian');
            Route::post('/wig-capaian', [WigCapaianController::class, 'store'])->name('wig-capaian.store');
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
            Route::prefix('monitoring-kinerja/{indikator}')
                ->where(['indikator' => 'total|peserta-aktif|kepuasan|penerimaan-iuran|biaya-manfaat|biaya-operasional'])
                ->group(function () {
                    Route::get('/', [ApcDashboardController::class, 'index'])->name('monitoring-kinerja.show');
                    Route::post('/upload', [ApcDashboardController::class, 'upload'])->name('monitoring-kinerja.upload');
                    Route::get('/{upload}/unduh', [ApcDashboardController::class, 'unduh'])->name('monitoring-kinerja.unduh');
                    Route::delete('/{upload}', [ApcDashboardController::class, 'destroy'])->name('monitoring-kinerja.destroy');
                });
        });
    });

    /*
     |--------------------------------------------------------------------
     | Modul Project Management
     |--------------------------------------------------------------------
     | Berprefix /pm dengan nama rute `pm.` agar tidak bentrok dengan modul
     | 4DX yang untuk sementara masih berada di root (lihat
     | docs/RENCANA_RESTRUKTURISASI_MULTI_MODUL.md, Fase 2 ditunda).
     |
     | Hak akses berlapis: middleware `modul:pm` menyaring siapa yang boleh
     | masuk modul sama sekali, sedangkan siapa boleh apa di dalam sebuah
     | project ditentukan ProjectPolicy.
     */
    Route::prefix('pm')->name('pm.')->middleware('modul:pm')->group(function () {
        Route::get('/', [PmDashboardController::class, 'index'])->name('dashboard');
        Route::get('/tugas-saya', [PmTugasSayaController::class, 'index'])->name('tugas-saya');

        Route::get('/projects', [PmProjectController::class, 'index'])->name('projects');
        Route::post('/projects', [PmProjectController::class, 'store'])->name('projects.store');
        Route::get('/projects/{project}', [PmProjectController::class, 'show'])->name('projects.show');
        Route::put('/projects/{project}', [PmProjectController::class, 'update'])->name('projects.update');
        Route::delete('/projects/{project}', [PmProjectController::class, 'destroy'])->name('projects.destroy');

        Route::prefix('projects/{project}')->group(function () {
            Route::post('/tasks', [PmTaskController::class, 'store'])->name('tasks.store');
            Route::put('/tasks/{task}', [PmTaskController::class, 'update'])->name('tasks.update');
            Route::patch('/tasks/{task}/pindah', [PmTaskController::class, 'pindah'])->name('tasks.pindah');
            Route::patch('/tasks/{task}/progress', [PmTaskController::class, 'progress'])->name('tasks.progress');
            Route::delete('/tasks/{task}', [PmTaskController::class, 'destroy'])->name('tasks.destroy');

            Route::post('/anggota', [PmMemberController::class, 'store'])->name('anggota.store');
            Route::put('/anggota/{anggota}', [PmMemberController::class, 'update'])->name('anggota.update');
            Route::delete('/anggota/{anggota}', [PmMemberController::class, 'destroy'])->name('anggota.destroy');
        });
    });
});
