<div>
    <style>
        .lead-detail-table th, .lead-detail-table td { padding: .35rem .5rem; font-size: .8rem; }
        .lead-detail-table .badge { font-size: .7rem; padding: .25em .45em; }
        .lead-detail-table thead th { font-weight: 600; }
    </style>
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h3 class="mb-0">Dashboard Kedeputian Wilayah</h3>
            <small class="text-muted">Overview pencapaian seluruh kantor cabang di wilayah Anda</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <input type="number" wire:model.live="tahun" class="form-control form-control-sm" style="width:90px">
            <select wire:model.live="bulan" class="form-select form-select-sm" style="width:110px">
                @foreach([1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'] as $k=>$v)
                    <option value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </select>
            <select wire:model.live="minggu" class="form-select form-select-sm" style="width:120px">
                @for($i=1;$i<=4;$i++)<option value="{{ $i }}">Minggu {{ $i }}</option>@endfor
            </select>
            <a href="{{ url('/dashboard-cabang') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-clipboard-data"></i> Detail Cabang
            </a>
        </div>
    </div>

    {{-- SUMMARY CARDS --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card text-white bg-primary h-100"><div class="card-body p-3">
            <small><i class="bi bi-building"></i> Total Cabang</small><h3 class="mb-0">{{ $totalCabang }}</h3></div></div></div>
        <div class="col-md-3"><div class="card text-white bg-info h-100"><div class="card-body p-3">
            <small><i class="bi bi-bullseye"></i> Total WIG Aktif</small><h3 class="mb-0">{{ $totalWig }}</h3></div></div></div>
        <div class="col-md-3"><div class="card text-white bg-success h-100"><div class="card-body p-3">
            <small><i class="bi bi-graph-up"></i> Avg Capaian Wilayah</small><h3 class="mb-0">{{ $avgWilayah }}%</h3></div></div></div>
        <div class="col-md-3"><div class="card text-white bg-warning h-100"><div class="card-body p-3">
            <small><i class="bi bi-trophy"></i> Cabang On Track</small><h3 class="mb-0">{{ $cabangOnTrack }}/{{ $totalCabang }}</h3></div></div></div>
    </div>

    <div class="row g-3 mb-4">
        {{-- RANKING CABANG --}}
        <div class="col-lg-12">
            <div class="card h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <strong><i class="bi bi-trophy"></i> Ranking Cabang — Minggu {{ $minggu }}/{{ $bulan }}/{{ $tahun }}</strong>
                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        <small class="text-muted">Cabang:</small>
                        <select wire:model.live="selected_cabang_id" class="form-select form-select-sm" style="width:150px">
                            <option value="">- Pilih -</option>
                            @foreach ($cabangs as $c)
                                <option value="{{ $c->id }}">{{ $c->nama }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">WIG:</small>
                        <select wire:model.live="filter_wig_id" class="form-select form-select-sm" style="width:170px">
                            <option value="">Semua WIG</option>
                            @foreach ($wigs as $w)
                                <option value="{{ $w->id }}">{{ $w->kode_wig }} — {{ \Illuminate\Support\Str::limit($w->nama_wig, 30) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:50px">#</th>
                                    <th>Kantor Cabang</th>
                                    <th class="text-center" style="width:90px">Lead Diisi</th>
                                    <th class="text-center" style="width:140px">% Capaian</th>
                                    <th class="text-center" style="width:130px">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rankingRows as $i => $r)
                                    <tr wire:click="$set('selected_cabang_id', {{ $r['cabang']->id }})"
                                        style="cursor:pointer"
                                        class="{{ $selected_cabang_id == $r['cabang']->id ? 'table-primary' : '' }}">
                                        <td class="text-center fw-semibold">{{ $i + 1 }}</td>
                                        <td>
                                            <i class="bi bi-{{ $selected_cabang_id == $r['cabang']->id ? 'eye-fill text-primary' : 'eye' }}"></i>
                                            {{ $r['cabang']->nama }}
                                        </td>
                                        <td class="text-center"><small class="text-muted">{{ $r['jumlah_lead'] }}</small></td>
                                        <td class="text-center">
                                            <div class="progress" style="height:18px">
                                                <div class="progress-bar bg-{{ $r['status'][0] }}" style="width: {{ min($r['pct'], 100) }}%">
                                                    {{ $r['pct'] }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $r['status'][0] }}">{{ $r['status'][1] }} {{ $r['status'][2] }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-3">Belum ada cabang di wilayah Anda.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- DETAIL LEAD MEASURE PER KC --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <strong><i class="bi bi-list-check"></i> Detail Lead Measure per Kantor Cabang</strong>
            <small class="ms-2 opacity-75">Periode: Minggu {{ $minggu }} / {{ \Carbon\Carbon::create()->month($bulan)->translatedFormat('F') }} {{ $tahun }} · diurutkan dari % capaian terendah</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 60vh;">
                <table class="table table-sm table-bordered align-middle mb-0 lead-detail-table" style="min-width: 1200px; font-size: .8rem;">
                    <thead class="table-light text-center" style="position: sticky; top: 0; z-index: 2;">
                        <tr>
                            <th style="width:40px">No</th>
                            <th style="min-width:200px">WIG</th>
                            <th style="min-width:220px">Lead Measure</th>
                            <th style="width:110px">Target</th>
                            <th style="width:140px">Realisasi Minggu Ini</th>
                            <th style="width:100px">% Capaian</th>
                            <th style="width:120px">Status</th>
                            <th style="width:100px">Tren</th>
                            <th style="min-width:200px">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (! $selected_cabang_id)
                            <tr><td colspan="9" class="text-center text-muted py-3">Klik baris cabang di Ranking, atau pilih dari filter di atas.</td></tr>
                        @else
                            @forelse ($detailLeads as $i => $d)
                                <tr>
                                    <td class="text-center">{{ $i + 1 }}</td>
                                    <td>
                                        <span class="badge bg-primary">{{ $d['wig']?->kode_wig }}</span>
                                        <div class="small text-muted">{{ \Illuminate\Support\Str::limit($d['wig']?->nama_wig, 50) }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $d['lead']->nama_lead }} <span class="text-muted">({{ $d['lead']->kode_lead }})</span></div>
                                    </td>
                                    <td class="text-end">{{ number_format($d['target'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($d['realisasi'], 0, ',', '.') }}</td>
                                    <td class="text-center"><span class="badge bg-{{ $d['status'][0] }}">{{ $d['pct'] }}%</span></td>
                                    <td class="text-center"><span class="badge bg-{{ $d['status'][0] }}">{{ $d['status'][1] }} {{ $d['status'][2] }}</span></td>
                                    <td class="text-center {{ $d['tren'][1] }}" title="Sebelumnya {{ $d['pct_prev'] }}%">
                                        <span style="font-size:1.3em">{{ $d['tren'][0] }}</span> <small>{{ $d['tren'][2] }}</small>
                                    </td>
                                    <td class="small" style="white-space:pre-wrap">{{ $d['keterangan'] ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-3">Belum ada Lead Measure untuk cabang ini.</td></tr>
                            @endforelse
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
