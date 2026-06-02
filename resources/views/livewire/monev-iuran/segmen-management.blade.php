<div class="container-fluid my-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="h4">Master Segmen</h2>
            <p class="text-muted">Kelola daftar segmen penerimaan iuran</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">{{ $editingId ? 'Edit Segmen' : 'Tambah Segmen' }}</h5>
                </div>
                <div class="card-body">
                    <form wire:submit="save">
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Segmen *</label>
                            <input type="text" class="form-control" id="nama" wire:model="nama">
                            @error('nama') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="urutan" class="form-label">Urutan</label>
                            <input type="number" class="form-control" id="urutan" wire:model="urutan" min="0">
                            @error('urutan') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" wire:model="is_active">
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ $editingId ? 'Update' : 'Tambah' }}</button>
                        @if ($editingId)
                            <button type="button" class="btn btn-secondary" wire:click="resetForm()">Batal</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Daftar Segmen</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">Urutan</th>
                                <th>Nama Segmen</th>
                                <th class="text-center" style="width: 80px;">Status</th>
                                <th class="text-center" style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($segmens as $segmen)
                                <tr>
                                    <td class="text-center">{{ $segmen->urutan }}</td>
                                    <td class="fw-bold">{{ $segmen->nama }}</td>
                                    <td class="text-center">
                                        @if ($segmen->is_active)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning" wire:click="edit({{ $segmen->id }})" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" wire:click="delete({{ $segmen->id }})" title="Hapus"
                                            onclick="return confirm('Hapus segmen ini?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Belum ada segmen.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
