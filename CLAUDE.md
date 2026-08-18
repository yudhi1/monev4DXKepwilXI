# Monitoring 4DX – Kedeputian Wilayah XI

## Tech Stack
- Backend: Laravel
- Frontend: Inertia.js + Vue 3 (SPA, komponen di `resources/js/Pages/`)
- Database: MySQL
- Authentication: sesi Laravel bawaan via `AuthController` (bukan Breeze/Jetstream)
- UI Framework: Tailwind CSS 4 + shadcn-vue (reka-ui), ikon Lucide
- Grafik: Chart.js via vue-chartjs
- Reporting: Laravel Excel / DomPDF

> Livewire sudah tidak dipakai lagi — seluruh halaman dimigrasi ke Inertia + Vue
> pada Agustus 2026 dan paketnya dilepas. Blade hanya tersisa untuk shell Inertia
> (`resources/views/inertia.blade.php`) dan template PDF DomPDF.

## Package yang Digunakan
- spatie/laravel-permission → Role & Permission
- maatwebstra/excel → Export Excel
- barryvdh/laravel-dompdf → Export PDF
- spatie/laravel-activitylog → Audit Trail

## User Role & Hak Akses
- Admin: CRUD semua data, kelola user, monitoring seluruh wilayah & cabang, export laporan
- Kedeputian Wilayah: Buat WIG, Lag Measure, Lead Measure, monitoring capaian wilayah
- Kantor Cabang: Input realisasi Lead Measure, lihat dashboard performa cabang

## Struktur Database
Tabel utama:
- users
- wigs (kode_wig, nama_wig, indikator_output, tahun)
- lag_measures (kode_lag, wig_id, tahun)
- lead_measures (kode_lead, lag_measure_id, wig_id, tahun)
- lead_measure_realisasis (lead_measure_id, minggu, bulan, tahun, target, realisasi)

## Relasi Data
- 1 WIG → banyak Lag Measure
- 1 Lag Measure → banyak Lead Measure
- 1 Lead Measure → banyak Target & Realisasi Mingguan

## Fitur Utama
1. Dashboard (statistik WIG, grafik pencapaian, ranking kantor cabang)
2. CRUD WIG
3. CRUD Lag Measure
4. CRUD Lead Measure
5. Input Realisasi Mingguan per Kantor Cabang
6. Dashboard Wilayah & Dashboard Cabang
7. Export Excel & PDF
8. Audit Trail

## Tahapan Pengembangan
- Phase 1: Authentication & Role Management
- Phase 2: CRUD WIG, Lag Measure, Lead Measure
- Phase 3: Input Realisasi Mingguan
- Phase 4: Dashboard & Reporting

## Pemeriksaan Sebelum Commit
- `npm run lint` — **wajib** untuk perubahan Vue. `npm run build` TIDAK menangkap
  variabel/komponen yang dipakai tanpa diimpor (mis. `computed` lupa diimpor);
  Vue baru gagal saat render dan halaman tampil **kosong tanpa pesan error**.
  ESLint menangkapnya lewat `no-undef`.
- `./vendor/bin/pint` — gaya kode PHP (juga dijalankan pre-commit hook).

## Konvensi Kode
- Gunakan bahasa Indonesia untuk nama variabel domain (misal: $realisasi, $capaian)
- Setiap halaman = satu Controller yang mengembalikan `Inertia::render()` + satu komponen di `resources/js/Pages/`
- Navigasi antar halaman selalu pakai `<Link>` dari `@inertiajs/vue3`, bukan `<a>`
- Validasi input wajib di Form Request
- Kontrol akses per role lewat middleware `role:` (spatie) di `routes/web.php`

## Struktur Multi-Modul
Aplikasi ini menampung dua modul dengan **satu login** dan satu tabel `users`:

| Modul | URL | Kode |
|---|---|---|
| Monev 4DX | root (`/dashboard`, `/wigs`, …) | `App\Http\Controllers\*`, `Pages/*` |
| Project Management | `/pm` | `App\Http\Controllers\Pm\*`, `App\Models\Pm\*`, `Pages/Pm/*` |

- Modul dipilih di `/apps` setelah login; user yang hanya berhak atas satu modul langsung dialihkan.
- Daftar modul: `config/modul.php`. Akses modul disaring middleware `modul:<kunci>`.
- Menu sidebar per modul: `resources/js/layouts/menu.js`.
- Tabel PM berprefiks `pm_`. Nama rute PM berprefiks `pm.`.
- Status/prioritas/peran PM ada di `config/pm.php`, bukan enum MySQL.

## Struktur Organisasi
- `unit_kerjas` = bidang: 4 di Kedeputian Wilayah (JPK, PIKEU, KML, SDMUK) +
  6 bidang × 11 kantor cabang (Kepesertaan, Yanfaskes, Yanser, PMU, PKP, SDMU) = 70 unit.
  Perhatikan **SDMUK** di wilayah tapi **SDMU** di cabang.
- Bidang cabang berdiri sendiri per cabang — "PMU KC Denpasar" ≠ "PMU KC Kupang".

## Dua Jenis Akun (satu tabel `users`, kolom `tipe`)
| | `institusi` | `pegawai` |
|---|---|---|
| Modul | Monev 4DX | Project Management |
| Contoh | `admin`, `kepwil`, `kc.*` | perorangan, terikat satu bidang |
| Role | spatie (`admin`/`kedeputian_wilayah`/`kantor_cabang`) | kolom `pm_role` |
| Dikelola | `/users` | `/pegawai` |

- **Permission PM tidak boleh dilekatkan pada role spatie.** Role spatie milik 4DX.
  Akses PM diturunkan dari `pm_role` lewat `User::selaraskanIzinPm()`
  (pemetaannya di `config/pm.php`). Pegawai tidak punya role spatie sama sekali.
- Rute 4DX dijaga `modul:4dx`, rute PM dijaga `modul:pm`. Jangan menambah rute
  modul di luar grup itu — halaman tanpa middleware `role:` akan bocor ke modul lain.
- `User::scopePegawai()` vs `User::adalahPegawai()`: namanya sengaja berbeda supaya
  metode instance tidak menutupi scope saat dipanggil statis.

## Rencana Aktif
- [docs/RENCANA_RESTRUKTURISASI_MULTI_MODUL.md](docs/RENCANA_RESTRUKTURISASI_MULTI_MODUL.md) —
  Fase 0 (bersih-bersih Livewire) dan Fase 1 (kerangka modul) selesai.
  Fase 2 (memindahkan URL 4DX ke `/4dx`) **ditunda**.
- [docs/PRD_Modul_Project_Management.md](docs/PRD_Modul_Project_Management.md) —
  tahap Core sedang dikerjakan; Kolaborasi, Management, dan Dashboard Eksekutif belum.