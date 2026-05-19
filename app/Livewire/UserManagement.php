<?php

namespace App\Livewire;

use App\Models\Cabang;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public ?int $editingId = null;

    public string $name = '';

    public string $password = '';

    public string $role = 'kantor_cabang';

    public ?int $wilayah_id = null;

    public ?int $cabang_id = null;

    public bool $is_active = true;

    public string $search = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:users,name,'.$this->editingId,
            'password' => $this->editingId ? 'nullable|min:6' : 'required|min:6',
            'role' => 'required|in:admin,kedeputian_wilayah,kantor_cabang',
            'wilayah_id' => 'nullable|exists:wilayahs,id',
            'cabang_id' => 'nullable|exists:cabangs,id',
            'is_active' => 'boolean',
        ];
    }

    private function emailFromName(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name, '.') ?: 'user';
        $email = $base.'@monev.local';
        $i = 1;
        while (User::where('email', $email)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $email = $base.($i++).'@monev.local';
        }

        return $email;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $u = User::findOrFail($id);
        $this->editingId = $u->id;
        $this->name = $u->name;
        $this->password = '';
        $this->role = $u->getRoleNames()->first() ?? 'kantor_cabang';
        $this->wilayah_id = $u->wilayah_id;
        $this->cabang_id = $u->cabang_id;
        $this->is_active = $u->is_active;
    }

    public function save(): void
    {
        $data = $this->validate();
        $payload = collect($data)->except(['password', 'role'])->toArray();
        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->update($payload);
        } else {
            $payload['email'] = $this->emailFromName($data['name']);
            $payload['password'] = Hash::make($data['password']);
            $user = User::create($payload);
        }

        $user->syncRoles([$data['role']]);
        $this->reset(['editingId', 'name', 'password', 'role', 'wilayah_id', 'cabang_id']);
        $this->is_active = true;
        session()->flash('success', 'User tersimpan.');
    }

    public function delete(int $id): void
    {
        User::findOrFail($id)->delete();
        session()->flash('success', 'User dihapus.');
    }

    public function render()
    {
        return view('livewire.user-management', [
            'users' => User::with('roles', 'wilayah', 'cabang')
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->latest()->paginate(10),
            'wilayahs' => Wilayah::orderBy('nama')->get(),
            'cabangs' => Cabang::orderBy('nama')->get(),
        ])->layout('layouts.app');
    }
}
