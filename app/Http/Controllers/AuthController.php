<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Masuk lewat nama (akun unit kerja) atau NPP (akun pegawai).
     *
     * Kedua jenis akun tinggal di tabel `users` yang sama, jadi kolom
     * pengenalnya dipilih dari `mode` yang dikirim form — bukan ditebak dari
     * isi kolomnya. Tanpa syarat `tipe` pada attempt(), akun unit kerja yang
     * kebetulan ber-NPP bisa ikut lolos lewat tab pegawai.
     */
    public function login(Request $request)
    {
        $pegawai = $request->input('mode') === 'pegawai';
        $kolom = $pegawai ? 'npp' : 'name';

        $request->validate([
            $kolom => ['required', 'string'],
            'password' => ['required'],
        ], [], [$kolom => $pegawai ? 'NPP' : 'nama']);

        $kredensial = [
            $kolom => $request->input($kolom),
            'password' => $request->input('password'),
            'tipe' => $pegawai ? 'pegawai' : 'unit_kerja',
        ];

        if (Auth::attempt($kredensial, $request->boolean('remember'))) {
            if (! Auth::user()->is_active) {
                Auth::logout();

                return back()->withErrors([$kolom => 'Akun nonaktif.']);
            }
            $request->session()->regenerate();

            /*
             | Selalu ke /apps, bukan langsung ke dashboard sebuah modul.
             |
             | /apps yang memutuskan: user dengan satu modul langsung
             | dialihkan ke sana, yang punya dua diberi pilihan. Sebelumnya
             | tujuannya dipatok ke /dashboard (modul 4DX), sehingga akun
             | pegawai — yang hanya berhak atas modul PM — langsung menabrak
             | 403 tepat setelah login.
             |
             | intended() sengaja tidak dipakai: modul 4DX menempati URL root
             | sehingga tujuan tersimpan seperti /wigs tidak bisa diperiksa
             | hak aksesnya di sini tanpa menebak-nebak.
             */
            return redirect('/apps');
        }

        return back()
            ->withErrors([$kolom => $pegawai ? 'NPP atau password salah.' : 'Nama atau password salah.'])
            ->onlyInput($kolom);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
