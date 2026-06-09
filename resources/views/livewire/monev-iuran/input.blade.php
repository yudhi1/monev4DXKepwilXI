<div class="container-fluid my-4">
    <style>
        /* ===== Styling Tabel Realisasi Iuran ===== */
        .tbl-iuran { border-collapse: separate; border-spacing: 0; }
        .tbl-iuran thead th {
            background: linear-gradient(120deg, #006837 0%, #009E60 45%, #00A99D 100%);
            color: #fff; border-color: rgba(255,255,255,.25) !important;
            font-weight: 600; vertical-align: middle; white-space: nowrap;
        }
        .tbl-iuran thead tr:nth-child(2) th {
            background: linear-gradient(120deg, #0086C9 0%, #1B4F8F 100%);
            font-size: .82rem;
        }
        .tbl-iuran thead th.col-segmen {
            background: #004d2a !important;
            position: sticky; left: 0; z-index: 5;
        }
        .tbl-iuran tbody td.col-segmen {
            position: sticky; left: 0; z-index: 1;
            background: #eef7f1; color: #14532d; font-weight: 600;
            border-right: 2px solid #009E60 !important;
        }
        .tbl-iuran tbody tr:nth-child(even) td { background-color: #f7fbf9; }
        .tbl-iuran tbody tr:nth-child(even) td.col-segmen { background-color: #e7f3ec; }
        .tbl-iuran tbody tr:hover td { background-color: #fff7e6; }
        .tbl-iuran tbody tr:hover td.col-segmen { background-color: #ffeec2; }
        .tbl-iuran tbody tr.row-editing td { background-color: #fff3cd !important; }
        .tbl-iuran tbody tr.row-editing td.col-segmen { background-color: #ffe69c !important; border-right-color:#ffc107 !important; }
        /* kolom mingguan diberi nuansa biru lembut */
        .tbl-iuran td.col-mg, .tbl-iuran th.col-mg { background-color: #f0f7fc; }
        .tbl-iuran tbody tr:hover td.col-mg { background-color: #fff7e6; }
        /* kolom total */
        .tbl-iuran td.col-total-bulan { background-color: #e8f5e9 !important; color:#1b5e20; }
        .tbl-iuran td.col-total-sd { background-color: #d6ecd9 !important; color:#0d4715; }
        .tbl-iuran tfoot td {
            background: linear-gradient(120deg, #1B4F8F 0%, #0086C9 100%) !important;
            color: #fff !important; font-weight: 700; border-color: rgba(255,255,255,.25) !important;
        }
        .tbl-iuran tfoot td.col-segmen { background: #14366b !important; }
        .tbl-iuran input.form-control, .tbl-iuran textarea.form-control { border-color:#9cc5b0; }
        .tbl-iuran input.form-control:focus, .tbl-iuran textarea.form-control:focus {
            border-color:#009E60; box-shadow:0 0 0 .2rem rgba(0,158,96,.2);
        }
        .card-iuran .card-header {
            background: linear-gradient(120deg, #006837 0%, #00A99D 100%); color:#fff;
        }
        .card-iuran .card-header h5 { color:#fff; }
    </style>

    <div class="row mb-4">
        <div class="col">
            <h2 class="h4">Input Realisasi Iuran</h2>
            <p class="text-muted">Realisasi mingguan (Mg1–Mg4) per Segmen — {{ $bulanLabels[$bulan] ?? '' }} {{ $tahun }}</p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Filter Periode</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="tahun" class="form-label">Tahun</label>
                    <input type="number" class="form-control" id="tahun" wire:model.live="tahun" min="2000" max="2100">
                </div>
                <div class="col-md-3">
                    <label for="bulan" class="form-label">Bulan Berjalan</label>
                    <select class="form-select" id="bulan" wire:model.live="bulan">
                        @foreach ($bulanLabels as $idx => $label)
                            <option value="{{ $idx }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="pilihan" class="form-label">Kantor Cabang</label>
                    <select class="form-select" id="pilihan" wire:model.live="pilihan"
                        {{ auth()->user()->hasRole('kantor_cabang') ? 'disabled' : '' }}>
                        <option value="">-- Pilih Cabang --</option>
                        @unless (auth()->user()->hasRole('kantor_cabang'))
                            <option value="konsolidasi">🔢 Konsolidasi (Semua Cabang)</option>
                        @endunless
                        @foreach ($cabangs as $cabang)
                            <option value="{{ $cabang->id }}">{{ $cabang->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    @if ($cabang_id || $konsolidasi)
        @if (count($realisasiData) === 0)
            <div class="alert alert-warning">
                Belum ada Master Segmen. Silakan tambahkan segmen terlebih dahulu di menu <strong>Master Segmen</strong>.
            </div>
        @else
            <div class="card card-iuran mb-3 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">
                        <i class="bi bi-table"></i>
                        {{ $konsolidasi ? 'Konsolidasi Realisasi — Total ' . count($cabangs) . ' Kantor Cabang' : 'Data Realisasi' }}
                    </h5>
                    <div>
                        @if ($konsolidasi)
                            <span class="badge bg-info"><i class="bi bi-bar-chart-fill"></i> Konsolidasi (lihat-saja)</span>
                        @elseif ($isPeriodeLocked)
                            <span class="badge bg-danger me-2"><i class="bi bi-lock-fill"></i> Final / Terkunci</span>
                            @if (auth()->user()->hasRole('admin'))
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                    wire:click="unlockPeriode()"
                                    onclick="return confirm('Buka kunci periode ini?')">Buka Kunci</button>
                            @endif
                        @else
                            <span class="badge bg-secondary me-2">Draft</span>
                            <button type="button" class="btn btn-sm btn-warning"
                                wire:click="lockPeriode()"
                                onclick="return confirm('Kunci periode ini menjadi Final? Data tidak bisa diubah lagi (kecuali oleh admin).')">
                                <i class="bi bi-lock"></i> Kunci (Final)
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Tabel lebar dengan scroll horizontal --}}
                <div class="table-responsive" style="max-height: 70vh; overflow: auto;">
                    <table class="table table-sm table-bordered align-middle mb-0 tbl-iuran" style="min-width: 1500px;">
                        <thead class="text-center" style="position: sticky; top: 0; z-index: 4;">
                            <tr>
                                <th rowspan="2" class="align-middle text-start col-segmen" style="min-width: 230px;">Segmen</th>
                                <th rowspan="2" class="align-middle" style="min-width: 170px;">Realisasi s.d.<br>{{ $bulanLalu }}</th>
                                <th colspan="4" class="col-mg">Realisasi {{ $bulanLabels[$bulan] ?? '' }} (Bulan Berjalan)</th>
                                <th rowspan="2" class="align-middle" style="min-width: 160px;">Total<br>Bulan Berjalan</th>
                                <th rowspan="2" class="align-middle" style="min-width: 170px;">Total s.d.<br>{{ $bulanLabels[$bulan] ?? '' }}</th>
                                <th rowspan="2" class="align-middle" style="min-width: 260px;">Keterangan</th>
                                <th rowspan="2" class="align-middle" style="min-width: 130px;">Aksi</th>
                            </tr>
                            <tr>
                                <th class="col-mg" style="min-width: 140px;">Mg1</th>
                                <th class="col-mg" style="min-width: 140px;">Mg2</th>
                                <th class="col-mg" style="min-width: 140px;">Mg3</th>
                                <th class="col-mg" style="min-width: 140px;">Mg4</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $sumSdLalu = 0; $sumMg1 = 0; $sumMg2 = 0; $sumMg3 = 0; $sumMg4 = 0; $sumBulan = 0; $sumTotal = 0;
                            @endphp
                            @foreach ($realisasiData as $segmenId => $data)
                                @php
                                    $totalBulan = ($data['mg1'] ?? 0) + ($data['mg2'] ?? 0) + ($data['mg3'] ?? 0) + ($data['mg4'] ?? 0);
                                    $totalSd = ($data['realisasi_sd_bulan_lalu'] ?? 0) + $totalBulan;
                                    $isEditing = $editingSegmenId === $segmenId;
                                    $sumSdLalu += $data['realisasi_sd_bulan_lalu'] ?? 0;
                                    $sumMg1 += $data['mg1'] ?? 0; $sumMg2 += $data['mg2'] ?? 0;
                                    $sumMg3 += $data['mg3'] ?? 0; $sumMg4 += $data['mg4'] ?? 0;
                                    $sumBulan += $totalBulan; $sumTotal += $totalSd;
                                @endphp
                                <tr wire:key="seg-{{ $segmenId }}" class="{{ $isEditing ? 'row-editing' : '' }}">
                                    <td class="col-segmen">
                                        {{ $data['nama'] }}
                                    </td>

                                    {{-- Realisasi s.d. bulan lalu --}}
                                    <td class="text-end">
                                        @if ($isEditing)
                                            <div x-data="numInput($wire.entangle('realisasiData.{{ $segmenId }}.realisasi_sd_bulan_lalu'), 'Rp')">
                                                <input type="text" inputmode="numeric" :value="display"
                                                    @focus="onFocus" @blur="onBlur" @input="onInput"
                                                    class="form-control form-control-sm text-end">
                                            </div>
                                        @else
                                            {{ number_format($data['realisasi_sd_bulan_lalu'], 0, ',', '.') }}
                                        @endif
                                    </td>

                                    {{-- Mg1 - Mg4 --}}
                                    @foreach (['mg1','mg2','mg3','mg4'] as $mg)
                                        <td class="text-end col-mg">
                                            @if ($isEditing)
                                                <div x-data="numInput($wire.entangle('realisasiData.{{ $segmenId }}.{{ $mg }}'), 'Rp')">
                                                    <input type="text" inputmode="numeric" :value="display"
                                                        @focus="onFocus" @blur="onBlur" @input="onInput"
                                                        class="form-control form-control-sm text-end">
                                                </div>
                                            @else
                                                {{ number_format($data[$mg], 0, ',', '.') }}
                                            @endif
                                        </td>
                                    @endforeach

                                    {{-- Total bulan berjalan (otomatis) --}}
                                    <td class="text-end fw-semibold col-total-bulan">{{ number_format($totalBulan, 0, ',', '.') }}</td>

                                    {{-- Total s.d. bulan ini (otomatis) --}}
                                    <td class="text-end fw-bold col-total-sd">{{ number_format($totalSd, 0, ',', '.') }}</td>

                                    {{-- Keterangan --}}
                                    <td>
                                        @if ($isEditing)
                                            <textarea rows="2" class="form-control form-control-sm"
                                                placeholder="Keterangan / catatan segmen ini..."
                                                wire:model="realisasiData.{{ $segmenId }}.keterangan"></textarea>
                                        @else
                                            <span class="small text-muted">{{ $data['keterangan'] ?: '—' }}</span>
                                        @endif
                                    </td>

                                    {{-- Aksi --}}
                                    <td class="text-center text-nowrap">
                                        @if ($konsolidasi)
                                            <span class="text-muted">—</span>
                                        @elseif ($isPeriodeLocked)
                                            <span class="text-muted small"><i class="bi bi-lock-fill"></i></span>
                                        @elseif ($isEditing)
                                            <button type="button" class="btn btn-sm btn-success"
                                                wire:click="saveRow({{ $segmenId }})" title="Simpan">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary"
                                                wire:click="cancelEdit()" title="Batal">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-warning"
                                                wire:click="editRow({{ $segmenId }})" title="Edit">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="fw-bold" style="position: sticky; bottom: 0; z-index: 3;">
                            <tr>
                                <td class="text-end col-segmen">TOTAL</td>
                                <td class="text-end">{{ number_format($sumSdLalu, 0, ',', '.') }}</td>
                                <td class="text-end col-mg">{{ number_format($sumMg1, 0, ',', '.') }}</td>
                                <td class="text-end col-mg">{{ number_format($sumMg2, 0, ',', '.') }}</td>
                                <td class="text-end col-mg">{{ number_format($sumMg3, 0, ',', '.') }}</td>
                                <td class="text-end col-mg">{{ number_format($sumMg4, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($sumBulan, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($sumTotal, 0, ',', '.') }}</td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="card-footer text-muted small">
                    @if ($konsolidasi)
                        <i class="bi bi-bar-chart-fill"></i> Mode <strong>Konsolidasi</strong>: menampilkan penjumlahan realisasi seluruh kantor cabang per segmen untuk {{ $bulanLabels[$bulan] ?? '' }} {{ $tahun }}. Data hanya bisa dilihat (tidak bisa diedit di sini).
                    @else
                        <i class="bi bi-info-circle"></i> Klik <strong>Edit</strong> pada baris segmen untuk mengubah nilai, lalu klik <i class="bi bi-check-lg"></i> untuk menyimpan. Geser tabel ke kanan untuk melihat kolom Keterangan & Aksi.
                    @endif
                </div>
            </div>
        @endif
    @else
        <div class="alert alert-info">Pilih Kantor Cabang untuk menampilkan tabel input realisasi.</div>
    @endif
</div>
