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
- wigs (kode_wig, nama_wig, indikator_output, tahun, sifat_capaian, arah)
- lag_measures (kode_lag, wig_id, tahun)
- lead_measures (kode_lead, lag_measure_id, wig_id, tahun)
- lead_measure_realisasis (lead_measure_id, minggu, bulan, tahun, target, realisasi)

## Sifat Capaian WIG
Tidak semua WIG boleh dijumlahkan antar bulan. `wigs.sifat_capaian` menentukan
cara meringkas angka bulanan jadi capaian "s.d. bulan", dan `wigs.arah`
menentukan penilaiannya (naik/turun lebih baik). Pilihannya di `config/wig.php`,
bukan enum MySQL.

| sifat | s.d. bulan | contoh |
|---|---|---|
| `akumulatif` | penjumlahan | penerimaan iuran, biaya pelayanan |
| `posisi` | nilai bulan terakhir terisi | jumlah peserta aktif |
| `periodik` | rata-rata bulan terisi | persentase kepatuhan |

Rumusnya ada **dua salinan yang harus dijaga sinkron**: `App\Support\Wig\Capaian`
(export, dashboard) dan `resources/js/lib/capaianWig.js` (halaman Capaian).
`arah` hanya memengaruhi warna dan status tercapai, tidak mengubah aritmetika —
persentase tetap realisasi ÷ target, dibaca sebagai "berapa persen pagu terpakai"
untuk WIG efisiensi.

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
| Master Data | root (`/users`, `/pegawai`, `/unit-kerja`, `/wilayahs`, `/cabangs`) | `UserController`, `PegawaiController`, `UnitKerjaController`, … |
| Project Management | `/pm` | `App\Http\Controllers\Pm\*`, `App\Models\Pm\*`, `Pages/Pm/*` |
| Monitoring Kinerja | `/kinerja` | `App\Http\Controllers\Kinerja\*`, `App\Models\Kinerja\*`, `Pages/Kinerja/*` |

- **Master Data berdiri sendiri, bukan menu di dalam 4DX.** Isinya melayani kedua
  modul; dulu ia menu 4DX sehingga menambah Pegawai (urusan PM) harus lewat 4DX.
  Dijaga `modul:master` + `role:admin`.

- **Monitoring Kinerja juga berdiri sendiri.** Isinya berkas capaian yang
  disampaikan ke kantor cabang, bukan rangkaian WIG/Lag/Lead, jadi cabang tidak
  perlu menelusuri menu perencanaan 4DX hanya untuk mengunduh satu berkas.
  Tiga lapis: kategori → indikator → file (maks 2 MB, tabel `kinerja_*`).
  Admin & Kedeputian Wilayah menyusun dan mengunggah; kantor cabang hanya
  melihat dan mengunduh. Pembatasnya `modul:kinerja` + `role:` di rute —
  tidak ada permission `kinerja.*` tersendiri, supaya hak unggah hanya punya
  satu sumber kebenaran.

- Modul dipilih di `/apps` setelah login; user yang hanya berhak atas satu modul langsung dialihkan.
- Daftar modul: `config/modul.php`. Akses modul disaring middleware `modul:<kunci>`.
- **`beranda` sebuah modul wajib terbuka bagi semua pemegang permission-nya.**
  Kalau berandanya dijaga lebih ketat (mis. `role:admin`) daripada permission
  modulnya, user menabrak 403 tepat setelah mengklik kartunya di `/apps`.
- Menu sidebar per modul: `resources/js/layouts/menu.js`.
- Tabel PM berprefiks `pm_`. Nama rute PM berprefiks `pm.`.
- Status/prioritas/peran PM ada di `config/pm.php`, bukan enum MySQL.

## Struktur Organisasi
- `unit_kerjas` = bidang: 4 di Kedeputian Wilayah (JPK, PIKEU, KML, SDMUK) +
  6 bidang × 11 kantor cabang (Kepesertaan, Yanfaskes, Yanser, PMU, PKP, SDMU) = 70 unit.
  Perhatikan **SDMUK** di wilayah tapi **SDMU** di cabang.
- Bidang cabang berdiri sendiri per cabang — "PMU KC Denpasar" ≠ "PMU KC Kupang".

## Dua Jenis Akun (satu tabel `users`, kolom `tipe`)
| | `unit_kerja` | `pegawai` |
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