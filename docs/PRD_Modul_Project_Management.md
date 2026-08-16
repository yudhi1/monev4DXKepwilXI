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
├── Bidang KML, JPK, PIKUE, SDMUK                 (tingkat 'wilayah')
└── 11 Kantor Cabang
    └── masing-masing: Bidang PMU, Yanfasskes,    (tingkat 'cabang')
        Kepesertaan, Yanser, PKP, SDMUK
```

Disimpan di tabel `unit_kerjas` — 4 + (11 × 6) = **70 unit**. Bidang di kantor
cabang berdiri sendiri per cabang: "Bidang PMU KC Denpasar" adalah baris berbeda
dari "Bidang PMU KC Kupang", karena keduanya unit kerja yang berbeda.

Keanggotaan project **boleh lintas bidang dan lintas level** — pegawai Bidang KML
di Kedeputian Wilayah dapat satu tim dengan pegawai Bidang PMU KC Denpasar.

**"Internal Kepwil"** adalah kantor cabang semu yang dipakai modul 4DX untuk input
internal, bukan kantor cabang sungguhan, jadi tidak diberi struktur bidang.

### Akun pengguna

Modul PM bekerja atas **akun perorangan** (`users.unit_kerja_id` terisi), bukan
akun institusi. Akun institusi lama (`admin`, `kepwil`, `kc.*`) tetap ada dan
dipakai modul 4DX, tetapi tidak muncul sebagai kandidat anggota project.

Pegawai ditambahkan admin lewat **Master → User**, satu per satu atau massal
lewat **Impor Pegawai** (Excel: `nama`, `jabatan`, `bidang`, `cabang`; kolom
`cabang` dikosongkan untuk pegawai Kedeputian Wilayah). Role ditentukan otomatis
dari tingkat bidangnya. Password awal akun hasil impor: `monev2026`.

**Teknologi:** mengikuti stack yang sudah ada — Laravel + Inertia + Vue 3 + Tailwind 4 +
shadcn-vue + MySQL. Dokumen konsep revisi sudah menghapus Fortify/Sanctum, Reverb,
Queue, dan Docker/Coolify, jadi modul ini **tidak menambah infrastruktur baru**.
Notifikasi realtime, kalau nanti diperlukan, dikerjakan dengan polling biasa dulu.

## 3. Role & Permission

Dua lapis, sengaja dipisah:

**Lapis 1 — akses aplikasi (global, spatie/laravel-permission).**

| Permission | Arti |
|---|---|
| `akses-pm` | Boleh masuk modul PM sama sekali |
| `pm.project.buat` | Boleh membuat project atas nama unit kerjanya |
| `pm.lihat-semua` | Melihat semua project tanpa harus jadi anggota (pimpinan & admin) |
| `pm.kelola` | Kelola master/pengaturan modul PM |

> **`pm.lihat-semua` sengaja tidak dilekatkan pada role `kedeputian_wilayah`.**
> Role itu kini juga dipakai staf bidang di Kedeputian Wilayah, dan staf tidak
> boleh melihat pekerjaan seluruh organisasi. Hak pimpinan diberikan **per user**
> lewat sakelar *Pimpinan* di form Kelola User (permission langsung, bukan role).

**Lapis 2 — peran di dalam sebuah project (kolom `peran` di `pm_project_members`).**

| Peran | Hak di project itu |
|---|---|
| `manager` | Atur anggota, milestone, task, bobot, dan tutup project |
| `member` | Kerjakan task yang di-assign, update progress, komentar, unggah file |
| `viewer` | Baca saja |

Alasan pemisahan: seorang pegawai bisa jadi *manager* di project A dan *member* di
project B. Peran semacam itu tidak bisa diwakili role global spatie, dan memaksakannya
ke sana akan mengunci satu orang pada satu peran untuk semua project.

Pemetaan ke role yang sudah ada:

| Role global sekarang | Dapat permission PM |
|---|---|
| `admin` | `akses-pm`, `pm.project.buat`, `pm.lihat-semua`, `pm.kelola` |
| `kedeputian_wilayah` | `akses-pm`, `pm.project.buat`, `pm.lihat-semua` |
| `kantor_cabang` | `akses-pm` (hanya project yang dia ikuti) |

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
