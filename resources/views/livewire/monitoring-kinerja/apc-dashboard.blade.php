<div class="container-fluid my-4">
    <div class="row mb-3">
        <div class="col">
            <h2 class="h4"><i class="bi bi-speedometer2"></i> Monitoring Kinerja — {{ $label }}</h2>
            <p class="text-muted mb-0">Dashboard capaian indikator APC. Data diisi melalui upload file Excel.</p>
        </div>
    </div>

    {{-- Navigasi antar indikator --}}
    <ul class="nav nav-pills flex-wrap gap-1 mb-4">
        @foreach ($semuaIndikator as $slug => $nama)
            <li class="nav-item">
                <a class="nav-link {{ $slug === $indikator ? 'active' : '' }}"
                    href="{{ route('monitoring-kinerja.show', $slug) }}">
                    {{ $nama }}
                </a>
            </li>
        @endforeach
    </ul>

    {{-- Form Upload --}}
    @if ($canManage)
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-upload"></i> Upload Data Excel</h5>
            </div>
            <div class="card-body">
                <form wire:submit="upload" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="tahun" class="form-label">Tahun</label>
                        <input type="number" class="form-control" id="tahun" wire:model="tahun" min="2000" max="2100">
                        @error('tahun') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="file" class="form-label">File Excel (.xlsx, .xls, .csv) — maks 10 MB</label>
                        <input type="file" class="form-control" id="file" wire:model="file"
                            accept=".xlsx,.xls,.csv">
                        @error('file') <span class="text-danger small">{{ $message }}</span> @enderror
                        <div wire:loading wire:target="file" class="text-muted small mt-1">
                            <span class="spinner-border spinner-border-sm"></span> Mengunggah file...
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100" wire:loading.attr="disabled" wire:target="upload,file">
                            <span wire:loading.remove wire:target="upload"><i class="bi bi-cloud-upload"></i> Upload</span>
                            <span wire:loading wire:target="upload"><span class="spinner-border spinner-border-sm"></span> Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Preview Data Terbaru --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="bi bi-table"></i> Data Terbaru</h5>
            @if ($latest)
                <span class="text-muted small">
                    {{ $latest->original_name }}
                    @if ($latest->tahun) · Tahun {{ $latest->tahun }} @endif
                    · diupload {{ $latest->created_at->format('d/m/Y H:i') }}
                </span>
            @endif
        </div>
        <div class="card-body">
            @if (! $latest || empty($latest->sheet_json))
                <div class="alert alert-info mb-0">
                    Belum ada data untuk indikator ini. @if ($canManage) Silakan upload file Excel di atas. @endif
                </div>
            @else
                @php
                    $rows = $latest->sheet_json;
                    $header = $rows[0] ?? [];
                    $body = array_slice($rows, 1);
                    $colCount = count($header) ?: (count($rows[0] ?? []));
                @endphp
                <div class="table-responsive" style="max-height: 65vh; overflow: auto;">
                    <table class="table table-sm table-bordered table-striped align-middle mb-0">
                        <thead class="table-dark" style="position: sticky; top: 0; z-index: 2;">
                            <tr>
                                <th style="width:48px;">#</th>
                                @foreach ($header as $h)
                                    <th>{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($body as $i => $row)
                                <tr>
                                    <td class="text-muted">{{ $i + 1 }}</td>
                                    @for ($c = 0; $c < $colCount; $c++)
                                        <td>{{ $row[$c] ?? '' }}</td>
                                    @endfor
                                </tr>
                            @empty
                                <tr><td colspan="{{ $colCount + 1 }}" class="text-center text-muted py-3">File tidak memiliki baris data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="text-muted small mt-2">Total {{ $latest->rows_count }} baris data.</div>
            @endif
        </div>
    </div>

    {{-- Riwayat Upload --}}
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Riwayat Upload</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nama File</th>
                        <th>Tahun</th>
                        <th class="text-center">Baris</th>
                        <th>Diupload Oleh</th>
                        <th>Waktu</th>
                        <th class="text-center" style="width:130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history as $h)
                        <tr>
                            <td>{{ $h->original_name }}</td>
                            <td>{{ $h->tahun ?? '-' }}</td>
                            <td class="text-center">{{ $h->rows_count }}</td>
                            <td>{{ $h->uploader?->name ?? '-' }}</td>
                            <td>{{ $h->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-center text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" wire:click="download({{ $h->id }})" title="Unduh">
                                    <i class="bi bi-download"></i>
                                </button>
                                @if ($canManage)
                                    <button class="btn btn-sm btn-outline-danger" wire:click="hapus({{ $h->id }})"
                                        onclick="return confirm('Hapus data upload ini?')" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">Belum ada riwayat upload.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
