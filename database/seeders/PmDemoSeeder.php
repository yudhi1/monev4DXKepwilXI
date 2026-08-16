<?php

namespace Database\Seeders;

use App\Models\Pm\Milestone;
use App\Models\Pm\Project;
use App\Models\Pm\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data contoh modul Project Management untuk pengembangan & demo.
 *
 * Aman dijalankan berulang: project dikenali lewat kode, dan isinya
 * dibangun ulang setiap kali seeder ini dipanggil.
 */
class PmDemoSeeder extends Seeder
{
    public function run(): void
    {
        /*
         | Project dimiliki sebuah bidang dan dikerjakan pegawai perorangan,
         | jadi seeder ini bergantung pada UnitKerjaSeeder + PegawaiDemoSeeder.
         */
        $pemilik = User::pegawai()->where('is_active', true)->orderBy('id')->first();

        if (! $pemilik) {
            $this->command?->warn('Belum ada pegawai perorangan; jalankan PegawaiDemoSeeder dulu. PmDemoSeeder dilewati.');

            return;
        }

        // Sengaja mencampur bidang Kepwil dan kantor cabang untuk menguji tim lintas unit.
        $anggotaLain = User::pegawai()
            ->where('is_active', true)
            ->where('id', '!=', $pemilik->id)
            ->orderBy('id')
            ->take(4)
            ->get();

        $daftar = [
            [
                'kode' => 'PRJ-001',
                'nama' => 'Migrasi Aplikasi Monev ke Inertia + Vue',
                'deskripsi' => "Memindahkan seluruh halaman Monev 4DX dari Livewire ke Inertia + Vue 3,\ntermasuk pembersihan kode lama dan penyeragaman komponen UI.",
                'status' => 'berjalan',
                'prioritas' => 'tinggi',
                'tanggal_mulai' => now()->subMonths(3)->toDateString(),
                'tanggal_selesai' => now()->addMonth()->toDateString(),
                'milestones' => ['Persiapan & Rakitan', 'Migrasi Halaman', 'Pembersihan'],
                'tasks' => [
                    ['Rakit Inertia + Vue + shadcn-vue', 'done', 'tinggi', 100, 3, -60, 0],
                    ['Migrasi halaman Master (User, Wilayah, Cabang)', 'done', 'sedang', 100, 5, -40, 1],
                    ['Migrasi halaman WIG & Lag Measure', 'done', 'tinggi', 100, 8, -25, 1],
                    ['Migrasi dashboard Kepwil & Cabang', 'done', 'tinggi', 100, 8, -15, 1],
                    ['Hapus sisa komponen Livewire', 'done', 'sedang', 100, 3, -1, 2],
                    ['Rapikan dokumentasi CLAUDE.md', 'in_progress', 'rendah', 60, 1, 7, 2],
                    ['Uji regresi seluruh halaman', 'review', 'tinggi', 80, 5, 3, 2],
                ],
            ],
            [
                'kode' => 'PRJ-002',
                'nama' => 'Pengembangan Modul Project Management',
                'deskripsi' => "Membangun modul kedua di dalam aplikasi: project, task, Kanban,\nkontribusi anggota, dan laporan untuk pimpinan.",
                'status' => 'berjalan',
                'prioritas' => 'tinggi',
                'tanggal_mulai' => now()->subWeek()->toDateString(),
                'tanggal_selesai' => now()->addMonths(3)->toDateString(),
                'milestones' => ['Core', 'Kolaborasi', 'Management', 'Dashboard Eksekutif'],
                'tasks' => [
                    ['Rancang skema database PM', 'done', 'tinggi', 100, 5, -3, 0],
                    ['Kerangka modul & halaman pemilih aplikasi', 'done', 'tinggi', 100, 3, -2, 0],
                    ['CRUD Project & keanggotaan', 'in_progress', 'tinggi', 70, 8, 5, 0],
                    ['Papan Kanban dengan drag-and-drop', 'in_progress', 'tinggi', 50, 8, 7, 0],
                    ['Komentar & lampiran task', 'todo', 'sedang', 0, 5, 30, 1],
                    ['Activity log otomatis', 'todo', 'sedang', 0, 5, 35, 1],
                    ['Gantt / timeline milestone', 'backlog', 'rendah', 0, 8, 60, 2],
                    ['Laporan kontribusi tim', 'backlog', 'sedang', 0, 5, 75, 3],
                ],
            ],
            [
                'kode' => 'PRJ-003',
                'nama' => 'Penyusunan Laporan Kinerja Semester I',
                'deskripsi' => 'Kompilasi capaian WIG seluruh kantor cabang untuk laporan pimpinan.',
                'status' => 'tertahan',
                'prioritas' => 'sedang',
                'tanggal_mulai' => now()->subMonths(2)->toDateString(),
                'tanggal_selesai' => now()->subWeeks(2)->toDateString(),
                'milestones' => ['Pengumpulan Data', 'Penyusunan'],
                'tasks' => [
                    ['Tarik data realisasi seluruh cabang', 'done', 'tinggi', 100, 5, -30, 0],
                    ['Validasi data dengan kantor cabang', 'in_progress', 'tinggi', 40, 8, -10, 0],
                    ['Susun narasi laporan', 'todo', 'sedang', 0, 5, -5, 1],
                ],
            ],
        ];

        foreach ($daftar as $data) {
            $this->buat($data, $pemilik, $anggotaLain);
        }

        $this->command?->info('Data demo Project Management siap: '.count($daftar).' project.');
    }

