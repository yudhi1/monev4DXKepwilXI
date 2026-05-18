<div>
    <style>
        .iuran-table th, .iuran-table td { padding: .35rem .5rem; font-size: .8rem; }
        .iuran-table .badge { font-size: .7rem; padding: .25em .45em; }
        .iuran-table thead th { font-weight: 600; }
    </style>
    <h3>Monitoring Iuran</h3>
    <p class="text-muted small">Monitoring tagihan iuran per Pemda, dirinci per bulan dan minggu (maks. {{ $maxPemdaPerMinggu }} Pemda per minggu).</p>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Tahun</label>
                    <input type="number" wire:model.live="tahun" class="form-control form-control-sm" min="2000" max="2100">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Bulan</label>
                    <select wire:model.live="bulan" class="form-select form-select-sm">
                        @foreach ($bulanLabels as $no => $nm)
                            <option value="{{ $no }}">{{ $nm }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>{{ $editingId ? 'Edit' : 'Tambah' }} Data Iuran</strong></div>
        <div class="card-body">
            @php $isCabangUser = auth()->user()?->hasRole('kantor_cabang'); @endphp
            <form wire:submit="save">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small">Kantor Cabang</label>
                        <select wire:model="cabang_id" class="form-select form-select-sm" @if($isCabangUser) disabled @endif>
                            <option value="">- Pilih Cabang -</option>
                            @foreach ($cabangs as $c)
                                <option value="{{ $c->id }}">{{ $c->nama }}</option>
                            @endforeach
                        </select>
                        @error('cabang_id')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Minggu</label>
                        <select wire:model="minggu" class="form-select form-select-sm">
                            @for ($m = 1; $m <= 5; $m++)
                                <option value="{{ $m }}">Minggu {{ $m }}</option>
                            @endfor
                        </select>
                        @error('minggu')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Nama Pemda</label>
                        <input wire:model="nama_pemda" class="form-control form-control-sm" placeholder="mis. TPG Provinsi Bali">
                        <small class="text-muted">Maks. {{ $maxPemdaPerMinggu }} per minggu per cabang · tidak boleh duplikat.</small>
                        @error('nama_pemda')<small class="text-danger d-block">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">PIC</label>
                        <input wire:model="pic" class="form-control form-control-sm" placeholder="mis. Septi">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small">Tagihan (Rp)</label>
                        <input type="number" step="0.01" wire:model.live.debounce.500ms="tagihan" class="form-control form-control-sm text-end">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Status Bayar</label>
                        <select wire:model.live="status_bayar" class="form-select form-select-sm">
                            <option value="belum">Belum</option>
                            <option value="sebagian">Sebagian</option>
                            <option value="sudah">Sudah</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Outstanding (Rp)</label>
                        <input type="number" step="0.01" wire:model="outstanding" class="form-control form-control-sm text-end">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Target Penyelesaian</label>
                        <input type="date" wire:model="target_penyelesaian" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small">Kendala</label>
                        <textarea rows="2" wire:model="kendala" class="form-control form-control-sm" placeholder="Deskripsi kendala..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Keterangan</label>
                        <textarea rows="2" wire:model="keterangan" class="form-control form-control-sm" placeholder="Catatan tambahan..."></textarea>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm"
                            @click="swalConfirm('Simpan data iuran ini?', () => $wire.save())">
                            {{ $editingId ? 'Update' : 'Simpan' }}
                        </button>
                        @if ($editingId)
                            <button type="button" wire:click="resetForm" class="btn btn-secondary btn-sm">Batal</button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <strong>Daftar Iuran — {{ $bulanLabels[$bulan] }} {{ $tahun }}</strong>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if (! $isCabangUser)
                    <select wire:model.live="filter_cabang_id" class="form-select form-select-sm" style="min-width:180px; height:31px">
                        <option value="">Semua Cabang</option>
                        @foreach ($cabangs as $c)
                            <option value="{{ $c->id }}">{{ $c->nama }}</option>
                        @endforeach
                    </select>
                @endif
                <select wire:model.live="bulan" class="form-select form-select-sm" style="width:140px; height:31px">
                    @foreach ($bulanLabels as $no => $nm)
                        <option value="{{ $no }}">{{ $nm }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filter_minggu" class="form-select form-select-sm" style="width:140px; height:31px">
                    <option value="">Semua Minggu</option>
                    @for ($m = 1; $m <= 5; $m++)
                        <option value="{{ $m }}">Minggu {{ $m }}</option>
                    @endfor
                </select>
                <select wire:model.live="filter_status" class="form-select form-select-sm" style="width:150px; height:31px">
                    <option value="">Semua Status</option>
                    <option value="sudah">Sudah</option>
                    <option value="sebagian">Sebagian</option>
                    <option value="belum">Belum</option>
                </select>
                <button type="button" wire:click="resetFilters" class="btn btn-outline-secondary btn-sm" style="height:31px">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button type="button" wire:click="exportExcel" class="btn btn-success btn-sm" style="height:31px">
                    <i class="bi bi-file-earmark-excel"></i> Excel
                </button>
                <button type="button" wire:click="exportPdf" class="btn btn-danger btn-sm" style="height:31px">
                    <i class="bi bi-file-earmark-pdf"></i> PDF
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive" style="max-height: 60vh; overflow: auto;">
                <table class="table table-sm table-bordered align-middle mb-0 iuran-table" style="min-width: 1500px; font-size: .8rem;">
                    <thead class="table-light text-center" style="position: sticky; top: 0; z-index: 2;">
                        <tr>
                            <th style="width:50px">No</th>
                            <th style="width:100px">Bulan</th>
                            <th style="min-width:110px; white-space:nowrap">Minggu</th>
                            @if (! $isCabangUser)<th style="min-width:140px">Kantor Cabang</th>@endif
                            <th style="min-width:200px">Nama Pemda</th>
                            <th style="width:140px">Tagihan</th>
                            <th style="width:110px">Bayar</th>
                            <th style="width:140px">Outstanding</th>
                            <th style="width:130px">Target Penyelesaian</th>
                            <th style="min-width:140px; white-space:nowrap">PIC</th>
                            <th style="min-width:220px">Kendala</th>
                            <th style="min-width:220px">Keterangan</th>
                            <th style="width:140px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $i => $r)
                                <tr>
                                    <td class="text-center">{{ $i + 1 }}</td>
                                    <td class="text-center bg-light">{{ $bulanLabels[$r->bulan] ?? $r->bulan }}</td>
                                    <td class="text-center fw-semibold bg-light" style="white-space:nowrap">Minggu {{ $r->minggu }}</td>
                                    @if (! $isCabangUser)<td>{{ $r->cabang?->nama }}</td>@endif
                                    <td>{{ $r->nama_pemda }}</td>
                                    <td class="text-end">{{ number_format((float) $r->tagihan, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        @if ($r->status_bayar === 'sudah')
                                            <span class="badge bg-success">SUDAH</span>
                                        @elseif ($r->status_bayar === 'sebagian')
                                            <span class="badge bg-warning text-dark">SEBAGIAN</span>
                                        @else
                                            <span class="badge bg-danger">BELUM</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format((float) $r->outstanding, 0, ',', '.') }}</td>
                                    <td class="text-center">{{ $r->target_penyelesaian?->format('d-M-y') }}</td>
                                    <td style="white-space:nowrap">{{ $r->pic }}</td>
                                    <td style="white-space: pre-wrap;">{{ $r->kendala }}</td>
                                    <td style="white-space: pre-wrap;">{{ $r->keterangan }}</td>
                                    <td class="text-nowrap">
                                        <button wire:click="edit({{ $r->id }})" class="btn btn-warning btn-sm">Edit</button>
                                        <button type="button"
                                            @click="swalConfirm('Hapus data {{ \Illuminate\Support\Str::limit($r->nama_pemda, 30) }}?', () => $wire.delete({{ $r->id }}), {icon: 'warning', confirmText: 'Ya, Hapus', confirmColor: '#dc3545'})"
                                            class="btn btn-danger btn-sm">Hapus</button>
                                    </td>
                                </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isCabangUser ? 12 : 13 }}" class="text-center text-muted">Belum ada data iuran sesuai filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
