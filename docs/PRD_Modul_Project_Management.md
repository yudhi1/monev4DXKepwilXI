# PRD — Modul Project Management

Modul kedua di dalam aplikasi Monev 4DX. Satu aplikasi, satu database, **satu login**;
modul dipilih setelah login. Lihat
[RENCANA_RESTRUKTURISASI_MULTI_MODUL.md](RENCANA_RESTRUKTURISASI_MULTI_MODUL.md)
untuk keputusan arsitekturnya.

Sumber: dokumen *Konsep Aplikasi Project Management* (revisi), ditambah tiga keputusan
lingkup yang tidak dibahas di dokumen tersebut (bagian 2 di bawah).

---

## 1. Tujuan

Menjawab lima pertanyaan: **siapa mengerjakan apa, sejauh mana progresnya, kapan harus
selesai, apa output-nya, dan seberapa besar kontribusi tiap anggota.**

Alur inti:

```
PROJECT → MILESTONE → TASK → ASSIGNEE → PROGRESS → OUTPUT → EVALUATION
```

## 2. Keputusan Lingkup

| Pertanyaan | Keputusan | Konsekuensi |
|---|---|---|
| Project terikat unit kerja? | **Ya, dimiliki satu bidang.** *(direvisi 16 Agu 2026)* | `pm_projects.unit_kerja_id`. Sebelumnya project berdiri bebas; diubah setelah struktur organisasi ditetapkan. |
| Siapa boleh melihat project? | **Anggota + rekan satu unit kerja + pimpinan/admin.** | Selain pemegang `pm.lihat-semua`, query difilter keanggotaan **atau** kesamaan `unit_kerja_id`. |
| Siapa boleh membuat project? | **Semua pegawai, atas nama unit kerjanya.** | Butuh `pm.project.buat` **dan** punya `unit_kerja_id`. Pembuat otomatis jadi Project Manager. |
| Member ditentukan kapan? | **Saat project dibuat.** | Form pembuatan project memuat pemilihan anggota beserta perannya; masih bisa diubah di tab Members. |
| Prefix URL 4DX dipindah ke `/4dx`? | **Ditunda.** | 4DX tetap di URL sekarang; PM langsung di `/pm`. Nama rute PM diberi prefix `pm.` agar tidak bentrok. |

## 2a. Struktur Organisasi

```
Kedeputian Wilayah XI
├── Bidang JPK, PIKEU, KML, SDMUK                    (tingkat 'wilayah')
└── 11 Kantor Cabang
    └── masing-masing: Bidang Kepesertaan,           (tingkat 'cabang')
        Yanfaskes, Yanser, PMU, PKP, SDMU
```

Perhatikan **SDMUK** di Kedeputian Wilayah tetapi **SDMU** di kantor cabang —
memang berbeda, bukan salah ketik.

Disimpan di tabel `unit_kerjas` — 4 + (11 × 6) = **70 unit**. Bidang di kantor
cabang berdiri sendiri per cabang: "Bidang PMU KC Denpasar" adalah baris berbeda
dari "Bidang PMU KC Kupang", karena keduanya unit kerja yang berbeda.

Keanggotaan project **boleh lintas bidang dan lintas level** — pegawai Bidang KML
di Kedeputian Wilayah dapat satu tim dengan pegawai Bidang PMU KC Denpasar.

**"Internal Kepwil"** adalah kantor cabang semu yang dipakai modul 4DX untuk input
internal, bukan kantor cabang sungguhan, jadi tidak diberi struktur bidang.

### Dua jenis akun

Kedua modul punya pengguna yang berbeda, dibedakan kolom `users.tipe`:

| | `unit_kerja` — Monev 4DX | `pegawai` — Project Management |
|---|---|---|
| Mewakili | satu unit kerja / kantor | satu orang |
| Contoh | `admin`, `kepwil`, `kc.denpasar` | Rina Kusuma, Kadek Surya |
| Field | nama unit kerja, password, role, wilayah, cabang | nama pegawai, password, bidang, jabatan, role PM |
| Role | spatie: `admin`, `kedeputian_wilayah`, `kantor_cabang` | kolom `pm_role` |
| Dikelola di | Master → **Akun Monev 4DX** (`/users`) | Master → **Pegawai** (`/pegawai`) |

**Satu tabel, bukan dua.** Laravel hanya mengautentikasi satu tabel per guard, dan
`pm_project_members.user_id`, `pm_task_assignees.user_id`, serta
`activity_log.causer_id` semuanya menunjuk ke `users` — memecahnya berarti relasi
polimorfik di mana-mana. Yang dipisah adalah layar kelola dan form-nya.

