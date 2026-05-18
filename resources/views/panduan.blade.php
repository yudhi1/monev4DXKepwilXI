@extends('layouts.app')

@section('content')
<style>
    .panduan-hero {
        background: linear-gradient(120deg, #006837 0%, #009E60 30%, #00A99D 60%, #0086C9 85%, #1B4F8F 100%);
        border-radius: 16px;
        color: #fff;
        padding: 2.5rem 2rem;
        margin-bottom: 2rem;
    }
    .panduan-hero h1 { font-weight: 800; font-size: 2rem; }
    .panduan-hero p { opacity: .88; max-width: 720px; }
    .step-badge {
        width: 40px; height: 40px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 1.1rem; flex-shrink: 0;
    }
    .discipline-card {
        border: none; border-radius: 14px;
        box-shadow: 0 3px 14px rgba(0,0,0,.07);
        transition: transform .15s, box-shadow .15s;
        height: 100%;
    }
    .discipline-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.12); }
    .discipline-icon { font-size: 2.2rem; }
    .flow-arrow { font-size: 1.8rem; color: #adb5bd; text-align: center; padding: .25rem 0; }
    .section-title { font-weight: 700; color: #1B4F8F; border-left: 4px solid #00A99D; padding-left: .75rem; margin-bottom: 1.25rem; }
    .role-row td { vertical-align: middle; }
</style>

<div class="panduan-hero">
    <div class="d-flex align-items-center gap-3 mb-3">
        <span style="font-size:2.8rem">🎯</span>
        <div>
            <h1 class="mb-1">Panduan Monitoring Kinerja Berbasis 4DX</h1>
            <p class="mb-0">4 Disciplines of Execution — kerangka kerja eksekusi strategi yang memastikan seluruh unit fokus pada hal yang paling penting dan mengukur kemajuan secara konsisten.</p>
        </div>
    </div>
    <span class="badge" style="background:rgba(255,255,255,.22); font-size:.85rem; padding:.45em .9em; border-radius:999px">
        <i class="bi bi-building"></i> Kedeputian Wilayah XI — BPJS Kesehatan
    </span>
</div>

{{-- APA ITU 4DX --}}
<div class="row g-4 mb-4">
    <div class="col-12">
        <h5 class="section-title"><i class="bi bi-info-circle"></i> Apa itu 4DX?</h5>
        <div class="card border-0 shadow-sm" style="border-radius:14px">
            <div class="card-body p-4">
                <p class="mb-3">
                    <strong>4 Disciplines of Execution (4DX)</strong> adalah metodologi manajemen eksekusi yang dikembangkan oleh FranklinCovey, dirancang untuk membantu organisasi mengeksekusi strategi terpenting mereka secara konsisten — di tengah pusaran aktivitas operasional sehari-hari (<em>the whirlwind</em>).
                </p>
                <div class="alert alert-info mb-0 d-flex gap-2" style="border-radius:10px">
                    <i class="bi bi-lightbulb-fill fs-5 text-warning mt-1"></i>
                    <div>
                        <strong>Prinsip utama:</strong> Strategi yang hebat tidak otomatis menghasilkan eksekusi yang hebat. 4DX menjembatani kesenjangan antara <em>perencanaan</em> dan <em>hasil nyata</em> melalui 4 disiplin yang berurutan dan saling menguatkan.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 4 DISIPLIN --}}
