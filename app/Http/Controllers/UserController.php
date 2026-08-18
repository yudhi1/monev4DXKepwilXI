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

/**
 * Kelola akun unit kerja — pengguna modul Monev 4DX.
 *
 * Akun perorangan pegawai (modul Project Management) diurus terpisah oleh
 * PegawaiController: field dan role-nya berbeda, jadi layarnya pun dipisah
 * meski keduanya tersimpan di tabel `users` dan dibedakan kolom `tipe`.
 */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $cari = trim((string) $request->query('cari', ''));
        $role = $request->query('role');

        $users = User::query()
            ->akunUnitKerja()
            ->with(['roles:id,name', 'wilayah:id,nama', 'cabang:id,nama'])
            ->when($cari !== '', fn ($q) => $q->where('name', 'like', "%{$cari}%"))
            ->when($role, fn ($q) => $q->whereHas('roles', fn ($sub) => $sub->where('name', $role)))
            // Yang aktif didahulukan; user nonaktif turun ke bawah.
            ->orderByDesc('is_active')
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
            'tipe' => 'unit_kerja',
            'email' => $this->emailDariNama($data['name']),
            'password' => Hash::make($data['password']),
        ]);

        $user->syncRoles([$data['role']]);

        return back()->with('success', 'Akun berhasil ditambahkan.');
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        abort_if($user->adalahPegawai(), 404);

        $data = $request->validated();
        $payload = collect($data)->except(['password', 'role'])->all();

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
        $user->syncRoles([$data['role']]);

        return back()->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->adalahPegawai(), 404);

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return back()->with('success', 'Akun berhasil dihapus.');
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