**Aksesnya benar-benar terpisah:** seluruh rute 4DX dijaga `modul:4dx` dan seluruh
rute PM dijaga `modul:pm`. Pegawai mendapat 403 di halaman 4DX, akun unit kerja
mendapat 403 di halaman PM. Hanya `admin` memegang keduanya.

Pegawai ditambahkan admin satu per satu, atau massal lewat **Impor Excel**
(`nama`, `jabatan`, `bidang`, `cabang`, `role`; kolom `cabang` dikosongkan untuk
pegawai Kedeputian Wilayah, kolom `role` boleh kosong dan berarti Member).
Password awal akun baru: `monev2026`.

**Teknologi:** mengikuti stack yang sudah ada — Laravel + Inertia + Vue 3 + Tailwind 4 +
shadcn-vue + MySQL. Dokumen konsep revisi sudah menghapus Fortify/Sanctum, Reverb,
Queue, dan Docker/Coolify, jadi modul ini **tidak menambah infrastruktur baru**.
Notifikasi realtime, kalau nanti diperlukan, dikerjakan dengan polling biasa dulu.

## 3. Role & Permission

Dua lapis, sengaja dipisah:

**Lapis 1 — role di akun (kolom `users.pm_role`), berlaku lintas project.**

| Role | Arti | Permission yang disinkronkan |
|---|---|---|
| `member` | Ikut project yang mendaftarkannya; tidak bisa membuat project | `akses-pm` |
| `project_manager` | Boleh membuat project atas nama unit kerjanya | + `pm.project.buat` |
| `pimpinan` | Melihat seluruh project tanpa harus jadi anggota | + `pm.lihat-semua` |

Pemetaan role → permission ada di `config/pm.php` dan diterapkan
`User::selaraskanIzinPm()` setiap kali pegawai disimpan.

> **Permission PM sengaja tidak dilekatkan pada role spatie.** Role spatie
> (`admin`, `kedeputian_wilayah`, `kantor_cabang`) milik modul 4DX dan hanya
> dipakai akun unit kerja. Pegawai tidak diberi role spatie sama sekali; aksesnya
> murni dari `pm_role`.

**Lapis 2 — peran di dalam sebuah project (kolom `peran` di `pm_project_members`).**

| Peran | Hak di project itu |
|---|---|
| `manager` | Atur anggota, milestone, task, bobot, dan tutup project |
| `member` | Kerjakan task yang di-assign, update progress, komentar, unggah file |
| `viewer` | Baca saja |

Alasan pemisahan: seorang pegawai bisa jadi *manager* di project A dan *member* di
project B. Peran semacam itu tidak bisa diwakili satu label tetap di akun, dan
memaksakannya ke sana akan mengunci satu orang pada satu peran untuk semua project.

Contoh bagaimana keduanya bekerja bersama: pegawai ber-`pm_role` **Member** tidak
bisa memulai project sendiri, tetapi tetap boleh ditunjuk sebagai *manager* di
sebuah project yang dibuat orang lain.

## 4. Struktur Database

Semua tabel PM berprefiks `pm_` agar batasnya jelas terhadap tabel 4DX. Tabel `users`
dipakai bersama — tidak ada duplikasi akun.

| Tabel | Isi |
|---|---|
| `unit_kerjas` | bidang di Kedeputian Wilayah & kantor cabang (dipakai bersama, bukan tabel PM) |
| `pm_projects` | kode, nama, deskripsi, status, prioritas, tanggal mulai & selesai, pemilik, **unit kerja** |
| `pm_project_members` | relasi project ⇄ user + `peran` (manager/member/viewer) |
| `pm_milestones` | tahapan project, urutan, target tanggal |
| `pm_tasks` | pekerjaan: judul, deskripsi, status, prioritas, deadline, progress, bobot, milestone |
| `pm_task_assignees` | relasi task ⇄ user (satu task bisa lebih dari satu PIC) |
| `pm_task_comments` | diskusi pada task *(fase Kolaborasi)* |
| `pm_attachments` | lampiran task/project *(fase Kolaborasi)* |
| `pm_activities` | audit trail project & task *(fase Kolaborasi)* |

### Status & prioritas

Status task mengikuti papan Kanban di konsep:
`backlog` → `todo` → `in_progress` → `review` → `done`.

Status project: `perencanaan`, `berjalan`, `tertahan`, `selesai`, `batal`.
Prioritas (project & task): `rendah`, `sedang`, `tinggi`.

