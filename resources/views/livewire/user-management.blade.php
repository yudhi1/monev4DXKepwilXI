<div>
    <h3>Kelola User</h3>

    <div class="card mb-3">
        <div class="card-header"><strong>{{ $editingId ? 'Edit' : 'Tambah' }} User</strong></div>
        <div class="card-body">
            <form wire:submit="save">
                <div class="row g-2">
                    <div class="col-md-4"><label class="form-label small">Nama (untuk login)</label>
                        <input wire:model="name" class="form-control form-control-sm">
                        @error('name')<small class="text-danger">{{ $message }}</small>@enderror</div>
                    <div class="col-md-3"><label class="form-label small">Password {{ $editingId ? '(opsional)' : '' }}</label>
                        <input type="password" wire:model="password" class="form-control form-control-sm">
                        @error('password')<small class="text-danger">{{ $message }}</small>@enderror</div>
                    <div class="col-md-2"><label class="form-label small">Role</label>
                        <select wire:model="role" class="form-select form-select-sm">
                            <option value="admin">Admin</option>
                            <option value="kedeputian_wilayah">Kedeputian Wilayah</option>
                            <option value="kantor_cabang">Kantor Cabang</option>
                        </select></div>
                    <div class="col-md-2"><label class="form-label small">Wilayah</label>
                        <select wire:model="wilayah_id" class="form-select form-select-sm"><option value="">-</option>
                            @foreach($wilayahs as $w)<option value="{{ $w->id }}">{{ $w->nama }}</option>@endforeach
                        </select></div>
                    <div class="col-md-3"><label class="form-label small">Cabang</label>
                        <select wire:model="cabang_id" class="form-select form-select-sm"><option value="">-</option>
                            @foreach($cabangs as $c)<option value="{{ $c->id }}">{{ $c->nama }}</option>@endforeach
                        </select></div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check"><input type="checkbox" wire:model="is_active" class="form-check-input" id="ua">
                            <label class="form-check-label" for="ua">Aktif</label></div>
                    </div>
                    <div class="col-12"><label class="form-label small">Alamat</label>
                        <textarea wire:model="alamat" class="form-control form-control-sm" rows="2" placeholder="Alamat lengkap (opsional)"></textarea>
                        @error('alamat')<small class="text-danger">{{ $message }}</small>@enderror</div>
                    <div class="col-md-7 d-flex align-items-end gap-2">
                        <button type="button" class="btn btn-primary btn-sm" @click="swalConfirm('Simpan data user ini?', () => $wire.save())">Simpan</button>
                        @if($editingId)<button type="button" wire:click="$set('editingId', null)" class="btn btn-secondary btn-sm">Batal</button>@endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Daftar User</strong>
            <input wire:model.live.debounce.300ms="search" placeholder="Cari nama/email" class="form-control form-control-sm" style="width:240px">
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead class="table-light"><tr><th style="width:50px">#</th><th>Nama</th><th>Role</th><th>Wilayah</th><th>Cabang</th><th>Alamat</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($users as $u)
                        <tr>
                            <td>{{ $users->firstItem() + $loop->index }}</td>
                            <td>{{ $u->name }}</td>
                            <td>{{ $u->roles->pluck('name')->join(', ') }}</td>
                            <td>{{ $u->wilayah?->nama }}</td><td>{{ $u->cabang?->nama }}</td>
                            <td>{{ $u->alamat ? Str::limit($u->alamat, 40) : '-' }}</td>
                            <td>{!! $u->is_active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>' !!}</td>
                            <td>
                                <button wire:click="edit({{ $u->id }})" class="btn btn-warning btn-sm">Edit</button>
                                <button type="button" @click="swalConfirm('Hapus user {{ $u->name }}?', () => $wire.delete({{ $u->id }}), {icon: 'warning', confirmText: 'Ya, Hapus', confirmColor: '#dc3545'})" class="btn btn-danger btn-sm">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">Belum ada data</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $users->links() }}
        </div>
    </div>
</div>
