@extends('layouts.app')
@section('content')
<h3 class="mb-3">Laporan Realisasi 4DX</h3>

<form method="GET" class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-1">Tahun</label>
                <input type="number" name="tahun" value="{{ $filters['tahun'] ?? $tahun }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">WIG</label>
                <select name="wig_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">- Semua WIG -</option>
                    @foreach($wigs as $w)
                        <option value="{{ $w->id }}" @selected(($filters['wig_id'] ?? null) == $w->id)>{{ $w->nama_wig }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Lead Measure</label>
                <select name="lead_measure_id" class="form-select form-select-sm">
                    <option value="">- Semua Lead -</option>
                    @foreach($leads as $l)
                        <option value="{{ $l->id }}" @selected(($filters['lead_measure_id'] ?? null) == $l->id)>{{ $l->nama_lead }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Cabang</label>
                <select name="cabang_id" class="form-select form-select-sm">
                    <option value="">- Semua Cabang -</option>
                    @foreach($cabangs as $c)
                        <option value="{{ $c->id }}" @selected(($filters['cabang_id'] ?? null) == $c->id)>{{ $c->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Bulan</label>
                <select name="bulan" class="form-select form-select-sm">
                    <option value="">- Semua -</option>
                    @foreach([1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'] as $k=>$v)
                        <option value="{{ $k }}" @selected(($filters['bulan'] ?? null) == $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Minggu</label>
                <select name="minggu" class="form-select form-select-sm">
                    <option value="">- Semua -</option>
                    @for($i=1;$i<=4;$i++)
                        <option value="{{ $i }}" @selected(($filters['minggu'] ?? null) == $i)>Minggu {{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-12 d-flex gap-2 mt-2">
                <button class="btn btn-primary btn-sm">Terapkan Filter</button>
                <a href="{{ route('laporan') }}" class="btn btn-secondary btn-sm">Reset</a>
                <div class="ms-auto d-flex gap-2">
                    <a href="{{ route('laporan.excel', request()->query()) }}" class="btn btn-success btn-sm">Export Excel</a>
                    <a href="{{ route('laporan.pdf', request()->query()) }}" class="btn btn-danger btn-sm">Export PDF</a>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:50px">#</th>
                        <th>WIG</th>
                        <th>Lag Measure</th>
                        <th>Lead Measure</th>
                        <th>Cabang</th>
                        <th class="text-center">Bulan</th>
                        <th class="text-center">Minggu</th>
                        <th class="text-end">Target</th>
                        <th class="text-end">Realisasi</th>
                        <th class="text-center">% Capaian</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($realisasis as $r)
                    <tr>
                        <td>{{ $realisasis->firstItem() + $loop->index }}</td>
                        <td>{{ $r->leadMeasure?->lagMeasure?->wig?->nama_wig }}</td>
                        <td>{{ $r->leadMeasure?->lagMeasure?->nama_lag }}</td>
                        <td>{{ $r->leadMeasure?->nama_lead }}</td>
                        <td>{{ $r->cabang?->nama }}</td>
                        <td class="text-center">{{ \Carbon\Carbon::create()->month($r->bulan)->translatedFormat('F') }}</td>
                        <td class="text-center">{{ $r->minggu_ke }}</td>
                        <td class="text-end">{{ number_format($r->target, 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($r->realisasi, 0, ',', '.') }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ $r->persentase >= 100 ? 'success' : ($r->persentase >= 70 ? 'warning' : 'danger') }}">{{ $r->persentase }}%</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted">Tidak ada data sesuai filter.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $realisasis->links() }}
    </div>
</div>
@endsection
