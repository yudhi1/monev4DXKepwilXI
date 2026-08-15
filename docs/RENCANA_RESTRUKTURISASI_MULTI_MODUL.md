# Rencana Restrukturisasi Multi-Modul

Menyiapkan aplikasi ini menampung dua modul — **Monev 4DX** (yang sudah ada) dan
**Project Management** (baru) — dalam satu aplikasi Laravel, satu database, dan
**satu login**.

Dokumen ini rencana kerja, bukan spesifikasi fitur PM. Fitur PM dibahas terpisah
setelah kerangka modul berdiri.

---

## 1. Keputusan Arsitektur

| Aspek | Keputusan |
|---|---|
| Autentikasi | **Satu** `/login`, satu tabel `users`, satu session. Tidak ada guard kedua. |
| Pemilihan modul | **Setelah** login, lewat halaman `/apps` + switcher di header. Bukan sebelum login. |
| Hak akses modul | Permission spatie: `akses-4dx`, `akses-pm`. Dicek via middleware. |
| URL | Semua rute modul diberi prefix: `/4dx/...` dan `/pm/...`. |
| Kode PHP | Namespace `App\Http\Controllers\FourDx\` dan `...\Pm\`, `App\Models\Pm\`. |
| Kode Vue | `resources/js/Pages/FourDx/` dan `Pages/Pm/`. |
| Database | Satu database. Tabel PM berprefiks `pm_` (`pm_projects`, `pm_tasks`, …). Tabel 4DX **tidak** diganti nama. |
| Data bersama | `users`, `wilayahs`, `cabangs`, `roles`, `permissions`, `activity_log` dipakai kedua modul. |

**Alasan tidak memisahkan login:** user-nya orang yang sama (pegawai Kepwil XI &
kantor cabang). Login terpisah berarti duplikasi akun, role, dan master wilayah/cabang
— setiap penambahan pegawai jadi dua kali kerja, dan sinkronisasinya manual selamanya.

---

## 2. Kondisi Awal (per 15 Agustus 2026, branch `feat/inertia-vue`)

Yang sudah ada dan menguntungkan:

- Seluruh 17 halaman sudah Inertia+Vue ([resources/js/Pages/](../resources/js/Pages/)); tidak ada rute Livewire tersisa di [routes/web.php](../routes/web.php).
- Auth sudah sederhana dan terpusat di [AuthController.php](../app/Http/Controllers/AuthController.php).
- Spatie permission sudah terpasang, di-seed lewat [RolePermissionSeeder.php](../database/seeders/RolePermissionSeeder.php).
- Layout tunggal [AppLayout.vue](../resources/js/layouts/AppLayout.vue) — satu tempat untuk pasang switcher modul.

Yang menghambat dan harus dibereskan dulu:

- **Sisa Livewire mati**: 15 komponen di [app/Livewire/](../app/Livewire/) + view-nya di
  [resources/views/livewire/](../resources/views/livewire/), plus layout Bootstrap lama
  [resources/views/layouts/app.blade.php](../resources/views/layouts/app.blade.php). Tidak ada rute
  yang memanggilnya lagi. Ini satu-satunya pemakai nama rute (`route('laporan')`, dll)
  di luar Inertia — selama masih ada, rename rute akan memecahkannya.
- **URL ditulis hardcoded di Vue**: ~66 kemunculan string seperti `'/wigs'`, `'/realisasi'`
  di `Pages/`, `components/`, `layouts/`. Semuanya harus disentuh saat prefix dipasang.
- `CLAUDE.md` masih menyebut stack Livewire — sudah tidak akurat.

---

## 3. Fase Kerja

Urutannya sengaja: setiap fase berdiri sendiri, bisa di-commit dan di-deploy tanpa
menunggu fase berikutnya.

### Fase 0 — Bersih-bersih (prasyarat) — ✅ SELESAI 15 Agustus 2026

Tujuan: hilangkan kode mati supaya restrukturisasi tidak menyeret dua stack sekaligus.

1. Hapus `app/Livewire/` dan `resources/views/livewire/`.
2. Hapus view Blade lama yang tak terpakai: `layouts/app.blade.php`, `panduan.blade.php`,
   `welcome.blade.php`, folder `auth/`. **Pertahankan** `inertia.blade.php`,
   `laporan/` dan `reports/` — dipakai export PDF DomPDF.
3. Hapus rute uji `/_inertia-check` dan `Pages/InertiaCheck.vue`
   ([web.php:128-131](../routes/web.php#L128-L131)).
4. Sederhanakan `AppLayout.vue`: buang `RUTE_INERTIA`, `sudahInertia()`, `tautan()`
   (baris 55-78) — semua rute sudah Inertia, jadi `<Link>` bisa dipakai langsung.
5. `composer remove livewire/livewire`.
6. Perbarui `CLAUDE.md`: frontend = Inertia + Vue 3 + Tailwind 4 + shadcn-vue (reka-ui).

Verifikasi: `php artisan route:list` tetap utuh, semua halaman terbuka, export PDF/Excel jalan.

**Catatan hasil:** `laporan/index.blade.php` ternyata ikut mati (ReportController@index
sudah `Inertia::render`), jadi dari folder `laporan/` hanya `pdf.blade.php` yang bertahan.
`composer remove livewire/livewire` gagal di mesin lokal karena PHP CLI 8.5.4 melampaui
batas `phpoffice/phpspreadsheet` di lock file — dijalankan ulang dengan
`--ignore-platform-req=php`. Ini mismatch lingkungan yang sudah ada sebelumnya;
sebaiknya `config.platform.php` di `composer.json` dipin ke versi PHP produksi
supaya resolusi dependensi deterministik.

### Fase 1 — Kerangka modul (belum pindah URL)

Tujuan: konsep "modul" hadir dan bisa dites, tanpa menyentuh satu URL pun.

1. **Permission modul.** Tambah `akses-4dx` dan `akses-pm` di `RolePermissionSeeder`.
   Berikan `akses-4dx` ke `admin`, `kedeputian_wilayah`, `kantor_cabang`;
   `akses-pm` ke `admin` saja untuk sekarang.
2. **Registry modul.** Buat `config/modul.php` — daftar modul: key, nama, deskripsi,
   ikon, URL beranda, permission yang dibutuhkan. Satu sumber kebenaran untuk halaman
   `/apps` dan switcher.
3. **Halaman `/apps`.** `ModulController@index` → `Pages/Apps.vue`, menampilkan kartu
   modul yang boleh diakses user. Kalau user hanya punya akses satu modul, langsung
   redirect ke modul itu (jangan paksa satu klik sia-sia).
4. **Middleware.** `EnsureModulAccess` — 403 kalau user tak punya permission modul.
5. **Switcher di header.** Dropdown di `AppLayout.vue` untuk pindah modul tanpa logout.
6. **Menu sidebar dipindah ke config.** Array `menu` yang sekarang hardcoded di
   `AppLayout.vue` (baris 87-142) dipecah jadi `config/menu/4dx.php` dan nanti
   `config/menu/pm.php`, dikirim ke Vue lewat Inertia shared props. Tanpa ini,
   `AppLayout` akan membengkak begitu PM masuk.

Verifikasi: user `kantor_cabang` login → langsung masuk 4DX (tak lihat `/apps`);
user `admin` → lihat `/apps` dengan satu kartu; buka `/pm` → 403.

### Fase 2 — Pindahkan 4DX ke `/4dx`

Fase paling berisiko. Kerjakan sekaligus dalam satu branch, jangan dicicil.

1. Bungkus seluruh grup rute 4DX di `web.php` dengan
   `Route::prefix('4dx')->name('4dx.')->middleware('modul:4dx')`.
   Nama rute jadi `4dx.wigs`, `4dx.laporan`, dst.
2. Pindahkan controller ke `App\Http\Controllers\FourDx\` (17 file) dan halaman Vue ke
   `Pages/FourDx/`; sesuaikan `Inertia::render()` di setiap controller.
3. Perbarui ~66 URL hardcoded di Vue. **Jangan** cari-ganti buta — sebagian string itu
   endpoint POST/PUT/DELETE di dalam form. Telusuri per file.
4. **Redirect kompatibilitas.** Tambahkan di akhir `web.php` redirect permanen dari
   URL lama ke baru (`/wigs` → `/4dx/wigs`, dst). Bookmark dan tautan yang sudah
   disebar ke cabang tidak boleh mati. Beri komentar rencana hapus, mis. setelah 6 bulan.
5. `/dashboard` ([web.php:33-39](../routes/web.php#L33-L39)) diubah: logika pilih
   dashboard berdasarkan role pindah ke `/4dx/dashboard`; `/dashboard` sendiri jadi
   redirect ke `/apps`.
6. `AuthController::login` → `redirect()->intended('/apps')`.

Verifikasi: telusuri manual tiap menu sidebar + tiap tombol simpan/hapus. Ini tidak
tercakup tes otomatis (belum ada tes fitur), jadi harus dicek tangan.

### Fase 3 — Kerangka modul PM

1. Rute `/pm` + `Pm\DashboardController` + `Pages/Pm/Dashboard.vue` (masih kosong).
2. `config/menu/pm.php`.
3. Migrasi tabel PM pertama sesuai desain fitur — semua berprefiks `pm_`.
4. Model di `App\Models\Pm\`.
5. Permission granular PM (`pm.project.create`, dst) ditambahkan di seeder.

### Fase 4 — Fitur PM

Di luar cakupan dokumen ini. Perlu PRD terpisah seperti
[PRD_Aplikasi_Monitoring_4DX.md](PRD_Aplikasi_Monitoring_4DX.md).

---

## 4. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| URL lama sudah tersebar ke kantor cabang, mati setelah Fase 2 | Redirect permanen 301 dari semua URL lama (Fase 2 langkah 4) |
| Salah ganti URL hardcoded di Vue → tombol simpan diam-diam rusak | Telusur manual per halaman; jangan sed global |
| Deploy produksi manual (`git reset --hard` via cron aaPanel) — tidak ada rollback otomatis | Deploy Fase 0/1/2 terpisah, jangan sekaligus; siapkan commit hash untuk rollback |
| Tabel PM bentrok nama dengan 4DX | Prefix `pm_` wajib |
| Nama rute bentrok antar modul | Prefix nama rute `4dx.` / `pm.` sejak Fase 2 |
| Suatu saat PM perlu dipisah jadi aplikasi sendiri | Batas namespace/prefix/tabel yang dijaga sejak awal membuat pemisahan itu mungkin tanpa membongkar 4DX |

---

## 5. Yang Perlu Diputuskan Sebelum Fase 3

- Apakah "project" di PM terikat ke `cabang` dan `wilayah` seperti data 4DX, atau berdiri bebas?
- Apakah user cabang boleh melihat project cabang lain?
- Apakah PM perlu role sendiri (mis. `project_manager`), atau cukup role yang ada + permission PM?

Ketiganya menentukan struktur tabel `pm_projects`, jadi jawab dulu sebelum menulis migrasi.
