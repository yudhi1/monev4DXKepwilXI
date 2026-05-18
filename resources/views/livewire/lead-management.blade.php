<div>
    <h3>Input Lead Measure per Kantor Cabang</h3>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label small">Pilih WIG</label>
                    <select wire:model.live="wig_id" class="form-select form-select-sm">
                        <option value="">- Pilih WIG -</option>
                        @foreach ($wigs as $w)
                            <option value="{{ $w->id }}" title="{{ $w->nama_wig }}">
                                {{ $w->kode_wig }} — {{ \Illuminate\Support\Str::limit($w->nama_wig, 70) }}
                            </option>s
                        @endforeach
                    </select>
                    @php $selectedWig = $wigs->firstWhere('id', $wig_id); @endphp
                    @if ($selectedWig)
                        <div class="border rounded p-2 mt-2 bg-light small text-start">
                            <div style="text-align: left;">
                                <span class="badge bg-primary">{{ $selectedWig->kode_wig }}</span>
                                @if ($selectedWig->bidang)
                                    <span class="badge bg-info">{{ $selectedWig->bidang }}</span>
                                @endif
                            </div>
                            <div class="mt-1 text-muted" style="word-break: break-word;">{{ $selectedWig->nama_wig }}</div>
                        </div>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Pilih Kantor Cabang</label>
                    <select wire:model.live="cabang_id" class="form-select form-select-sm">
                        <option value="">- Pilih Cabang -</option>
                        @foreach ($cabangs as $c)
                            <option value="{{ $c->id }}">{{ $c->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    @if ($wig_id && $cabang_id)
        <div class="card mb-3">
            <div class="card-header"><strong>{{ $editingId ? 'Edit' : 'Tambah' }} Lead Measure</strong></div>
            <div class="card-body">
                <form wire:submit="save">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Lag Measure (parent)</label>
                            <select wire:model.live="lag_measure_id" class="form-select form-select-sm">
                                <option value="">- Pilih Lag -</option>
                                @foreach ($lags as $lg)
                                    <option value="{{ $lg->id }}" title="{{ $lg->nama_lag }}">
                                        {{ $lg->kode_lag }} — {{ \Illuminate\Support\Str::limit($lg->nama_lag, 70) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('lag_measure_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror

                            @php $selectedLag = $lags->firstWhere('id', $lag_measure_id); @endphp
                            @if ($selectedLag)
                                <div class="border rounded p-2 mt-2 bg-light small">
                                    <div><span class="badge bg-secondary">{{ $selectedLag->kode_lag }}</span></div>
                                    <div class="mt-1 text-muted" style="word-break: break-word;">{{ $selectedLag->nama_lag }}</div>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Kode Lead <span class="text-muted">(otomatis)</span></label>
                            <input wire:model="kode_lead" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small">Nama Lead</label>
                            <textarea wire:model="nama_lead" rows="2" class="form-control form-control-sm"
                                placeholder="Deskripsi Lead Measure..."></textarea>
                            @error('nama_lead')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-12 d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-primary btn-sm"
                                @click="swalConfirm('Simpan Lead Measure ini?', () => $wire.save())">Simpan</button>
                            @if ($editingId)
                                <button type="button" wire:click="$set('editingId', null)"
                                    class="btn btn-secondary btn-sm">Batal</button>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Daftar Lead Measure</strong></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px">#</th>
                                <th>Kode</th>
                                <th>Nama Lead</th>
                                <th>Lag Parent</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leads as $l)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $l->kode_lead }}</td>
                                    <td>{{ $l->nama_lead }}</td>
                                    <td>{{ $l->lagMeasure?->kode_lag }}</td>
                                    <td class="text-center">
                                        @if ($l->is_active)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap" style="min-width:280px">
                                        <div class="d-inline-flex gap-2">
                                            <button wire:click="edit({{ $l->id }})"
                                                class="btn btn-warning btn-sm">Edit</button>
                                            <button type="button"
                                                @click="swalConfirm('{{ $l->is_active ? 'Nonaktifkan' : 'Aktifkan' }} Lead ini?', () => $wire.toggleActive({{ $l->id }}), {icon: 'question', confirmText: 'Ya'})"
                                                class="btn btn-{{ $l->is_active ? 'secondary' : 'success' }} btn-sm">
                                                {{ $l->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                            <button type="button"
                                                @click="swalConfirm('Hapus Lead {{ \Illuminate\Support\Str::limit($l->nama_lead, 40) }}?', () => $wire.delete({{ $l->id }}), {icon: 'warning', confirmText: 'Ya, Hapus', confirmColor: '#dc3545'})"
                                                class="btn btn-danger btn-sm">Hapus</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Belum ada Lead Measure untuk
                                        kombinasi ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info">Pilih <strong>WIG</strong> dan <strong>Kantor Cabang</strong> dulu untuk mulai
            setup Lead Measure.</div>
    @endif
</div>