<div class="row g-4 mb-4">
    <div class="col-12"><h5 class="section-title"><i class="bi bi-layers"></i> 4 Disiplin Utama</h5></div>

    <div class="col-md-6 col-xl-3">
        <div class="card discipline-card">
            <div class="card-body p-4">
                <div class="discipline-icon mb-2">🎯</div>
                <span class="badge bg-primary mb-2">Disiplin 1</span>
                <h6 class="fw-bold">Focus on the Wildly Important Goal (WIG)</h6>
                <p class="small text-muted mb-3">Fokus pada satu atau dua tujuan terpenting yang jika tercapai akan memberikan dampak terbesar bagi organisasi, bukan pada semua tujuan sekaligus.</p>
                <ul class="small ps-3 mb-0 text-muted">
                    <li>Setiap wilayah/cabang memiliki WIG yang jelas</li>
                    <li>Dinyatakan dengan format: "Dari X menjadi Y pada tanggal Z"</li>
                    <li>Disetujui bersama antara pimpinan dan tim</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card discipline-card">
            <div class="card-body p-4">
                <div class="discipline-icon mb-2">📊</div>
                <span class="badge bg-success mb-2">Disiplin 2</span>
                <h6 class="fw-bold">Act on the Lead Measures</h6>
                <p class="small text-muted mb-3">Mengidentifikasi dan mengukur aktivitas-aktivitas kunci (Lead Measure) yang secara langsung mendorong tercapainya WIG.</p>
                <ul class="small ps-3 mb-0 text-muted">
                    <li><strong>Lag Measure:</strong> hasil akhir (WIG) — bisa diukur tapi sulit dipengaruhi langsung</li>
                    <li><strong>Lead Measure:</strong> aktivitas pendorong — bisa dikendalikan tim setiap minggu</li>
                    <li>Diisi realisasinya tiap minggu oleh Kantor Cabang</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card discipline-card">
            <div class="card-body p-4">
                <div class="discipline-icon mb-2">📋</div>
                <span class="badge bg-warning text-dark mb-2">Disiplin 3</span>
                <h6 class="fw-bold">Keep a Compelling Scoreboard</h6>
                <p class="small text-muted mb-3">Papan skor yang menarik dan mudah dibaca agar tim selalu tahu posisi mereka — apakah sedang menang atau kalah.</p>
                <ul class="small ps-3 mb-0 text-muted">
                    <li>Dashboard Kepwil: ranking & capaian seluruh cabang</li>
                    <li>Dashboard Cabang: performa individual per WIG</li>
                    <li>Indikator status: On Track 🟢 / Waspada 🟡 / Awas 🔴</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card discipline-card">
            <div class="card-body p-4">
                <div class="discipline-icon mb-2">🔄</div>
                <span class="badge bg-danger mb-2">Disiplin 4</span>
                <h6 class="fw-bold">Create a Cadence of Accountability</h6>
                <p class="small text-muted mb-3">Sesi WIG mingguan berdurasi singkat (15–20 menit) di mana setiap anggota tim melaporkan komitmen minggu lalu dan menetapkan komitmen baru.</p>
                <ul class="small ps-3 mb-0 text-muted">
                    <li>Review capaian Lead Measure tiap minggu</li>
                    <li>Identifikasi kendala dan rencana tindak lanjut</li>
                    <li>Komitmen individu — bukan hanya laporan angka</li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- ALUR MONEV --}}
<div class="row g-4 mb-4">
    <div class="col-12">
        <h5 class="section-title"><i class="bi bi-diagram-3"></i> Alur Monitoring di Sistem Ini</h5>
        <div class="card border-0 shadow-sm" style="border-radius:14px">
            <div class="card-body p-4">
                <div class="row g-0 align-items-center text-center">
                    <div class="col">
                        <div class="p-3 rounded-3" style="background:#e8f4fd">
                            <div class="fs-3 mb-1">🏛️</div>
                            <div class="fw-bold small">Kedeputian Wilayah</div>
                            <div class="text-muted" style="font-size:.75rem">Buat WIG & Lag Measure</div>
                        </div>
                    </div>
                    <div class="col-auto px-2"><div class="flow-arrow">→</div></div>
                    <div class="col">
                        <div class="p-3 rounded-3" style="background:#e8fdf4">
                            <div class="fs-3 mb-1">📌</div>
                            <div class="fw-bold small">Lead Measure</div>
                            <div class="text-muted" style="font-size:.75rem">Aktivitas mingguan per cabang</div>
                        </div>
                    </div>
                    <div class="col-auto px-2"><div class="flow-arrow">→</div></div>
                    <div class="col">
                        <div class="p-3 rounded-3" style="background:#fff8e1">
                            <div class="fs-3 mb-1">✏️</div>
                            <div class="fw-bold small">Kantor Cabang</div>
                            <div class="text-muted" style="font-size:.75rem">Input realisasi tiap minggu</div>
                        </div>
                    </div>
                    <div class="col-auto px-2"><div class="flow-arrow">→</div></div>
                    <div class="col">
                        <div class="p-3 rounded-3" style="background:#fdeaea">
                            <div class="fs-3 mb-1">📈</div>
                            <div class="fw-bold small">Dashboard</div>
                            <div class="text-muted" style="font-size:.75rem">Capaian & ranking real-time</div>
                        </div>
                    </div>
                    <div class="col-auto px-2"><div class="flow-arrow">→</div></div>
                    <div class="col">
                        <div class="p-3 rounded-3" style="background:#ede8fd">
                            <div class="fs-3 mb-1">🔄</div>
                            <div class="fw-bold small">WIG Session</div>
                            <div class="text-muted" style="font-size:.75rem">Review & komitmen mingguan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- STRUKTUR DATA & STATUS --}}
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <h5 class="section-title"><i class="bi bi-table"></i> Struktur Data Monitoring</h5>
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px">
            <div class="card-body p-0">
                <table class="table table-sm align-middle mb-0" style="font-size:.88rem">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Entitas</th>
                            <th>Keterangan</th>
                            <th>Contoh</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="role-row">
                            <td class="ps-3 fw-semibold text-primary">WIG</td>
                            <td>Wildly Important Goal — tujuan utama tahunan</td>
                            <td class="text-muted small">Meningkatkan peserta aktif dari 500K ke 600K</td>
                        </tr>
                        <tr class="role-row">
                            <td class="ps-3 fw-semibold text-success">Lag Measure</td>
                            <td>Indikator hasil — mengukur seberapa jauh WIG tercapai</td>
                            <td class="text-muted small">Jumlah peserta aktif terdaftar</td>
                        </tr>
                        <tr class="role-row">
                            <td class="ps-3 fw-semibold text-warning">Lead Measure</td>
                            <td>Aktivitas pendorong — dikendalikan tim setiap minggu</td>
                            <td class="text-muted small">Kunjungan perusahaan per minggu</td>
                        </tr>
                        <tr class="role-row">
                            <td class="ps-3 fw-semibold text-danger">Realisasi</td>
                            <td>Nilai aktual Lead Measure yang dicapai pada minggu tersebut</td>
                            <td class="text-muted small">12 kunjungan (dari target 15)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <h5 class="section-title"><i class="bi bi-traffic-light"></i> Indikator Status Capaian</h5>
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px">
            <div class="card-body p-4">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background:#d1f0e0">
                        <span class="step-badge bg-success text-white">🟢</span>
                        <div>
                            <div class="fw-bold text-success">On Track</div>
                            <div class="small text-muted">Capaian ≥ 100% — target tercapai atau terlampaui</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background:#fff8e1">
                        <span class="step-badge bg-warning">🟡</span>
                        <div>
                            <div class="fw-bold text-warning">Waspada</div>
                            <div class="small text-muted">Capaian 90% – &lt;100% — perlu perhatian segera</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background:#fde8e8">
                        <span class="step-badge bg-danger text-white">🔴</span>
                        <div>
                            <div class="fw-bold text-danger">Awas</div>
                            <div class="small text-muted">Capaian &lt;90% — intervensi diperlukan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- HAK AKSES --}}
