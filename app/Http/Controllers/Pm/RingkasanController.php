<?php

namespace App\Http\Controllers\Pm;

use App\Http\Controllers\Controller;
use App\Models\Pm\Project;
use App\Models\Pm\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Rincian di balik kartu statistik dashboard.
 *
 * Empat kartu itu tadinya buntu — angkanya tak bisa ditelusuri. Halaman ini
 * membuka isinya, dan sengaja memakai sumber data yang sama persis dengan
 * DashboardController supaya jumlah barisnya tidak pernah berbeda dari angka
 * yang diklik.
 */
class RingkasanController extends Controller
{
    /** Tampilan yang tersedia, sesuai empat kartu di dashboard. */
    private const TAMPIL = [
        'project' => ['judul' => 'Daftar Project', 'keterangan' => 'Seluruh project beserta task di dalamnya.'],
        'task' => ['judul' => 'Daftar Task', 'keterangan' => 'Seluruh task dan project asalnya.'],
        'selesai' => ['judul' => 'Task Selesai', 'keterangan' => 'Task yang sudah rampung, beserta project asalnya.'],
        'terlambat' => ['judul' => 'Task Terlambat', 'keterangan' => 'Task yang lewat deadline dan belum selesai.'],
        'jatuh-tempo' => ['judul' => 'Jatuh Tempo 7 Hari', 'keterangan' => 'Task yang tenggatnya dalam sepekan ke depan dan belum selesai.'],
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();
        // Dicast ke string: query yang absen bernilai null, dan null bukan kunci array.
        $diminta = (string) $request->query('tampil', '');
        $tampil = array_key_exists($diminta, self::TAMPIL) ? $diminta : 'project';

        $selesai = config('pm.status_selesai');

        $projects = Project::query()
            ->bisaDilihat($user)
            ->with([
                'unitKerja.cabang:id,nama',
                'tasks.assignees:id,name',
                'tasks.milestone:id,nama',
            ])
            ->orderBy('kode')
            ->get();

        $semuaTask = $projects->flatMap->tasks;

        // Penyaringan memakai definisi yang sama dengan kartu dashboard.
        $taskTerpilih = match ($tampil) {
            'selesai' => $semuaTask->where('status', $selesai),
            'terlambat' => $semuaTask->filter(fn (Task $t) => $t->terlambat()),
            'jatuh-tempo' => $semuaTask->filter(
                fn (Task $t) => ! $t->selesai()
                    && $t->deadline
                    && $t->deadline->betweenIncluded(now()->startOfDay(), now()->addDays(7)->endOfDay())
            ),
            default => $semuaTask,
        };

        return Inertia::render('Pm/Ringkasan', [
            'tampil' => $tampil,
            'meta' => self::TAMPIL[$tampil],

            // Tampilan 'project' dikelompokkan; tiga lainnya berupa daftar rata.
            'projects' => $tampil === 'project'
                ? $projects->map(fn (Project $p) => $this->ringkasProject($p, $selesai))->values()
                : [],

            'tasks' => $tampil === 'project'
                ? []
                : $taskTerpilih
                    ->sortBy(fn (Task $t) => $t->deadline?->timestamp ?? PHP_INT_MAX)
                    ->map(fn (Task $t) => $this->ringkasTask($t))
                    ->values(),

            'jumlah' => [
                'project' => $projects->count(),
                'task' => $semuaTask->count(),
                'selesai' => $semuaTask->where('status', $selesai)->count(),
                'terlambat' => $semuaTask->filter(fn (Task $t) => $t->terlambat())->count(),
                'jatuh-tempo' => $semuaTask->filter(
                    fn (Task $t) => ! $t->selesai()
                        && $t->deadline
                        && $t->deadline->betweenIncluded(now()->startOfDay(), now()->addDays(7)->endOfDay())
                )->count(),
            ],

            'opsi' => [
                'statusProject' => config('pm.status_project'),
                'statusTask' => config('pm.status_task'),
                'prioritas' => config('pm.prioritas'),
            ],
        ]);
    }

    private function ringkasProject(Project $project, string $selesai): array
    {
        return [
            'id' => $project->id,
            'kode' => $project->kode,
            'nama' => $project->nama,
            'status' => $project->status,
            'prioritas' => $project->prioritas,
            'progress' => $project->progress(),
            'health' => $project->health(),
            'unitKerja' => $project->unitKerja?->nama,
            'jumlahTask' => $project->tasks->count(),
            'jumlahSelesai' => $project->tasks->where('status', $selesai)->count(),
            'jumlahTerlambat' => $project->tasks->filter(fn (Task $t) => $t->terlambat())->count(),
            'tasks' => $project->tasks
                ->sortBy(fn (Task $t) => $t->deadline?->timestamp ?? PHP_INT_MAX)
                ->map(fn (Task $t) => $this->ringkasTask($t, false))
                ->values(),
        ];
    }

    /** @param  bool  $sertakanProject  daftar rata perlu tahu asal project-nya */
    private function ringkasTask(Task $task, bool $sertakanProject = true): array
    {
        $baris = [
            'id' => $task->id,
            'judul' => $task->judul,
            'status' => $task->status,
            'prioritas' => $task->prioritas,
            'deadline' => $task->deadline?->toDateString(),
            'terlambat' => $task->terlambat(),
            'progress' => $task->progress,
            'satuan' => $task->satuan,
            'target' => $task->target !== null ? (float) $task->target : null,
            'realisasi' => $task->realisasi !== null ? (float) $task->realisasi : null,
            'pakaiTarget' => $task->pakaiTarget(),
            'milestone' => $task->milestone?->nama,
            'assignees' => $task->assignees->pluck('name')->all(),
        ];

        if ($sertakanProject) {
            $baris['project'] = [
                'id' => $task->project_id,
                'kode' => $task->project?->kode,
                'nama' => $task->project?->nama,
            ];
        }

        return $baris;
    }
}
