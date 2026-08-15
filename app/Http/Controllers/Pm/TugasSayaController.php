<?php

namespace App\Http\Controllers\Pm;

use App\Http\Controllers\Controller;
use App\Models\Pm\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TugasSayaController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $status = (string) $request->query('status', '');
        $prioritas = (string) $request->query('prioritas', '');

        $tasks = Task::query()
            ->milik($user)
            ->with(['project:id,kode,nama', 'milestone:id,nama'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($prioritas !== '', fn ($q) => $q->where('prioritas', $prioritas))
            ->orderByRaw('deadline is null, deadline asc')
            ->get()
            ->map(fn (Task $t) => [
                'id' => $t->id,
                'judul' => $t->judul,
                'status' => $t->status,
                'prioritas' => $t->prioritas,
                'deadline' => $t->deadline?->toDateString(),
                'progress' => $t->progress,
                'terlambat' => $t->terlambat(),
                'milestone' => $t->milestone?->nama,
                'project' => ['id' => $t->project_id, 'kode' => $t->project?->kode, 'nama' => $t->project?->nama],
            ]);

        return Inertia::render('Pm/TugasSaya', [
            'tasks' => $tasks,
            'filter' => ['status' => $status, 'prioritas' => $prioritas],
            'opsi' => [
                'statusTask' => config('pm.status_task'),
                'prioritas' => config('pm.prioritas'),
            ],
        ]);
    }
}
