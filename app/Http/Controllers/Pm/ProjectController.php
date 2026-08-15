<?php

namespace App\Http\Controllers\Pm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pm\ProjectRequest;
use App\Models\Pm\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $cari = trim((string) $request->query('cari', ''));
        $status = (string) $request->query('status', '');
        $prioritas = (string) $request->query('prioritas', '');

        $projects = Project::query()
            ->bisaDilihat($user)
            ->with(['pemilik:id,name', 'tasks:id,project_id,status,progress,bobot,deadline'])
            ->withCount('anggotas')
            ->when($cari !== '', fn ($q) => $q->where(
                fn ($sub) => $sub->where('kode', 'like', "%{$cari}%")
                    ->orWhere('nama', 'like', "%{$cari}%")
            ))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($prioritas !== '', fn ($q) => $q->where('prioritas', $prioritas))
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Project $p) => $this->ringkas($p, $user));

        return Inertia::render('Pm/Project/Index', [
            'projects' => $projects,
            'filter' => ['cari' => $cari, 'status' => $status, 'prioritas' => $prioritas],
            'opsi' => $this->opsi(),
            'bisaBuat' => $user->can('create', Project::class),
            'kandidatAnggota' => $user->can('create', Project::class) ? $this->kandidatAnggota() : [],
        ]);
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $project = DB::transaction(function () use ($request) {
            $project = Project::create([
                ...$request->validated(),
                'pemilik_id' => $request->user()->id,
            ]);

            // Pemilik selalu tercatat sebagai anggota bertaraf manager supaya
            // aturan keanggotaan (mis. validasi assignee) tidak perlu kasus khusus.
            $project->anggotas()->create([
                'user_id' => $request->user()->id,
                'peran' => 'manager',
            ]);

            return $project;
        });

        return redirect("/pm/projects/{$project->id}")->with('success', 'Project berhasil dibuat.');
    }

    public function show(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $user = $request->user();

        $project->load([
            'pemilik:id,name',
            'anggotas.user:id,name,email',
            'milestones',
            'tasks.assignees:id,name',
            'tasks.milestone:id,nama',
        ]);

        $statusTask = config('pm.status_task');

        // Kartu dikelompokkan per kolom Kanban, urut sesuai kolom `urutan`.
        $papan = collect($statusTask)->map(fn ($meta, $kunci) => [
            'kunci' => $kunci,
            'label' => $meta['label'],
            'warna' => $meta['warna'],
            'keterangan' => $meta['keterangan'],
            'tasks' => $project->tasks
                ->where('status', $kunci)
                ->sortBy([['urutan', 'asc'], ['id', 'asc']])
                ->map(fn ($t) => $this->ringkasTask($t))
                ->values(),
        ])->values();

        return Inertia::render('Pm/Project/Show', [
            'project' => [
                ...$this->ringkas($project, $user),
                'deskripsi' => $project->deskripsi,
                'milestones' => $project->milestones->map(fn ($m) => [
                    'id' => $m->id,
                    'nama' => $m->nama,
                    'deskripsi' => $m->deskripsi,
                    'target_tanggal' => $m->target_tanggal?->toDateString(),
                    'urutan' => $m->urutan,
                    'jumlahTask' => $project->tasks->where('milestone_id', $m->id)->count(),
                ]),
                'anggotas' => $project->anggotas->map(fn ($a) => [
                    'id' => $a->id,
                    'user_id' => $a->user_id,
                    'nama' => $a->user?->name,
                    'email' => $a->user?->email,
                    'peran' => $a->peran,
                    'kontribusi' => $this->kontribusi($project, $a->user_id),
                ])->sortBy('nama')->values(),
            ],
            'papan' => $papan,
            'opsi' => $this->opsi(),
            'izin' => [
                'kelola' => $user->can('update', $project),
                'kelolaTask' => $user->can('kelolaTask', $project),
                'ubahProgress' => $user->can('ubahProgress', $project),
            ],
            'kandidatAnggota' => $user->can('kelolaAnggota', $project) ? $this->kandidatAnggota() : [],
        ]);
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return back()->with('success', 'Project berhasil diperbarui.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect('/pm/projects')->with('success', 'Project berhasil dihapus.');
    }

    /* ---------------------------------------------------------------- */

    /** Bentuk ringkas project untuk daftar maupun kepala halaman detail. */
    private function ringkas(Project $project, User $user): array
    {
        $tasks = $project->tasks;
        $selesai = config('pm.status_selesai');

        return [
            'id' => $project->id,
            'kode' => $project->kode,
            'nama' => $project->nama,
            'status' => $project->status,
            'prioritas' => $project->prioritas,
            'tanggal_mulai' => $project->tanggal_mulai?->toDateString(),
            'tanggal_selesai' => $project->tanggal_selesai?->toDateString(),
            'pemilik' => $project->pemilik?->name,
            'progress' => $project->progress(),
            'health' => $project->health(),
            'peranSaya' => $project->peranUser($user),
            'jumlahAnggota' => $project->anggotas_count ?? $project->anggotas()->count(),
            'jumlahTask' => $tasks->count(),
            'jumlahSelesai' => $tasks->where('status', $selesai)->count(),
            'jumlahTerlambat' => $tasks->filter(fn ($t) => $t->terlambat())->count(),
        ];
    }

    private function ringkasTask($task): array
    {
        return [
            'id' => $task->id,
            'judul' => $task->judul,
            'deskripsi' => $task->deskripsi,
            'status' => $task->status,
            'prioritas' => $task->prioritas,
            'deadline' => $task->deadline?->toDateString(),
            'progress' => $task->progress,
            'bobot' => (float) $task->bobot,
            'urutan' => $task->urutan,
            'terlambat' => $task->terlambat(),
            'milestone_id' => $task->milestone_id,
            'milestone' => $task->milestone?->nama,
            'assignees' => $task->assignees->map(fn ($u) => [
                'id' => $u->id,
                'nama' => $u->name,
            ])->values(),
        ];
    }

    /**
     * Kontribusi seorang anggota = Σ(bobot × progress ÷ jumlah PIC task)
     * dibagi total bobot project. Pembagian dengan jumlah PIC mencegah satu
     * task yang dikerjakan bertiga dihitung penuh untuk ketiganya.
     */
    private function kontribusi(Project $project, int $userId): float
    {
        $totalBobot = (float) $project->tasks->sum('bobot');

        if ($totalBobot <= 0) {
            return 0.0;
        }

        $milik = $project->tasks->filter(
            fn ($t) => $t->assignees->contains('id', $userId)
        );

        $nilai = $milik->sum(function ($t) {
            $jumlahPic = max(1, $t->assignees->count());

            return (float) $t->bobot * $t->progress / 100 / $jumlahPic;
        });

        return round($nilai / $totalBobot * 100, 1);
    }

    /** Semua user aktif, untuk dropdown penambahan anggota. */
    private function kandidatAnggota(): array
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $u) => ['id' => $u->id, 'nama' => $u->name, 'email' => $u->email])
            ->all();
    }

    private function opsi(): array
    {
        return [
            'statusProject' => config('pm.status_project'),
            'statusTask' => config('pm.status_task'),
            'prioritas' => config('pm.prioritas'),
            'peran' => config('pm.peran'),
        ];
    }
}