Nilai-nilai ini disimpan sebagai string pendek dengan daftar terpusat di
`config/pm.php`, bukan enum MySQL — supaya menambah status tidak perlu migrasi.

## 4a. Target & Realisasi per Task

Task boleh punya angka: `satuan`, `target`, dan `realisasi` (ketiganya opsional).
Untuk task semacam itu **progress tidak diisi tangan**, melainkan dihitung
`realisasi ÷ target`, dibatasi 0–100%. Task tanpa target tetap memakai progress
manual — tidak semua pekerjaan terukur angka.

Hasilnya tetap disimpan di kolom `progress`, bukan dihitung saat dibaca, supaya
progress project dan kontribusi anggota tidak perlu tahu soal target.

**Sengaja sekali per task, bukan riwayat per periode.** Pelacakan berkala sudah
menjadi tugas modul 4DX (`wig_realisasis` per bulan, `lead_measure_realisasis`
per minggu). Menduplikasinya di PM berarti dua sumber angka untuk hal yang sama,
dan tim harus mengisi di dua tempat.

**Masuk kolom Done tidak memaksa 100% untuk task bertarget.** Penagihan yang
periodenya ditutup di 70% tampil apa adanya — "selesai dikerjakan, target tidak
tercapai" adalah informasi yang berguna, bukan kesalahan yang perlu ditutupi.
Task tanpa target tetap menjadi 100% saat masuk Done.

## 5. Perhitungan Kontribusi & Progress

Dari dokumen konsep: **Kontribusi = Bobot Task × Progress Task**.

- `pm_tasks.bobot` — angka relatif per task di dalam satu project (bukan persen yang
  wajib berjumlah 100; persentase dihitung dari total bobot project).
- Kontribusi seorang anggota di sebuah project:

  ```
  kontribusi = Σ (bobot_task × progress_task / 100 / jumlah_assignee_task)
               ───────────────────────────────────────────────────────────
                              Σ bobot semua task project
  ```

  Pembagian dengan `jumlah_assignee_task` mencegah satu task yang dikerjakan tiga orang
  dihitung penuh untuk ketiganya.

- Progress project = Σ(bobot × progress) / Σ(bobot). Kalau semua bobot kosong,
  jatuh ke rata-rata progress task biasa.

**Project health** (dokumen bagian 13), dihitung, bukan diinput:

| Status | Syarat |
|---|---|
| `on_track` | tidak ada task overdue dan progress ≥ ekspektasi jadwal |
| `at_risk` | ada task overdue, atau progress tertinggal < 15% dari ekspektasi |
| `critical` | progress tertinggal ≥ 15% dari ekspektasi, atau due date project terlewat |

Ekspektasi jadwal = proporsi waktu yang sudah berlalu antara `tanggal_mulai` dan
`tanggal_selesai`.

## 6. Halaman

| URL | Isi |
|---|---|
| `/pm` | Dashboard: kartu statistik (project, task, selesai, overdue), project aktif, task saya, aktivitas |
| `/pm/projects` | Daftar project (kartu/tabel) + filter status & prioritas |
| `/pm/projects/{project}` | Detail, tab: Overview, Tasks (Kanban), Members, Timeline, Files, Activity |
| `/pm/tugas-saya` | Task pribadi lintas project, dikelompokkan status/prioritas/deadline |
| `/pm/members` | Anggota dan keterlibatannya di project |
| `/pm/laporan` | Laporan project, performa tim, kontribusi, project health |

## 7. Tahapan Pengembangan

| Tahap | Isi | Status |
|---|---|---|
| **Core** | Project, member, milestone, task, assignee, status, prioritas, deadline, progress, Kanban | sedang dikerjakan |
| **Kolaborasi** | Komentar, lampiran, checklist, activity log | belum |
| **Manajemen** | Timeline/Gantt, kontribusi, project health | belum |
| **Dashboard Eksekutif** | Performa project & tim, laporan pimpinan | belum |

Urutan ini mengikuti roadmap dokumen konsep, dengan milestone dinaikkan ke tahap Core
karena `pm_tasks` sudah perlu mereferensinya sejak awal — menambahkannya belakangan
berarti migrasi ulang tabel task.

## 8. Di Luar Cakupan

- Notifikasi realtime (Reverb) — sudah dihapus dari dokumen konsep revisi.
- Drag-and-drop Gantt chart; timeline tahap pertama cukup daftar milestone berurut.
- Integrasi dengan data 4DX. Kedua modul berbagi `users`, `wilayahs`, `cabangs` saja.
