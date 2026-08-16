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
- `unit_kerjas` = bidang: 4 di Kedeputian Wilayah (KML, JPK, PIKUE, SDMUK) +
  6 bidang × 11 kantor cabang (PMU, Yanfasskes, Kepesertaan, Yanser, PKP, SDMUK) = 70 unit.
- Bidang cabang berdiri sendiri per cabang — "PMU KC Denpasar" ≠ "PMU KC Kupang".
- `users.unit_kerja_id` terisi = akun pegawai perorangan (dipakai modul PM).
  Akun institusi lama (`admin`, `kepwil`, `kc.*`) kosong dan hanya untuk 4DX.
- Hak "pimpinan" (`pm.lihat-semua`) diberikan **per user**, bukan lewat role —
  role `kedeputian_wilayah` kini juga dipakai staf bidang.

## Rencana Aktif
- [docs/RENCANA_RESTRUKTURISASI_MULTI_MODUL.md](docs/RENCANA_RESTRUKTURISASI_MULTI_MODUL.md) —
  Fase 0 (bersih-bersih Livewire) dan Fase 1 (kerangka modul) selesai.
  Fase 2 (memindahkan URL 4DX ke `/4dx`) **ditunda**.
- [docs/PRD_Modul_Project_Management.md](docs/PRD_Modul_Project_Management.md) —
  tahap Core sedang dikerjakan; Kolaborasi, Management, dan Dashboard Eksekutif belum.