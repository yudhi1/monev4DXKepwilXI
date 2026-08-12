<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Cabang;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $cari = trim((string) $request->query('cari', ''));
        $role = $request->query('role');

        $users = User::query()
            ->with(['roles:id,name', 'wilayah:id,nama', 'cabang:id,nama'])
            ->when($cari !== '', fn ($q) => $q->where('name', 'like', "%{$cari}%"))
            ->when($role, fn ($q) => $q->whereHas('roles', fn ($sub) => $sub->where('name', $role)))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => (bool) $user->is_active,
                'alamat' => $user->alamat,
                'role' => $user->getRoleNames()->first(),
                'wilayah_id' => $user->wilayah_id,
                'cabang_id' => $user->cabang_id,
                'wilayah' => $user->wilayah?->nama,
                'cabang' => $user->cabang?->nama,
            ]);

        return Inertia::render('User/Index', [
            'users' => $users,
            'wilayahs' => Wilayah::orderBy('nama')->get(['id', 'nama']),
            'cabangs' => Cabang::orderBy('nama')->get(['id', 'nama', 'wilayah_id']),
            'filter' => ['cari' => $cari, 'role' => $role],
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            ...collect($data)->except(['password', 'role'])->all(),
            'email' => $this->emailDariNama($data['name']),
            'password' => Hash::make($data['password']),
        ]);

        $user->syncRoles([$data['role']]);

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $payload = collect($data)->except(['password', 'role'])->all();

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
        $user->syncRoles([$data['role']]);

        return back()->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }

    /**
     * Email tidak diisi manual oleh admin — diturunkan dari nama dan
     * dijamin unik, sama seperti perilaku UserManagement (Livewire).
     */
    private function emailDariNama(string $nama): string
    {
        $dasar = Str::slug($nama, '.') ?: 'user';
        $email = $dasar.'@monev.local';
        $i = 1;

        while (User::where('email', $email)->exists()) {
            $email = $dasar.($i++).'@monev.local';
        }

        return $email;
    }
}
