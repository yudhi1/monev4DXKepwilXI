<div>
    <h3>Master Pemda</h3>
    <p class="text-muted small">Daftar Pemda yang dimonitor per kantor cabang (maks. {{ $maxPerCabang }} Pemda per cabang).</p>

    <div class="card mb-3">
        <div class="card-header"><strong>{{ $editingId ? 'Edit' : 'Tambah' }} Pemda</strong></div>
        <div class="card-body">
            <form wire:submit="save">
                <div class="row g-3">
                    @php $isCabangUser = auth()->user()?->hasRole('kantor_cabang'); @endphp
                    <div class="col-md-4">
                        <label class="form-label small">Kantor Cabang</label>
                        <select wire:model="cabang_id" class="form-select form-select-sm" @if($isCabangUser) disabled @endif>
                            <option value="">- Pilih Cabang -</option>
                            @foreach ($cabangs as $c)
                                <option value="{{ $c->id }}">{{ $c->nama }}</option>
                            @endforeach
                        </select>
                        @error('cabang_id')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Nama Pemda</label>
                        <input wire:model="nama" class="form-control form-control-sm" placeholder="mis. TPG Provinsi Bali">
                        @error('nama')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Keterangan <span class="text-muted">(opsional)</span></label>
                        <input wire:model="keterangan" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm"
                            @click="swalConfirm('Simpan data Pemda ini?', () => $wire.save())">
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
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Daftar Pemda per Cabang</strong>
            <div style="min-width: 240px">
                <select wire:model.live="filter_cabang_id" class="form-select form-select-sm">
                    <option value="">Semua Cabang</option>
                    @foreach ($cabangs as $c)
                        <option value="{{ $c->id }}">{{ $c->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th style="width:50px">No</th>
                            <th>Kantor Cabang</th>
                            <th>Nama Pemda</th>
                            <th>Keterangan</th>
                            <th style="width:140px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $rowNo = 0; @endphp
                        @forelse ($cabangs as $c)
                            @php $grup = $pemdas[$c->id] ?? collect(); @endphp
                            @if ($grup->isEmpty())
                                <tr>
                                    <td class="text-center text-muted">-</td>
                                    <td>{{ $c->nama }}</td>
                                    <td colspan="3" class="text-muted">Belum ada Pemda terdaftar.</td>
                                </tr>
                            @else
                                @foreach ($grup as $i => $p)
                                    @php $rowNo++; @endphp
                                    <tr>
                                        <td class="text-center">{{ $rowNo }}</td>
                                        @if ($i === 0)
                                            <td rowspan="{{ count($grup) }}" class="align-middle bg-light fw-semibold">
                                                {{ $c->nama }}
                                                <div class="small text-muted">{{ count($grup) }}/{{ $maxPerCabang }}</div>
                                            </td>
                                        @endif
                                        <td>{{ $p->nama }}</td>
                                        <td>{{ $p->keterangan }}</td>
                                        <td class="text-nowrap">
                                            <button wire:click="edit({{ $p->id }})" class="btn btn-warning btn-sm">Edit</button>
                                            <button type="button"
                                                @click="swalConfirm('Hapus Pemda {{ \Illuminate\Support\Str::limit($p->nama, 30) }}?', () => $wire.delete({{ $p->id }}), {icon: 'warning', confirmText: 'Ya, Hapus', confirmColor: '#dc3545'})"
                                                class="btn btn-danger btn-sm">Hapus</button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Tidak ada cabang.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
