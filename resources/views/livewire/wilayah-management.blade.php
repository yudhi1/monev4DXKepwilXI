<div>
    <h3>Kelola Wilayah</h3>

    <div class="card mb-3">
        <div class="card-header"><strong>{{ $editingId ? 'Edit' : 'Tambah' }} Wilayah</strong></div>
        <div class="card-body">
            <form wire:submit="save">
                <div class="row g-2">
                    <div class="col-md-3"><label class="form-label small">Kode</label>
                        <input wire:model="kode" class="form-control form-control-sm">
                        @error('kode')<small class="text-danger">{{ $message }}</small>@enderror</div>
                    <div class="col-md-4"><label class="form-label small">Nama</label>
                        <input wire:model="nama" class="form-control form-control-sm">
                        @error('nama')<small class="text-danger">{{ $message }}</small>@enderror</div>
                    <div class="col-md-5"><label class="form-label small">Deskripsi</label>
                        <input wire:model="deskripsi" class="form-control form-control-sm"></div>
                    <div class="col-md-12 d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-primary btn-sm" @click="swalConfirm('Simpan data wilayah ini?', () => $wire.save())">Simpan</button>
                        @if($editingId)<button type="button" wire:click="$set('editingId', null)" class="btn btn-secondary btn-sm">Batal</button>@endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Daftar Wilayah</strong></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead class="table-light"><tr><th style="width:50px">#</th><th>Kode</th><th>Nama</th><th>Deskripsi</th><th></th></tr></thead>
                    <tbody>
                    @forelse($wilayahs as $w)
                        <tr><td>{{ $wilayahs->firstItem() + $loop->index }}</td><td>{{ $w->kode }}</td><td>{{ $w->nama }}</td><td>{{ $w->deskripsi }}</td>
                            <td>
                                <button wire:click="edit({{ $w->id }})" class="btn btn-warning btn-sm">Edit</button>
                                <button type="button" @click="swalConfirm('Hapus wilayah {{ $w->nama }}?', () => $wire.delete({{ $w->id }}), {icon: 'warning', confirmText: 'Ya, Hapus', confirmColor: '#dc3545'})" class="btn btn-danger btn-sm">Hapus</button>
                            </td></tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">Belum ada data</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $wilayahs->links() }}
        </div>
    </div>
</div>
