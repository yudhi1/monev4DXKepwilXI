<?php

namespace App\Http\Controllers\Pm;

use App\Exports\Pm\TugasExport;
use App\Http\Controllers\Controller;
use App\Models\Pm\Project;
use App\Models\Pm\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TugasSayaController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filter = $this->filter($request, $user);

        $tasks = $this->query($user, $filter)
            ->get()
            ->map(fn (Task $t) => [
                'id' => $t->id,
                'judul' => $t->judul,
                'status' => $t->status,
                'prioritas' => $t->prioritas,
                'deadline' => $t->deadline?->toDateString(),
                'progress' => $t->progress,
                'terlambat' => $t->terlambat(),
                'satuan' => $t->satuan,
                'target' => $t->target !== null ? (float) $t->target : null,
                'realisasi' => $t->realisasi !== null ? (float) $t->realisasi : null,
                'pakaiTarget' => $t->pakaiTarget(),
                'milestone' => $t->milestone?->nama,
                'pic' => $t->assignees->pluck('name')->all(),
                'project' => ['id' => $t->project_id, 'kode' => $t->project?->kode, 'nama' => $t->project?->nama],
            ]);

        return Inertia::render('Pm/TugasSaya', [
            'tasks' => $tasks,
            'filter' => $filter,
            'bisaLihatTim' => $this->bisaLihatTim($user),
            'daftarPic' => $filter['lingkup'] === 'tim' ? $this->daftarPic($user) : [],
            'daftarProject' => $filter['lingkup'] === 'tim' ? $this->daftarProject($user) : [],
            'opsi' => [
                'statusTask' => config('pm.status_task'),
                'prioritas' => config('pm.prioritas'),
            ],
        ]);
    }

    /** Unduh daftar yang sedang tersaring sebagai Excel. */
    public function ekspor(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        $filter = $this->filter($request, $user);

        $tasks = $this->query($user, $filter)->get();

        $berkas = 'tugas-'.($filter['lingkup'] === 'tim' ? 'tim' : 'saya').'-'.now()->format('Ymd-Hi').'.xlsx';

        return Excel::download(new TugasExport($tasks, $filter), $berkas);
    }

    /* ---------------------------------------------------------------- */

    /**
     * Filter yang berlaku, sudah dibersihkan.
     *
     * `lingkup` dipaksa kembali ke 'saya' bila user tidak berhak melihat
     * tugas tim — tanpa ini, mengetik ?lingkup=tim di alamat sudah cukup
     * untuk mengintip pekerjaan orang lain.
     */
    private function filter(Request $request, User $user): array
    {
        $lingkup = $request->query('lingkup') === 'tim' && $this->bisaLihatTim($user) ? 'tim' : 'saya';

        return [
            'lingkup' => $lingkup,
            'status' => (string) $request->query('status', ''),
            'prioritas' => (string) $request->query('prioritas', ''),
            'pic' => $lingkup === 'tim' ? $request->query('pic') : null,
            'project' => $lingkup === 'tim' ? $request->query('project') : null,
        ];
    }

    /** @return Builder<Task> */
    private function query(User $user, array $filter): Builder
    {
        $q = Task::query()
            ->with(['project:id,kode,nama', 'milestone:id,nama', 'assignees:id,name']);

        if ($filter['lingkup'] === 'tim') {
            $q->whereIn('project_id', $this->idProjectTim($user));

            if ($filter['pic']) {
                $q->whereHas('assignees', fn (Builder $a) => $a->where('users.id', $filter['pic']));
            }

            if ($filter['project']) {
                $q->where('project_id', $filter['project']);
            }
        } else {
            $q->milik($user);
        }

        return $q
            ->when($filter['status'] !== '', fn ($x) => $x->where('status', $filter['status']))
            ->when($filter['prioritas'] !== '', fn ($x) => $x->where('prioritas', $filter['prioritas']))
            ->orderByRaw('deadline is null, deadline asc');
    }

    /**
     * Project yang boleh dipantau menyeluruh oleh user ini.
     *
     * Pemegang `pm.lihat-semua` mendapat semuanya; selain itu hanya project
     * yang dia kelola — dimiliki atau berperan manager di dalamnya.
     */
    private function idProjectTim(User $user): array
    {
        if ($user->can('pm.lihat-semua')) {
            return Project::bisaDilihat($user)->pluck('id')->all();
        }

        return Project::where('pemilik_id', $user->id)
            ->orWhereHas('anggotas', fn (Builder $a) => $a->where('user_id', $user->id)->where('peran', 'manager'))
            ->pluck('id')
            ->all();
    }

    /** Menu "Tugas Tim" hanya masuk akal bagi yang memang mengelola sesuatu. */
    private function bisaLihatTim(User $user): bool
    {
        return $user->can('pm.lihat-semua') || $this->idProjectTim($user) !== [];
    }

    /** @return array<int, array<string, mixed>> */
    private function daftarPic(User $user): array
    {
        return User::query()
            ->whereHas('tasksPm', fn (Builder $t) => $t->whereIn('project_id', $this->idProjectTim($user)))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $u) => ['id' => $u->id, 'nama' => $u->name])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function daftarProject(User $user): array
    {
        return Project::whereIn('id', $this->idProjectTim($user))
            ->orderBy('kode')
            ->get(['id', 'kode', 'nama'])
            ->map(fn (Project $p) => ['id' => $p->id, 'kode' => $p->kode, 'nama' => $p->nama])
            ->all();
    }
}
