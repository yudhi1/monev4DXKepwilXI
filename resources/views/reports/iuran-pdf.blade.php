<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monitoring Iuran</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10px; }
        h2 { margin: 0 0 4px; }
        .meta { color: #666; font-size: 10px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px 6px; vertical-align: top; }
        thead th { background: #d9e1f2; font-weight: bold; text-align: center; font-size: 10px; }
        td.num { text-align: right; }
        td.ctr { text-align: center; }
        .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 9px; color: #fff; }
        .badge.sudah { background: #198754; }
        .badge.sebagian { background: #ffc107; color: #000; }
        .badge.belum { background: #dc3545; }
    </style>
</head>
<body>
    <h2>Monitoring Iuran</h2>
    <div class="meta">
        Periode: <strong>{{ $periode }}</strong>
        @if(!empty($cabangFilter)) · Cabang: <strong>{{ $cabangFilter }}</strong>@endif
        · Dicetak: {{ now()->format('d M Y H:i') }}
    </div>
    <table>
        <thead>
            <tr>
                <th style="width:22px">No</th>
                <th>Bulan</th>
                <th>Minggu</th>
                <th>Kantor Cabang</th>
                <th>Nama Pemda</th>
                <th>Tagihan</th>
                <th>Bayar</th>
                <th>Outstanding</th>
                <th>Target Selesai</th>
                <th>PIC</th>
                <th>Kendala</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $i => $r)
                <tr>
                    <td class="ctr">{{ $i + 1 }}</td>
                    <td class="ctr">{{ $bulanLabels[$r->bulan] ?? $r->bulan }}</td>
                    <td class="ctr">Minggu {{ $r->minggu }}</td>
                    <td>{{ $r->cabang?->nama }}</td>
                    <td>{{ $r->nama_pemda }}</td>
                    <td class="num">{{ number_format((float) $r->tagihan, 0, ',', '.') }}</td>
                    <td class="ctr"><span class="badge {{ $r->status_bayar }}">{{ strtoupper($r->status_bayar) }}</span></td>
                    <td class="num">{{ number_format((float) $r->outstanding, 0, ',', '.') }}</td>
                    <td class="ctr">{{ $r->target_penyelesaian?->format('d-M-y') }}</td>
                    <td>{{ $r->pic }}</td>
                    <td>{{ $r->kendala }}</td>
                    <td>{{ $r->keterangan }}</td>
                </tr>
            @empty
                <tr><td colspan="12" style="text-align:center;color:#888;padding:12px;">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
