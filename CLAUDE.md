# Monitoring 4DX – Kedeputian Wilayah XI

## Tech Stack
- Backend: Laravel
- Frontend: Laravel Livewire
- Database: MySQL
- Authentication: Laravel Breeze / Jetstream
- UI Framework: Bootstrap / Tailwind CSS
- Reporting: Laravel Excel / DomPDF

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
- Gunakan Repository Pattern untuk Model
- Setiap fitur gunakan Livewire Component
- Validasi input wajib di Form Request
- Gunakan Policy Laravel untuk kontrol akses per role