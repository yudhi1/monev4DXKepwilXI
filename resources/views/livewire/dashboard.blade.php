<div>
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h3 class="mb-0">Dashboard 4DX Kepwil XI</h3>
        <div class="d-flex gap-2 flex-wrap">
            @if(auth()->user() && ! auth()->user()->hasRole('kantor_cabang'))
            <select wire:model.live="cabang_id" class="form-select form-select-sm" style="width:200px">
                @foreach($cabangs as $c)<option value="{{ $c->id }}">{{ $c->nama }}</option>@endforeach
            </select>
            @endif
            <input type="number" wire:model.live="tahun" class="form-control form-control-sm" style="width:90px">
            <select wire:model.live="bulan" class="form-select form-select-sm" style="width:110px">
                @foreach([1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'] as $k=>$v)
                    <option value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </select>
            <select wire:model.live="minggu" class="form-select form-select-sm" style="width:120px">
                @for($i=1;$i<=4;$i++)<option value="{{ $i }}">Minggu {{ $i }}</option>@endfor
            </select>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card text-white bg-primary"><div class="card-body p-3">
            <small>Total WIG</small><h3 class="mb-0">{{ $totalWig }}</h3></div></div></div>
        <div class="col-md-4"><div class="card text-white bg-info"><div class="card-body p-3">
            <small>Total Lead Measure</small><h3 class="mb-0">{{ $totalLead }}</h3></div></div></div>
        <div class="col-md-4"><div class="card text-white bg-success"><div class="card-body p-3">
            <small>Lead On Track ({{ $totalLead ? round($onTrack/$totalLead*100) : 0 }}%)</small><h3 class="mb-0">{{ $onTrack }}/{{ $totalLead }}</h3></div></div></div>
    </div>

    {{-- WIG PROGRESS PER CABANG (disembunyikan sementara) --}}
    @if(false && count($wigProgress))
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <strong>WIG Progress — {{ $cabangs->firstWhere('id', $cabang_id)?->nama ?? '-' }}</strong>
        </div>
        <div class="card-body" x-data="{ selected: 0, mode: 'bulanan' }">
            {{-- LEGEND / CARA BACA --}}
            <div class="border rounded bg-light p-3 mb-3 small">
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-info-circle-fill text-primary me-2"></i>
                    <strong>Panduan Membaca Tabel</strong>
                    <span class="text-muted ms-2">— klik baris WIG untuk melihat detail di bawah</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="d-flex">
                            <span class="badge bg-primary me-2 align-self-start" style="min-width:90px">% WIG</span>
                            <span class="text-muted">Capaian hasil bulanan WIG terhadap target.</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex">
                            <span class="badge bg-warning text-dark me-2 align-self-start" style="min-width:90px">% Aktivitas</span>
                            <span class="text-muted">Rata-rata capaian Lead Measure (aktivitas pendorong).</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex">
                            <span class="badge bg-success me-2 align-self-start" style="min-width:90px">Korelasi</span>
                            <span class="text-muted">Apakah aktivitas benar-benar mendorong hasil WIG.</span>
                        </div>
                    </div>
                </div>
                <hr class="my-2">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="text-muted me-1">Skala korelasi:</span>
                    <span class="badge rounded-pill bg-success">🟢 Lead efektif &nbsp;·&nbsp; r ≥ 0.6</span>
                    <span class="badge rounded-pill bg-warning text-dark">🟡 Cukup &nbsp;·&nbsp; 0.3 – 0.6</span>
                    <span class="badge rounded-pill bg-danger">🔴 Lemah &nbsp;·&nbsp; &lt; 0.3</span>
                    <span class="badge rounded-pill bg-secondary">⚪ Data belum cukup</span>
                </div>
            </div>

            {{-- TABEL --}}
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:28%">WIG</th>
                            <th class="text-end" style="width:11%">Sekarang / Target</th>
                            <th class="text-center" style="width:16%">Progres Tahun</th>
                            <th class="text-center" style="width:8%">% WIG</th>
                            <th class="text-center" style="width:8%">% Aktivitas</th>
                            <th class="text-center" style="width:9%">Gap</th>
                            <th class="text-center" style="width:11%">Korelasi</th>
                            <th class="text-center" style="width:9%">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($wigProgress as $idxWp => $wp)
                            @php
                                $paceColor = ['on'=>'success','warn'=>'warning','behind'=>'danger','unknown'=>'secondary'][$wp['pace']];
                                $paceLabel = ['on'=>'On Pace','warn'=>'Hati-hati','behind'=>'Belum Tercapai','unknown'=>'-'][$wp['pace']];
                                $wigPctNow = $wp['wig_pct_bulan'][$bulan-1] ?? 0;
                                $leadPctNow = $wp['lead_pct_bulan'][$bulan-1] ?? 0;
                                $gapAkhir = round($leadPctNow - $wigPctNow, 2);
                                $gapBadge = $gapAkhir >= 0 ? 'success' : ($gapAkhir >= -15 ? 'warning' : 'danger');
                                $korColor = ['kuat'=>'success','sedang'=>'warning','lemah'=>'danger','kurang_data'=>'secondary'][$wp['korelasi']['level']];
                                $korIcon = ['kuat'=>'🟢','sedang'=>'🟡','lemah'=>'🔴','kurang_data'=>'⚪'][$wp['korelasi']['level']];
                            @endphp
                            <tr style="cursor:pointer" :class="selected === {{ $idxWp }} ? 'table-primary' : ''" @click="selected = {{ $idxWp }}; setTimeout(()=>renderWigDetail(), 30)">
                                <td>
                                    <div>
                                        <span class="badge bg-primary">{{ $wp['wig']->kode_wig }}</span>
                                        @if($wp['wig']->bidang)<span class="badge bg-info">{{ $wp['wig']->bidang }}</span>@endif
                                    </div>
                                    <div class="small fw-semibold">{{ $wp['wig']->nama_wig }}</div>
                                </td>
                                <td class="text-end small">
                                    <strong>{{ \App\Support\Format::nilai($wp['nilai_sekarang'], $wp['satuan']) }}</strong>
                                    <div class="text-muted">/ {{ \App\Support\Format::nilai($wp['nilai_target'], $wp['satuan']) }}</div>
                                </td>
                                <td>
                                    <div class="progress" style="height:18px">
                                        <div class="progress-bar bg-{{ $paceColor }}" style="width: {{ $wp['pct'] }}%">{{ $wp['pct_raw'] }}%</div>
                                    </div>
                                </td>
                                <td class="text-center"><span class="badge bg-primary">{{ round($wigPctNow,1) }}%</span></td>
                                <td class="text-center"><span class="badge bg-warning text-dark">{{ round($leadPctNow,1) }}%</span></td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $gapBadge }}">{{ $gapAkhir >= 0 ? '+' : '' }}{{ $gapAkhir }}%</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $korColor }}" title="{{ $wp['korelasi']['label'] }}">
                                        {{ $korIcon }} {{ $wp['korelasi']['r'] !== null ? $wp['korelasi']['r'] : '—' }}
                                    </span>
                                </td>
                                <td class="text-center"><span class="badge bg-{{ $paceColor }}">{{ $paceLabel }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- PANEL DETAIL --}}
            @php
                $detailPayload = collect($wigProgress)->map(fn($wp) => [
                    'kode' => $wp['wig']->kode_wig,
                    'nama' => $wp['wig']->nama_wig,
                    'satuan' => $wp['satuan'],
                    'nilai_awal' => $wp['nilai_awal'],
                    'nilai_sekarang' => $wp['nilai_sekarang'],
                    'nilai_target' => $wp['nilai_target'],
                    'wig_pct_bulan' => $wp['wig_pct_bulan'],
                    'wig_delta_bulan' => $wp['wig_delta_bulan'],
                    'lead_pct_bulan' => $wp['lead_pct_bulan'],
                    'lead_pct_mingguan' => $wp['lead_pct_mingguan'],
                    'lead_labels_mingguan' => $wp['lead_labels_mingguan'],
                    'korelasi' => $wp['korelasi'],
                ])->values();
            @endphp
            <div class="border rounded mt-3 p-3 bg-light" id="wigDetailPanel" data-payload='@json($detailPayload)'>
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <div>
                        <strong id="wigDetailTitle">—</strong>
                        <small id="wigDetailNilai" class="text-muted d-block"></small>
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary" :class="mode==='bulanan' ? 'active' : ''" @click="mode='bulanan'; setTimeout(()=>renderWigDetail(), 30)">📅 Bulanan</button>
                        <button type="button" class="btn btn-outline-primary" :class="mode==='mingguan' ? 'active' : ''" @click="mode='mingguan'; setTimeout(()=>renderWigDetail(), 30)">📆 Detail Mingguan (Aktivitas)</button>
                    </div>
                </div>
                <canvas id="wigDetailChart" height="100"></canvas>
                <div id="wigDetailInsight" class="alert mt-3 mb-0 small py-2"></div>
            </div>
        </div>
    </div>
    @endif

    {{-- SCOREBOARD --}}
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <strong>1. SCOREBOARD WIG MINGGUAN</strong>
            <span class="float-end small">
                Cabang:
                <strong>{{ $cabangs->firstWhere('id', $cabang_id)?->nama ?? '-' }}</strong>
                &nbsp;|&nbsp; Periode: <strong>{{ \Carbon\Carbon::create()->month($bulan)->translatedFormat('F') }} {{ $tahun }} – Minggu {{ $minggu }}</strong>
            </span>
        </div>
        <div class="card-body p-0">
            <div class="accordion accordion-flush" id="wigAcc">
                @forelse($wigs as $i => $wig)
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#wig-{{ $wig->id }}">
                                <span class="badge bg-primary me-2">{{ $wig->kode_wig }}</span>
                                <strong>{{ $wig->nama_wig }}</strong>
                                @if($wig->bidang)<span class="badge bg-info ms-2">{{ $wig->bidang }}</span>@endif
                            </button>
                        </h2>
                        <div id="wig-{{ $wig->id }}" class="accordion-collapse collapse" data-bs-parent="#wigAcc">
                            <div class="accordion-body p-3">
                                @forelse($wig->lagMeasures as $lag)
                                    <div class="mb-3">
                                        <div class="text-secondary small mb-1">
                                            <span class="badge bg-secondary">{{ $lag->kode_lag }}</span>
                                            {{ $lag->nama_lag }}
                                            <span class="text-muted">(Target Tahunan: {{ number_format($lag->target_tahunan, 0, ',', '.') }} {{ $lag->satuan }})</span>
                                        </div>
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-center" style="width:40px">No</th>
                                                    <th>Lead Measure</th>
                                                    <th class="text-end" style="width:10%">Target</th>
                                                    <th class="text-end" style="width:12%">Realisasi Minggu Ini</th>
                                                    <th class="text-center" style="width:13%">Perhitungan</th>
                                                    <th class="text-center" style="width:9%">% Capaian</th>
                                                    <th class="text-center" style="width:10%">Status</th>
                                                    <th class="text-center" style="width:10%">Tren</th>
                                                    <th style="min-width:200px">Keterangan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($lag->leadMeasures as $lead)
                                                @php
                                                    $rNow = $realisasiNow->get($lead->id);
                                                    $rPrev = $realisasiPrev->get($lead->id);
                                                    $pctNow = $rNow?->persentase ?? 0;
                                                    $pctPrev = $rPrev?->persentase ?? 0;
                                                    $status = $pctNow >= 100 ? ['green','🟢','On Track'] : ($pctNow >= 70 ? ['yellow','🟡','Hati-hati'] : ['red','🔴','Belum Tercapai']);
                                                    $diff = $pctNow - $pctPrev;
                                                    $tren = abs($diff) < 1 ? ['→','text-secondary','Stabil'] : ($diff > 0 ? ['↗','text-success','Naik'] : ['↘','text-danger','Turun']);
                                                @endphp
                                                <tr>
                                                    <td class="text-center">{{ $loop->iteration }}</td>
                                                    <td>{{ $lead->nama_lead }} ({{ $lead->kode_lead }})</td>
                                                    <td class="text-end">{{ $rNow ? number_format($rNow->target, 0, ',', '.') : '-' }}</td>
                                                    <td class="text-end">{{ $rNow ? number_format($rNow->realisasi, 0, ',', '.') : '-' }}</td>
                                                    <td class="text-center small">
                                                        @if ($rNow && $rNow->target > 0)
                                                            <span class="text-muted">({{ number_format($rNow->realisasi, 0, ',', '.') }} ÷ {{ number_format($rNow->target, 0, ',', '.') }}) × 100</span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center"><strong>{{ $pctNow }}%</strong></td>
                                                    <td class="text-center" title="{{ $status[2] }}">{{ $status[1] }} <small class="text-muted">{{ $status[2] }}</small></td>
                                                    <td class="text-center {{ $tren[1] }}" title="Sebelumnya {{ $pctPrev }}%"><span style="font-size:1.3em">{{ $tren[0] }}</span> <small>{{ $tren[2] }}</small></td>
                                                    <td class="small" style="white-space: pre-wrap;">{{ $rNow?->catatan ?: '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="9" class="text-muted text-center">Belum ada Lead Measure</td></tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">Belum ada Lag Measure pada WIG ini.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-3 text-center text-muted">Belum ada WIG untuk tahun {{ $tahun }}.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- chart scripts disabled (WIG progress & grafik dihilangkan) --}}
    <script type="text/template" id="dashboard-archived-chart-script">
        document.addEventListener('DOMContentLoaded', initCharts);
        document.addEventListener('livewire:navigated', initCharts);
        window.addEventListener('livewire:update', () => { setTimeout(initCharts, 50); });
        function initCharts() {
            initChart();
            renderWigDetail();
        }
        function initChart() {
            const el = document.getElementById('bulanChart');
            if (!el) return;
            if (el._chart) el._chart.destroy();
            el._chart = new Chart(el, {
                type: 'bar',
                data: {
                    labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
                    datasets: [{ label: '% Capaian', data: @json($bulanData), backgroundColor: '#0d6efd' }]
                },
                options: { scales: { y: { beginAtZero: true, max: 150 } } }
            });
        }
        function getSelectedWigIdx() {
            const panel = document.getElementById('wigDetailPanel');
            if (!panel) return 0;
            // Ambil dari Alpine state (jika ada) lewat closest x-data
            const cardBody = panel.closest('[x-data]');
            try { return (cardBody && cardBody._x_dataStack) ? cardBody._x_dataStack[0].selected : 0; } catch (e) { return 0; }
        }
        function getMode() {
            const panel = document.getElementById('wigDetailPanel');
            const cardBody = panel ? panel.closest('[x-data]') : null;
            try { return (cardBody && cardBody._x_dataStack) ? cardBody._x_dataStack[0].mode : 'bulanan'; } catch (e) { return 'bulanan'; }
        }
        function renderWigDetail() {
            const panel = document.getElementById('wigDetailPanel');
            if (!panel) return;
            const payload = JSON.parse(panel.dataset.payload || '[]');
            if (!payload.length) return;
            const idx = Math.min(getSelectedWigIdx(), payload.length - 1);
            const wp = payload[idx];
            const mode = getMode();

            // Header
            document.getElementById('wigDetailTitle').textContent = `[${wp.kode}] ${wp.nama}`;
            const fmt = n => Number(n).toLocaleString('id-ID', { maximumFractionDigits: 2 });
            document.getElementById('wigDetailNilai').textContent =
                `Awal: ${fmt(wp.nilai_awal)} ${wp.satuan||''} · Sekarang: ${fmt(wp.nilai_sekarang)} ${wp.satuan||''} · Target: ${fmt(wp.nilai_target)} ${wp.satuan||''}`;

            // Chart
            const el = document.getElementById('wigDetailChart');
            if (el._chart) el._chart.destroy();
            let cfg;
            if (mode === 'bulanan') {
                cfg = {
                    type: 'line',
                    data: {
                        labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
                        datasets: [
                            { label: '% Capaian WIG (kumulatif)', data: wp.wig_pct_bulan, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,.15)', borderWidth: 3, tension: .3, fill: true },
                            { label: '% Capaian Aktivitas (Lead bulanan)', data: wp.lead_pct_bulan, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,.15)', borderWidth: 3, borderDash: [6,4], tension: .3, fill: false },
                        ]
                    },
                    options: {
                        responsive: true,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { position: 'bottom' },
                            tooltip: { callbacks: { label: c => c.dataset.label + ': ' + c.parsed.y + '%' } },
                        },
                        scales: { y: { beginAtZero: true, suggestedMax: 120, ticks: { callback: v => v + '%' } } }
                    }
                };
            } else {
                cfg = {
                    type: 'bar',
                    data: {
                        labels: wp.lead_labels_mingguan,
                        datasets: [{
                            label: '% Aktivitas mingguan',
                            data: wp.lead_pct_mingguan,
                            backgroundColor: wp.lead_pct_mingguan.map(v => v >= 100 ? '#198754' : v >= 70 ? '#ffc107' : v > 0 ? '#dc3545' : '#e9ecef'),
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: c => 'Aktivitas: ' + c.parsed.y + '%' } },
                        },
                        scales: {
                            y: { beginAtZero: true, suggestedMax: 120, ticks: { callback: v => v + '%' } },
                            x: { ticks: { font: { size: 9 } } }
                        }
                    }
                };
            }
            el._chart = new Chart(el, cfg);

            // Insight tekstual
            const insight = document.getElementById('wigDetailInsight');
            const k = wp.korelasi || {};
            const wigNow = wp.wig_pct_bulan[wp.wig_pct_bulan.length-1] || 0;
            const leadNow = wp.lead_pct_bulan[wp.lead_pct_bulan.length-1] || 0;
            let cls = 'alert-secondary', msg = '';
            if (k.level === 'kuat') {
                cls = 'alert-success';
                msg = `<strong>🟢 Aktivitas (Lead) terbukti mendorong WIG.</strong> Korelasi r=${k.r}. Pertahankan ritme aktivitas saat ini — setiap kenaikan aktivitas berdampak ke hasil WIG.`;
            } else if (k.level === 'sedang') {
                cls = 'alert-warning';
                msg = `<strong>🟡 Aktivitas cukup berpengaruh.</strong> Korelasi r=${k.r}. Ada hubungan tapi belum kuat — evaluasi apakah ada Lead Measure tambahan yang perlu diaktifkan, atau Lead yang ada perlu ditingkatkan kualitasnya.`;
            } else if (k.level === 'lemah') {
                cls = 'alert-danger';
                msg = `<strong>🔴 Aktivitas tidak nyambung dengan hasil WIG.</strong> Korelasi r=${k.r}. Ada kemungkinan: (1) Lead Measure yang dipilih bukan pendorong utama, (2) hasil WIG dipengaruhi faktor eksternal, atau (3) ada jeda waktu yang lebih panjang. Pertimbangkan ganti/tambah Lead Measure.`;
            } else {
                cls = 'alert-secondary';
                msg = `<strong>⚪ Data belum cukup</strong> untuk menyimpulkan hubungan. Minimal butuh 3 bulan realisasi WIG + Aktivitas.`;
            }
            // Tambah pesan gap
            const gap = (leadNow - wigNow).toFixed(1);
            if (Math.abs(gap) > 5 && k.level !== 'kurang_data') {
                msg += ` <br><strong>Bulan ini:</strong> % Aktivitas ${leadNow}% vs % WIG ${wigNow}% (gap ${gap >= 0 ? '+' : ''}${gap}%).`;
            }
            insight.className = 'alert mt-3 mb-0 small py-2 ' + cls;
            insight.innerHTML = msg;
        }
    </script>
</div>