    private function buat(array $data, User $pemilik, $anggotaLain): void
    {
        $project = Project::updateOrCreate(
            ['kode' => $data['kode']],
            [
                'nama' => $data['nama'],
                'deskripsi' => $data['deskripsi'],
                'status' => $data['status'],
                'prioritas' => $data['prioritas'],
                'tanggal_mulai' => $data['tanggal_mulai'],
                'tanggal_selesai' => $data['tanggal_selesai'],
                'pemilik_id' => $pemilik->id,
                'unit_kerja_id' => $pemilik->unit_kerja_id,
            ]
        );

        // Bangun ulang isinya supaya seeder bisa dijalankan berkali-kali.
        $project->tasks()->delete();
        $project->milestones()->delete();
        $project->anggotas()->delete();

        $project->anggotas()->create(['user_id' => $pemilik->id, 'peran' => 'manager']);

        foreach ($anggotaLain as $i => $u) {
            $project->anggotas()->create([
                'user_id' => $u->id,
                'peran' => $i === 0 ? 'manager' : ($i === 3 ? 'viewer' : 'member'),
            ]);
        }

        $milestones = [];
        foreach ($data['milestones'] as $i => $nama) {
            $milestones[] = Milestone::create([
                'project_id' => $project->id,
                'nama' => $nama,
                'target_tanggal' => now()->addWeeks(($i + 1) * 3)->toDateString(),
                'urutan' => $i,
            ]);
        }

        $bisaDitugasi = $project->anggotas()
            ->whereIn('peran', ['manager', 'member'])
            ->pluck('user_id')
            ->all();

        $urutan = [];

        foreach ($data['tasks'] as $i => [$judul, $status, $prioritas, $progress, $bobot, $selisihHari, $indeksMilestone]) {
            $urutan[$status] = ($urutan[$status] ?? -1) + 1;

            $task = Task::create([
                'project_id' => $project->id,
                'milestone_id' => $milestones[$indeksMilestone]->id ?? null,
                'judul' => $judul,
                'status' => $status,
                'prioritas' => $prioritas,
                'progress' => $progress,
                'bobot' => $bobot,
                'deadline' => now()->addDays($selisihHari)->toDateString(),
                'urutan' => $urutan[$status],
                'dibuat_oleh' => $pemilik->id,
            ]);

            // Sebar PIC bergantian supaya angka kontribusi bervariasi.
            if ($bisaDitugasi !== []) {
                $jumlah = $i % 3 === 0 ? 2 : 1;
                $pilihan = [];
                for ($n = 0; $n < $jumlah; $n++) {
                    $pilihan[] = $bisaDitugasi[($i + $n) % count($bisaDitugasi)];
                }
                $task->assignees()->sync(array_unique($pilihan));
            }
        }
    }
}
