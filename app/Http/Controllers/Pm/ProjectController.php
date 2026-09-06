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
            ->with(['pemilik:id,name', 'unitKerja.cabang:id,nama', 'tasks:id,project_id,status,progress,bobot,deadline'])
            ->withCount('anggotas')
            ->when($cari !== '', fn ($q) => $q->where(
                fn ($sub) => $sub->where('kode', 'like', "%{$cari}%")
                    ->orWhere('nama', 'like', "%{$cari}%")
            ))
            /*
             | "aktif" bukan status di database, melainkan gabungan status yang
             | dianggap sedang dikerjakan. Ada supaya kartu dashboard bisa
             | menautkan ke daftar yang isinya persis sebanyak angkanya.
             */
            ->when($status === 'aktif', fn ($q) => $q->whereIn('status', config('pm.status_project_aktif')))
            ->when($status !== '' && $status !== 'aktif', fn ($q) => $q->where('status', $status))
            ->when($prioritas !== '', fn ($q) => $q->where('prioritas', $prioritas))
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Project $p) => $this->ringkas($p, $user));

        $bisaBuat = $user->can('create', Project::class);

        return Inertia::render('Pm/Project/Index', [
            'projects' => $projects,
            'filter' => ['cari' => $cari, 'status' => $status, 'prioritas' => $prioritas],
            'opsi' => $this->opsi(),
            'bisaBuat' => $bisaBuat,
            'kandidatAnggota' => $bisaBuat ? $this->kandidatAnggota() : [],
            'unitSaya' => $user->unitKerja ? [
                'id' => $user->unitKerja->id,
                'nama' => $user->unitKerja->nama,
                'induk' => $user->unitKerja->tingkat === 'cabang'
                    ? ($user->unitKerja->cabang?->nama ?? '-')
                    : 'Kedeputian Wilayah',
            ] : null,
        ]);
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $anggotas = $data['anggotas'] ?? [];
        unset($data['anggotas']);

        $pembuat = $request->user();

        // Project selalu dimiliki sebuah unit kerja; bawaannya unit si pembuat.
        $data['unit_kerja_id'] = $data['unit_kerja_id'] ?? $pembuat->unit_kerja_id;

        $project = DB::transaction(function () use ($data, $anggotas, $pembuat) {
            $project = Project::create([
                ...$data,
                'pemilik_id' => $pembuat->id,
            ]);

            /*
             | Pembuat selalu tercatat sebagai anggota bertaraf manager supaya
             | aturan keanggotaan (mis. validasi assignee) tidak perlu kasus
             | khusus. Kalau dia juga ada di daftar anggota, barisnya tidak
             | digandakan.
             */
            $project->anggotas()->create([
                'user_id' => $pembuat->id,
                'peran' => 'manager',
            ]);

            foreach ($anggotas as $anggota) {
                if ((int) $anggota['user_id'] === $pembuat->id) {
                    continue;
                }

                $project->anggotas()->create([
                    'user_id' => $anggota['user_id'],
                    'peran' => $anggota['peran'],
                ]);
            }

            return $project;
        });

        return redirect("/pm/projects/{$project->id}")
            ->with('success', 'Project berhasil dibuat dengan '.$project->anggotas()->count().' anggota.');
    }

    public function show(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $user = $request->user();

        $project->load([
            'pemilik:id,name',
            'unitKerja.cabang:id,nama',
            'anggotas.user:id,name,email,jabatan,unit_kerja_id',
            'anggotas.user.unitKerja.cabang:id,nama',
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
                    'jabatan' => $a->user?->jabatan,
                    'unitKerja' => $a->user?->unitKerja?->nama,
                    'induk' => $a->user?->unitKerja
                        ? ($a->user->unitKerja->tingkat === 'cabang'
                            ? ($a->user->unitKerja->cabang?->nama ?? '-')
                            : 'Kedeputian Wilayah')
                        : null,
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
        $data = $request->validated();

        /*
         | Keanggotaan tidak diubah dari sini. Form ubah project hanya memuat
         | data projectnya; anggota dikelola di tab Members pada halaman detail
         | supaya perubahan peran tidak tercampur dengan perubahan jadwal.
         */
        unset($data['anggotas']);

        $project->update($data);

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
            // Ikut dikirim karena form ubah project mengisinya dari daftar ini;
            // tanpa ini, menyimpan akan mengosongkan deskripsi yang sudah ada.
            'deskripsi' => $project->deskripsi,
            'status' => $project->status,
            'prioritas' => $project->prioritas,
            'tanggal_mulai' => $project->tanggal_mulai?->toDateString(),
            'tanggal_selesai' => $project->tanggal_selesai?->toDateString(),
            'pemilik' => $project->pemilik?->name,
            'unitKerja' => $project->unitKerja?->nama,
            'unitKerjaInduk' => $project->unitKerja
                ? ($project->unitKerja->tingkat === 'cabang'
                    ? ($project->unitKerja->cabang?->nama ?? '-')
                    : 'Kedeputian Wilayah')
                : null,
            'progress' => $project->progress(),
            'health' => $project->health(),
            'peranSaya' => $project->peranUser($user),
            // Ditentukan policy di sini; Vue tidak boleh menebak ulang aturannya.
            'bisaKelola' => $user->can('update', $project),
            'jumlahAnggota' => $project->anggotas_count ?? $project->anggotas()->count(),
            'jumlahTask' => $tasks->count(),
            'jumlahSelesai' => $tasks->where('status', $selesai)->count(),
            'jumlahTerlambat' => $tasks->filter(fn ($t) => $t->terlambat())->count(),
            // Task yang ditandai selesai tetapi realisasinya belum diisi penuh;
            // inilah yang membuat "3/4 selesai" bisa berdampingan dengan 0%.
            'jumlahRealisasiTertinggal' => $tasks->filter(fn ($t) => $t->realisasiTertinggal())->count(),
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
            'satuan' => $task->satuan,
            'target' => $task->target !== null ? (float) $task->target : null,
            'realisasi' => $task->realisasi !== null ? (float) $task->realisasi : null,
            'pakaiTarget' => $task->pakaiTarget(),
            'realisasiTertinggal' => $task->realisasiTertinggal(),
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

    /**
     * Kandidat anggota: seluruh pegawai aktif dari semua bidang, baik di
     * Kedeputian Wilayah maupun kantor cabang — keanggotaan project memang
     * boleh lintas bidang dan lintas level.
     *
     * Akun unit kerja (kc.*, kepwil) tidak ikut karena bukan perorangan.
     */
    private function kandidatAnggota(): array
    {
        return User::query()
            ->pegawai()
            ->where('is_active', true)
            ->with('unitKerja.cabang:id,nama')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'nama' => $u->name,
                'jabatan' => $u->jabatan,
                'unitKerja' => $u->unitKerja?->nama,
                'induk' => $u->unitKerja?->tingkat === 'cabang'
                    ? ($u->unitKerja?->cabang?->nama ?? '-')
                    : 'Kedeputian Wilayah',
            ])
            ->all();
    }

    private function opsi(): array
    {
        return [
            'statusProject' => config('pm.status_project'),
            'statusTask' => config('pm.status_task'),
            'prioritas' => config('pm.prioritas'),
            'peran' => config('pm.peran'),
            'satuan' => config('pm.satuan'),
        ];
    }
}
