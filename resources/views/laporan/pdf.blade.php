<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Laporan Realisasi {{ $tahun }}</title>
<style>
body { font-family: sans-serif; font-size: 10px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #555; padding: 4px 6px; }
th { background: #eee; }
h2 { margin-bottom: 4px; }
.text-end { text-align: right; }
.text-center { text-align: center; }
</style></head>
<body>
<h2>Laporan Realisasi 4DX - Tahun {{ $tahun }}</h2>
<small>Dicetak: {{ now()->format('d M Y H:i') }}</small>
<table style="margin-top:8px">
    <thead>
        <tr>
            <th style="width:30px">#</th>
            <th>WIG</th><th>Lag Measure</th><th>Lead Measure</th><th>Cabang</th>
            <th class="text-center">Bulan</th><th class="text-center">Mg</th>
            <th class="text-end">Target</th><th class="text-end">Realisasi</th><th class="text-center">% Capaian</th>
        </tr>
    </thead>
    <tbody>
    @foreach($realisasis as $r)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $r->leadMeasure?->lagMeasure?->wig?->nama_wig }}</td>
            <td>{{ $r->leadMeasure?->lagMeasure?->nama_lag }}</td>
            <td>{{ $r->leadMeasure?->nama_lead }}</td>
            <td>{{ $r->cabang?->nama }}</td>
            <td class="text-center">{{ \Carbon\Carbon::create()->month($r->bulan)->translatedFormat('F') }}</td>
            <td class="text-center">{{ $r->minggu_ke }}</td>
            <td class="text-end">{{ number_format($r->target, 0, ',', '.') }}</td>
            <td class="text-end">{{ number_format($r->realisasi, 0, ',', '.') }}</td>
            <td class="text-center">{{ $r->persentase }}%</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body></html>
