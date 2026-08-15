<?php

namespace App\Http\Controllers\Pm;

use App\Http\Controllers\Controller;
use App\Models\Pm\Project;
use App\Models\Pm\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $selesai = config('pm.status_selesai');

        $projects = Project::query()
            ->bisaDilihat($user)
            ->with(['pemilik:id,name', 'tasks:id,project_id,status,progress,bobot,deadline'])
            ->withCount('anggotas')
            ->get();

        $idProject = $projects->pluck('id');

        // Task hanya dari project yang boleh dilihat user.
        $tasks = Task::query()
            ->whereIn('project_id', $idProject)
            ->with(['project:id,kode,nama', 'assignees:id,name'])
            ->get();

        $tugasSaya = $tasks
            ->filter(fn (Task $t) => $t->assignees->contains('id', $user->id))
            ->filter(fn (Task $t) => ! $t->selesai())
            // Tanpa deadline diletakkan paling belakang.
            ->sortBy(fn (Task $t) => $t->deadline?->timestamp ?? PHP_INT_MAX)
            ->take(8)
            ->map(fn (Task $t) => [
                'id' => $t->id,
                'judul' => $t->judul,
                'status' => $t->status,
                'prioritas' => $t->prioritas,
                'deadline' => $t->deadline?->toDateString(),
                'progress' => $t->progress,
                'terlambat' => $t->terlambat(),
                'project' => ['id' => $t->project_id, 'nama' => $t->project?->nama],
            ])
            ->values();

        return Inertia::render('Pm/Dashboard', [
            'ringkasan' => [
                'project' => $projects->count(),
                'projectBerjalan' => $projects->where('status', 'berjalan')->count(),
                'task' => $tasks->count(),
                'taskSelesai' => $tasks->where('status', $selesai)->count(),
                'taskTerlambat' => $tasks->filter(fn (Task $t) => $t->terlambat())->count(),
                'tugasSaya' => $tasks->filter(
                    fn (Task $t) => ! $t->selesai() && $t->assignees->contains('id', $user->id)
                )->count(),
            ],
            'projectAktif' => $projects
                ->whereIn('status', ['perencanaan', 'berjalan', 'tertahan'])
                ->sortByDesc('updated_at')
                ->take(6)
                ->map(fn (Project $p) => [
                    'id' => $p->id,
                    'kode' => $p->kode,
                    'nama' => $p->nama,
                    'status' => $p->status,
                    'prioritas' => $p->prioritas,
                    'progress' => $p->progress(),
                    'health' => $p->health(),
                    'jumlahAnggota' => $p->anggotas_count,
                    'jumlahTask' => $p->tasks->count(),
                    'jumlahSelesai' => $p->tasks->where('status', $selesai)->count(),
                    'tanggal_selesai' => $p->tanggal_selesai?->toDateString(),
                ])
                ->values(),
            'tugasSaya' => $tugasSaya,
            'opsi' => [
                'statusProject' => config('pm.status_project'),
                'statusTask' => config('pm.status_task'),
                'prioritas' => config('pm.prioritas'),
            ],
        ]);
    }
}
