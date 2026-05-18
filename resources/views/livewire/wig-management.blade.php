<div>
    <h3>WIG (Wildly Important Goals)</h3>

    <div class="card mb-3">
        <div class="card-header"><strong>{{ $editingId ? 'Edit' : 'Tambah' }} WIG</strong></div>
        <div class="card-body">
            <form wire:submit="save">
                <div class="row g-2">
                    <div class="col-md-3"><label class="form-label small">Kode WIG</label>
                        <input wire:model="kode_wig" class="form-control form-control-sm">
                        @error('kode_wig')<small class="text-danger">{{ $message }}</small>@enderror</div>
                    <div class="col-md-5"><label class="form-label small">Nama WIG</label>
                        <input wire:model="nama_wig" class="form-control form-control-sm">
                        @error('nama_wig')<small class="text-danger">{{ $message }}</small>@enderror</div>
                    <div class="col-md-2"><label class="form-label small">Bidang</label>
                        <select wire:model="bidang" class="form-select form-select-sm">
                            <option value="">- Pilih -</option>
                            @foreach(\App\Models\Wig::BIDANG as $b)
                                <option value="{{ $b }}">{{ $b }}</option>
                            @endforeach
                        </select>
                        @error('bidang')<small class="text-danger">{{ $message }}</small>@enderror</div>
                    <div class="col-md-2"><label class="form-label small">Tahun</label>
                        <input type="number" wire:model="tahun" class="form-control form-control-sm">
                        @error('tahun')<small class="text-danger">{{ $message }}</small>@enderror</div>
                    <div class="col-md-8"><label class="form-label small">Indikator Output</label>
                        <textarea wire:model="indikator_output" rows="2" class="form-control form-control-sm"></textarea></div>
                    <div class="col-md-4"><label class="form-label small">Wilayah</label>
                        <select wire:model="wilayah_id" class="form-select form-select-sm"><option value="">-</option>
                            @foreach($wilayahs as $w)<option value="{{ $w->id }}">{{ $w->nama }}</option>@endforeach
                        </select></div>
                    <div class="col-md-12 d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-primary btn-sm" @click="swalConfirm('Simpan data WIG ini?', () => $wire.save())">Simpan</button>
                        @if($editingId)<button type="button" wire:click="$set('editingId', null)" class="btn btn-secondary btn-sm">Batal</button>@endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Daftar WIG</strong></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead class="table-light"><tr><th style="width:50px">#</th><th>Kode</th><th>Nama</th><th>Bidang</th><th>Indikator</th><th>Tahun</th><th>Wilayah</th><th></th></tr></thead>
                    <tbody>
                    @forelse($wigs as $w)
                        <tr><td>{{ $wigs->firstItem() + $loop->index }}</td><td>{{ $w->kode_wig }}</td><td>{{ $w->nama_wig }}</td>
                            <td>{!! $w->bidang ? '<span class="badge bg-info">'.$w->bidang.'</span>' : '-' !!}</td>
                            <td>{{ Str::limit($w->indikator_output, 40) }}</td>
                            <td>{{ $w->tahun }}</td><td>{{ $w->wilayah?->nama }}</td>
                            <td>
                                <button wire:click="edit({{ $w->id }})" class="btn btn-warning btn-sm">Edit</button>
                                <button type="button" @click="swalConfirm('Hapus WIG {{ $w->nama_wig }}?', () => $wire.delete({{ $w->id }}), {icon: 'warning', confirmText: 'Ya, Hapus', confirmColor: '#dc3545'})" class="btn btn-danger btn-sm">Hapus</button>
                            </td></tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">Belum ada data</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $wigs->links() }}
        </div>
    </div>
</div>
