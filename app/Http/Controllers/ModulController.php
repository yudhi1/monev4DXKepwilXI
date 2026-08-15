<?php

namespace App\Http\Controllers;

use App\Support\Modul;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ModulController extends Controller
{
    /**
     * Halaman pemilih modul setelah login.
     *
     * Kalau user hanya berhak atas satu modul, langsung diarahkan ke sana —
     * tidak ada gunanya memaksa satu klik untuk pilihan tunggal.
     */
    public function index(Request $request)
    {
        $daftar = Modul::untukUser($request->user());

        if (count($daftar) === 1) {
            return redirect($daftar[0]['beranda']);
        }

        if (count($daftar) === 0) {
            abort(403, 'Akun Anda belum diberi akses ke modul mana pun.');
        }

        return Inertia::render('Apps', [
            'modules' => $daftar,
        ]);
    }
}