<div class="row g-4 mb-4">
    <div class="col-12">
        <h5 class="section-title"><i class="bi bi-people"></i> Hak Akses per Role</h5>
        <div class="card border-0 shadow-sm" style="border-radius:14px">
            <div class="card-body p-0">
                <table class="table table-sm align-middle mb-0" style="font-size:.88rem">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width:160px">Role</th>
                            <th>Yang Bisa Dilakukan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="role-row">
                            <td class="ps-3"><span class="badge bg-dark">Admin</span></td>
                            <td>CRUD semua data, kelola user & wilayah, monitoring seluruh wilayah & cabang, export laporan</td>
                        </tr>
                        <tr class="role-row">
                            <td class="ps-3"><span class="badge bg-primary">Kedeputian Wilayah</span></td>
                            <td>Buat & kelola WIG, Lag Measure, Lead Measure; monitoring capaian seluruh cabang di wilayahnya; export laporan</td>
                        </tr>
                        <tr class="role-row">
                            <td class="ps-3"><span class="badge bg-success">Kantor Cabang</span></td>
                            <td>Input realisasi Lead Measure mingguan; lihat dashboard performa cabang sendiri; export laporan cabang</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- TIPS --}}
<div class="row g-4 mb-2">
    <div class="col-12">
        <h5 class="section-title"><i class="bi bi-patch-check"></i> Tips Eksekusi 4DX yang Efektif</h5>
        <div class="row g-3">
            @foreach ([
                ['bi-alarm','warning','Konsisten Setiap Minggu','Input realisasi Lead Measure tepat waktu setiap minggu. Data yang telat membuat dashboard tidak akurat dan WIG Session tidak efektif.'],
                ['bi-chat-dots','info','WIG Session yang Singkat & Fokus','Lakukan WIG Session maksimal 20 menit: (1) laporkan komitmen minggu lalu, (2) update scoreboard, (3) buat komitmen minggu ini.'],
                ['bi-bar-chart-line','success','Pisahkan WIG dari Whirlwind','Jangan biarkan aktivitas operasional harian menggeser WIG. Jadwalkan waktu khusus untuk mengerjakan Lead Measure.'],
                ['bi-graph-up-arrow','primary','Fokus pada Lead, Bukan Lag','Lag Measure adalah hasil — tidak bisa diubah. Energikan tim pada Lead Measure yang bisa dikendalikan hari ini.'],
            ] as [$icon, $color, $title, $desc])
            <div class="col-md-6">
                <div class="d-flex gap-3 p-3 rounded-3 border h-100" style="background:#fff">
                    <i class="bi {{ $icon }} text-{{ $color }} fs-4 mt-1"></i>
                    <div>
                        <div class="fw-semibold mb-1">{{ $title }}</div>
                        <div class="small text-muted">{{ $desc }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
