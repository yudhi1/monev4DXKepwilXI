<?php

use App\Http\Controllers\ApcDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CabangController;
use App\Http\Controllers\DashboardCabangController;
use App\Http\Controllers\DashboardKepwilController;
use App\Http\Controllers\IuranMonitoringController;
use App\Http\Controllers\Kinerja\FileCapaianController as KinerjaFileController;
use App\Http\Controllers\Kinerja\IndikatorController as KinerjaIndikatorController;
use App\Http\Controllers\Kinerja\KategoriController as KinerjaKategoriController;
use App\Http\Controllers\LagMeasureController;
use App\Http\Controllers\LeadMeasureController;
use App\Http\Controllers\ModulController;
use App\Http\Controllers\MonevIuranController;
use App\Http\Controllers\MonevSegmenController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\Pm\DashboardController as PmDashboardController;
use App\Http\Controllers\Pm\MemberController as PmMemberController;
use App\Http\Controllers\Pm\MilestoneController as PmMilestoneController;
use App\Http\Controllers\Pm\ProjectController as PmProjectController;
use App\Http\Controllers\Pm\QuizController as PmQuizController;
use App\Http\Controllers\Pm\QuizPengerjaanController as PmQuizPengerjaanController;
use App\Http\Controllers\Pm\QuizSoalController as PmQuizSoalController;
use App\Http\Controllers\Pm\RingkasanController as PmRingkasanController;
use App\Http\Controllers\Pm\TaskController as PmTaskController;
use App\Http\Controllers\Pm\TugasSayaController as PmTugasSayaController;
use App\Http\Controllers\RealisasiLeadController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UnitKerjaController;
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
            Route::get('/wig-capaian/excel', [WigCapaianController::class, 'excel'])->name('wig-capaian.excel');
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
     | Modul Master Data
     |--------------------------------------------------------------------
     | Berdiri sendiri, bukan bagian 4DX: isinya melayani kedua modul.
     | Dijaga `modul:master` (permission akses-master) dan `role:admin`.
     |
     | URL-nya sengaja tidak diberi prefix agar tautan lama tetap hidup.
     */
    Route::middleware('modul:master')->group(function () {
        /*
         | Akun Project Management boleh dikelola akun 4DX juga, tetapi
         | PegawaiController membatasinya pada bidang di penempatan masing-
         | masing — kantor cabang hanya bidang kantornya, Kedeputian Wilayah
         | hanya bidang tingkat wilayah. Admin menjangkau semuanya.
         */
        Route::middleware('role:admin,kedeputian_wilayah,kantor_cabang')->group(function () {
            Route::get('/pegawai', [PegawaiController::class, 'index'])->name('pegawai');
            Route::get('/pegawai/impor/template', [PegawaiController::class, 'templateImpor'])->name('pegawai.impor.template');
            Route::post('/pegawai/impor', [PegawaiController::class, 'impor'])->name('pegawai.impor');
            Route::post('/pegawai', [PegawaiController::class, 'store'])->name('pegawai.store');
            Route::put('/pegawai/{pegawai}', [PegawaiController::class, 'update'])->name('pegawai.update');
            Route::delete('/pegawai/{pegawai}', [PegawaiController::class, 'destroy'])->name('pegawai.destroy');
        });

        // Sisanya tetap milik admin: akun 4DX dan struktur organisasi.
        Route::middleware('role:admin')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
            Route::get('/wilayahs', [WilayahController::class, 'index'])->name('wilayahs');
            Route::post('/wilayahs', [WilayahController::class, 'store'])->name('wilayahs.store');
            Route::put('/wilayahs/{wilayah}', [WilayahController::class, 'update'])->name('wilayahs.update');
            Route::delete('/wilayahs/{wilayah}', [WilayahController::class, 'destroy'])->name('wilayahs.destroy');
            Route::get('/cabangs', [CabangController::class, 'index'])->name('cabangs');
            Route::post('/cabangs', [CabangController::class, 'store'])->name('cabangs.store');
            Route::put('/cabangs/{cabang}', [CabangController::class, 'update'])->name('cabangs.update');
            Route::delete('/cabangs/{cabang}', [CabangController::class, 'destroy'])->name('cabangs.destroy');

            // Bidang/unit kerja: sebelumnya hanya bisa diubah lewat seeder.
            Route::get('/unit-kerja', [UnitKerjaController::class, 'index'])->name('unit-kerja');
            Route::post('/unit-kerja', [UnitKerjaController::class, 'store'])->name('unit-kerja.store');
            Route::put('/unit-kerja/{unit_kerja}', [UnitKerjaController::class, 'update'])->name('unit-kerja.update');
            Route::delete('/unit-kerja/{unit_kerja}', [UnitKerjaController::class, 'destroy'])->name('unit-kerja.destroy');
        });
    });

    /*
     |--------------------------------------------------------------------
     | Modul Monitoring Kinerja
     |--------------------------------------------------------------------
     | Berprefix /kinerja dengan nama rute `kinerja.`. Dijaga `modul:kinerja`
     | (permission akses-kinerja).
     |
     | Dua lapis hak: melihat dan mengunduh berkas terbuka bagi seluruh role
     | 4DX — itulah gunanya modul ini — sedangkan menyusun kategori, indikator,
     | dan mengunggah berkas hanya Admin dan Kedeputian Wilayah.
     */
    Route::prefix('kinerja')->name('kinerja.')->middleware('modul:kinerja')->group(function () {
        // Terbuka untuk semua pemegang akses modul, termasuk kantor cabang.
        Route::get('/file', [KinerjaFileController::class, 'index'])->name('file');
        Route::get('/file/{file}/unduh', [KinerjaFileController::class, 'unduh'])->name('file.unduh');

        Route::middleware('role:admin,kedeputian_wilayah')->group(function () {
            Route::get('/kategori', [KinerjaKategoriController::class, 'index'])->name('kategori');
            Route::post('/kategori', [KinerjaKategoriController::class, 'store'])->name('kategori.store');
            Route::put('/kategori/{kategori}', [KinerjaKategoriController::class, 'update'])->name('kategori.update');
            Route::delete('/kategori/{kategori}', [KinerjaKategoriController::class, 'destroy'])->name('kategori.destroy');

            Route::get('/indikator', [KinerjaIndikatorController::class, 'index'])->name('indikator');
            Route::post('/indikator', [KinerjaIndikatorController::class, 'store'])->name('indikator.store');
            Route::put('/indikator/{indikator}', [KinerjaIndikatorController::class, 'update'])->name('indikator.update');
            Route::delete('/indikator/{indikator}', [KinerjaIndikatorController::class, 'destroy'])->name('indikator.destroy');

            Route::post('/file', [KinerjaFileController::class, 'store'])->name('file.store');
            Route::put('/file/{file}', [KinerjaFileController::class, 'update'])->name('file.update');
            Route::delete('/file/{file}', [KinerjaFileController::class, 'destroy'])->name('file.destroy');
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
        Route::get('/tugas-saya/ekspor', [PmTugasSayaController::class, 'ekspor'])->name('tugas-saya.ekspor');

        // Rincian di balik kartu statistik dashboard.
        Route::get('/ringkasan', [PmRingkasanController::class, 'index'])->name('ringkasan');

        Route::get('/projects', [PmProjectController::class, 'index'])->name('projects');
        Route::post('/projects', [PmProjectController::class, 'store'])->name('projects.store');
        Route::get('/projects/{project}', [PmProjectController::class, 'show'])->name('projects.show');
        Route::put('/projects/{project}', [PmProjectController::class, 'update'])->name('projects.update');
        Route::delete('/projects/{project}', [PmProjectController::class, 'destroy'])->name('projects.destroy');

        /*
         | Quiz. Membuat dan menyusun soal dijaga QuizPolicy (permission
         | `pm.quiz.kelola`, hanya Project Manager); mengerjakan terbuka bagi
         | semua pengguna modul.
         |
         | Rute percobaan sengaja tidak bersarang di bawah {quiz}: sebuah
         | percobaan sudah menunjuk quiz-nya sendiri, dan menyalin id quiz ke
         | URL hanya menambah satu hal lagi yang bisa tidak cocok.
         */
        Route::get('/quiz', [PmQuizController::class, 'index'])->name('quiz.index');
        Route::post('/quiz', [PmQuizController::class, 'store'])->name('quiz.store');
        Route::get('/quiz/{quiz}/kelola', [PmQuizController::class, 'kelola'])->name('quiz.kelola');
        Route::put('/quiz/{quiz}', [PmQuizController::class, 'update'])->name('quiz.update');
        Route::patch('/quiz/{quiz}/status', [PmQuizController::class, 'status'])->name('quiz.status');
        Route::delete('/quiz/{quiz}', [PmQuizController::class, 'destroy'])->name('quiz.destroy');
        Route::get('/quiz/{quiz}/peringkat', [PmQuizController::class, 'peringkat'])->name('quiz.peringkat');
        Route::get('/quiz/{quiz}/peringkat/ekspor', [PmQuizController::class, 'ekspor'])->name('quiz.ekspor');

        Route::post('/quiz/{quiz}/soal', [PmQuizSoalController::class, 'store'])->name('quiz.soal.store');
        Route::put('/quiz/{quiz}/soal/{soal}', [PmQuizSoalController::class, 'update'])->name('quiz.soal.update');
        Route::delete('/quiz/{quiz}/soal/{soal}', [PmQuizSoalController::class, 'destroy'])->name('quiz.soal.destroy');

        Route::post('/quiz/{quiz}/mulai', [PmQuizPengerjaanController::class, 'mulai'])->name('quiz.mulai');
        Route::get('/quiz/percobaan/{percobaan}', [PmQuizPengerjaanController::class, 'kerjakan'])->name('quiz.kerjakan');
        Route::patch('/quiz/percobaan/{percobaan}/jawab', [PmQuizPengerjaanController::class, 'jawab'])->name('quiz.jawab');
        Route::post('/quiz/percobaan/{percobaan}/selesai', [PmQuizPengerjaanController::class, 'selesai'])->name('quiz.selesai');
        Route::get('/quiz/percobaan/{percobaan}/hasil', [PmQuizPengerjaanController::class, 'hasil'])->name('quiz.hasil');

        Route::prefix('projects/{project}')->group(function () {
            Route::post('/tasks', [PmTaskController::class, 'store'])->name('tasks.store');
            Route::post('/tasks/massal', [PmTaskController::class, 'storeMassal'])->name('tasks.massal');
            Route::put('/tasks/{task}', [PmTaskController::class, 'update'])->name('tasks.update');
            Route::patch('/tasks/{task}/pindah', [PmTaskController::class, 'pindah'])->name('tasks.pindah');
            Route::patch('/tasks/{task}/progress', [PmTaskController::class, 'progress'])->name('tasks.progress');
            Route::delete('/tasks/{task}', [PmTaskController::class, 'destroy'])->name('tasks.destroy');

            // Milestone opsional: project tanpa milestone tetap berjalan normal.
            Route::post('/milestones', [PmMilestoneController::class, 'store'])->name('milestones.store');
            Route::put('/milestones/{milestone}', [PmMilestoneController::class, 'update'])->name('milestones.update');
            Route::delete('/milestones/{milestone}', [PmMilestoneController::class, 'destroy'])->name('milestones.destroy');

            Route::post('/anggota', [PmMemberController::class, 'store'])->name('anggota.store');
            Route::put('/anggota/{anggota}', [PmMemberController::class, 'update'])->name('anggota.update');
            Route::delete('/anggota/{anggota}', [PmMemberController::class, 'destroy'])->name('anggota.destroy');
        });
    });
});
