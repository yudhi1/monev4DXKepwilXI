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
            ->with([
                'pemilik:id,name',
                'anggotas.user:id,name',
                'tasks:id,project_id,status,progress,bobot,deadline',
            ])
            ->withCount('anggotas')
            ->get();

        $idProject = $projects->pluck('id');

        // Task hanya dari project yang boleh dilihat user.
        $tasks = Task::query()
            ->whereIn('project_id', $idProject)
            ->with(['project:id,kode,nama', 'assignees:id,name'])
            ->get();

        $milikSaya = $tasks->filter(fn (Task $t) => $t->assignees->contains('id', $user->id));
        $terbukaSaya = $milikSaya->filter(fn (Task $t) => ! $t->selesai());

        /*
         | "Perlu perhatian" = pekerjaan saya yang terlambat atau jatuh tempo
         | dalam sepekan, didahulukan atas sisanya. Daftar lama hanya mengurut
         | tenggat tanpa menyatakan mana yang sudah lewat.
         */
        $perluPerhatian = $terbukaSaya
            /*
             | sort() dipakai, bukan sortBy(): yang terakhir memperlakukan
             | closure sebagai pengambil kunci (satu argumen), bukan pembanding.
             | Yang terlambat naik ke atas, sisanya urut tenggat terdekat.
             */
            ->sort(function (Task $a, Task $b) {
                return ($b->terlambat() <=> $a->terlambat())
                    ?: (($a->deadline?->timestamp ?? PHP_INT_MAX) <=> ($b->deadline?->timestamp ?? PHP_INT_MAX));
            })
            ->take(8)
            ->map(fn (Task $t) => [
                'id' => $t->id,
                'judul' => $t->judul,
                'status' => $t->status,
                'prioritas' => $t->prioritas,
                'deadline' => $t->deadline?->toDateString(),
                'progress' => $t->progress,
                'terlambat' => $t->terlambat(),
                // Dihitung di server agar Vue tidak perlu mengurus zona waktu.
                'sisaHari' => $t->deadline ? (int) now()->startOfDay()->diffInDays($t->deadline->startOfDay(), false) : null,
                'project' => ['id' => $t->project_id, 'nama' => $t->project?->nama],
            ])
            ->values();

        /* Progres keseluruhan ditimbang bobot, sama seperti progres per project. */
        $totalBobot = (float) $tasks->sum('bobot');
        $progresKeseluruhan = $totalBobot > 0
            ? (int) round($tasks->sum(fn (Task $t) => (float) $t->bobot * $t->progress) / $totalBobot)
            : 0;

        $jatuhTempo = $tasks->filter(
            fn (Task $t) => ! $t->selesai()
                && $t->deadline
                && $t->deadline->betweenIncluded(now()->startOfDay(), now()->addDays(7)->endOfDay())
        );

        /* Sebaran status: menjawab "pekerjaan menumpuk di tahap mana". */
        $sebaran = collect(config('pm.status_task'))
            ->map(fn ($meta, $kunci) => [
                'kunci' => $kunci,
                'label' => $meta['label'],
                'warna' => $meta['warna'],
                'jumlah' => $tasks->where('status', $kunci)->count(),
            ])
            ->values();

        return Inertia::render('Pm/Dashboard', [
            'ringkasan' => [
                'progres' => $progresKeseluruhan,
                'tugasSaya' => $terbukaSaya->count(),
                'jatuhTempo' => $jatuhTempo->count(),
                'terlambat' => $tasks->filter(fn (Task $t) => $t->terlambat())->count(),

                // Dipakai keterangan kecil di bawah angka, bukan kartu tersendiri.
                'project' => $projects->count(),
                'task' => $tasks->count(),
                'taskSelesai' => $tasks->where('status', $selesai)->count(),
            ],
            'sebaran' => $sebaran,
            'perluPerhatian' => $perluPerhatian,
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
                    // Nama, bukan sekadar jumlah: "3 anggota" tidak memberi tahu siapa.
                    'anggotas' => $p->anggotas->map(fn ($a) => $a->user?->name)->filter()->values(),
                    'jumlahTask' => $p->tasks->count(),
                    'jumlahSelesai' => $p->tasks->where('status', $selesai)->count(),
                    'tanggal_selesai' => $p->tanggal_selesai?->toDateString(),
                ])
                ->values(),
            'opsi' => [
                'statusProject' => config('pm.status_project'),
                'statusTask' => config('pm.status_task'),
                'prioritas' => config('pm.prioritas'),
            ],
        ]);
    }
}
