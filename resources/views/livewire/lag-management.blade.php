<div>
    <h3>Lag Measure</h3>

    <div class="card mb-3">
        <div class="card-header"><strong>{{ $editingId ? 'Edit' : 'Tambah' }} Lag</strong></div>
        <div class="card-body">
            <form wire:submit="save">
                <div class="row g-2">
                    {{-- Baris 1: Pilih WIG + Pilih Cabang --}}
                    <div class="col-md-6">
                        <label class="form-label small">Pilih WIG</label>
                        <select wire:model.live="wig_id" class="form-select form-select-sm">
                            <option value="">- Pilih WIG -</option>
                            @foreach($wigs as $w)
                                <option value="{{ $w->id }}" title="{{ $w->nama_wig }}">
                                    {{ $w->kode_wig }} — {{ \Illuminate\Support\Str::limit($w->nama_wig, 70) }}
                                </option>
                            @endforeach
                        </select>
                        @error('wig_id')<small class="text-danger">{{ $message }}</small>@enderror
                        @php $selectedWig = $wigs->firstWhere('id', $wig_id); @endphp
                        @if($selectedWig)
                            <div class="border rounded p-2 mt-2 bg-light small">
                                <div>
                                    <span class="badge bg-primary">{{ $selectedWig->kode_wig }}</span>
                                    @if($selectedWig->bidang)<span class="badge bg-info">{{ $selectedWig->bidang }}</span>@endif
                                </div>
                                <div class="mt-1 text-muted" style="white-space: pre-wrap; word-break: break-word;">{{ $selectedWig->nama_wig }}</div>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Pilih Kantor Cabang</label>
                        <select wire:model.live="cabang_id" class="form-select form-select-sm">
                            <option value="">- Pilih Cabang -</option>
                            @foreach($cabangs as $c)
                                <option value="{{ $c->id }}">{{ $c->nama }}</option>
                            @endforeach
                        </select>
                        @error('cabang_id')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    {{-- Baris 2: Kode Lag + Nama Lag + Target Waktu --}}
                    <div class="col-md-3">
                        <label class="form-label small">Kode Lag <span class="text-muted">(otomatis)</span></label>
                        <input wire:model="kode_lag" class="form-control form-control-sm" readonly>
                        @error('kode_lag')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Nama Lag</label>
                        <textarea wire:model="nama_lag" rows="3" class="form-control form-control-sm" placeholder="Deskripsi Lag Measure..."></textarea>
                        @error('nama_lag')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-3">

                        <label class="form-label small">Target Waktu</label>
                        <input type="date" wire:model="tanggal_target" class="form-control form-control-sm">
                        @error('tanggal_target')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-md-12 d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-primary btn-sm" @click="swalConfirm('Simpan data Lag Measure ini?', () => $wire.save())">Simpan</button>
                        @if($editingId)
                            <button type="button" wire:click="$set('editingId', null)" class="btn btn-secondary btn-sm">Batal</button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Daftar Lag Measure</strong></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">#</th>
                            <th>Kode</th>
                            <th>WIG</th>
                            <th>Nama</th>
                            <th>Cabang</th>
                            <th>Target Waktu</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($lags as $l)
                        <tr>
                            <td>{{ $lags->firstItem() + $loop->index }}</td>
                            <td>{{ $l->kode_lag }}</td>
                            <td>{{ $l->wig?->kode_wig }}</td>
                            <td>{{ $l->nama_lag }}</td>
                            <td>{{ $l->cabang?->nama ?? '-' }}</td>
                            <td>{{ $l->tanggal_target ? \Carbon\Carbon::parse($l->tanggal_target)->translatedFormat('d M Y') : '-' }}</td>
                            <td>
                                <button wire:click="edit({{ $l->id }})" class="btn btn-warning btn-sm">Edit</button>
                                <button type="button"
                                    @click="swalConfirm('Hapus Lag {{ $l->nama_lag }}?', () => $wire.delete({{ $l->id }}), {icon: 'warning', confirmText: 'Ya, Hapus', confirmColor: '#dc3545'})"
                                    class="btn btn-danger btn-sm">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">Belum ada data</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $lags->links() }}
        </div>
    </div>
</div>
