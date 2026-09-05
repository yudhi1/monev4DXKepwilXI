<?php

namespace App\Http\Controllers\Pm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pm\TaskMassalRequest;
use App\Http\Requests\Pm\TaskRequest;
use App\Models\Pm\Project;
use App\Models\Pm\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function store(TaskRequest $request, Project $project): RedirectResponse
    {
        $data = $request->validated();
        $assignees = $data['assignees'] ?? [];
        unset($data['assignees']);

        DB::transaction(function () use ($project, $data, $assignees, $request) {
            $task = $project->tasks()->create([
                ...$data,
                'dibuat_oleh' => $request->user()->id,
                // Kartu baru ditaruh di dasar kolomnya.
                'urutan' => (int) $project->tasks()->where('status', $data['status'])->max('urutan') + 1,
            ]);

            $this->selaraskanProgress($task);
            $task->assignees()->sync($assignees);
        });

        return back()->with('success', 'Task berhasil ditambahkan.');
    }

    /**
     * Membuat beberapa task sekaligus dari satu formulir.
     *
     * Status, prioritas, satuan, milestone, dan PIC diambil dari pengaturan
     * bersama; tiap baris hanya menyumbang judul, tenggat, bobot, dan angkanya.
     */
    public function storeMassal(TaskMassalRequest $request, Project $project): RedirectResponse
    {
        $data = $request->validated();
        $baris = $data['tasks'];
        $assignees = $data['assignees'] ?? [];

        DB::transaction(function () use ($project, $data, $baris, $assignees, $request) {
            // Dihitung sekali lalu dinaikkan sendiri, bukan query per baris.
            $urutan = (int) $project->tasks()->where('status', $data['status'])->max('urutan');

            foreach ($baris as $isi) {
                $task = $project->tasks()->create([
                    'judul' => $isi['judul'],
                    'deskripsi' => $isi['deskripsi'] ?? null,
                    'status' => $data['status'],
                    'prioritas' => $data['prioritas'],
                    'deadline' => $isi['deadline'] ?? null,
                    'bobot' => $isi['bobot'],
                    'satuan' => $data['satuan'] ?? null,
                    'target' => $isi['target'] ?? null,
                    'realisasi' => $isi['realisasi'] ?? null,
                    'milestone_id' => $data['milestone_id'] ?? null,
                    'progress' => 0,
                    'urutan' => ++$urutan,
                    'dibuat_oleh' => $request->user()->id,
                ]);

                $this->selaraskanProgress($task);
                $task->assignees()->sync($assignees);
            }
        });

        $jumlah = count($baris);

        return back()->with('success', "{$jumlah} task berhasil ditambahkan.");
    }

    public function update(TaskRequest $request, Project $project, Task $task): RedirectResponse
    {
        $this->pastikanMilikProject($project, $task);

        $data = $request->validated();
        $assignees = $data['assignees'] ?? [];
        unset($data['assignees']);

        DB::transaction(function () use ($task, $data, $assignees) {
            $task->update($data);
            $this->selaraskanProgress($task);
            $task->assignees()->sync($assignees);
        });

        return back()->with('success', 'Task berhasil diperbarui.');
    }

    /**
     * Memindahkan kartu di papan Kanban: ganti kolom dan/atau urutan.
     *
     * Dipisahkan dari update() karena hak aksesnya berbeda — member biasa
     * boleh menggeser pekerjaannya sendiri, tapi tidak boleh mengubah judul,
     * bobot, atau PIC.
     */
    public function pindah(Request $request, Project $project, Task $task): RedirectResponse
    {
        $this->authorize('ubahProgress', $project);
        $this->pastikanMilikProject($project, $task);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(config('pm.status_task')))],
            'urutan' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($project, $task, $data) {
            // Beri ruang di posisi tujuan sebelum kartu ini dipindahkan ke sana.
            $project->tasks()
                ->where('status', $data['status'])
                ->where('id', '!=', $task->id)
                ->where('urutan', '>=', $data['urutan'])
                ->increment('urutan');

            /*
             | Task tanpa target: masuk Done berarti rampung, progress 100.
             |
             | Task bertarget TIDAK diperlakukan begitu. Progressnya tetap
             | mengikuti realisasi, sehingga penagihan yang periodenya sudah
             | ditutup pada 70% tampil apa adanya — "selesai dikerjakan, target
             | tidak tercapai" adalah informasi, bukan kesalahan yang perlu
             | ditutupi dengan angka 100.
             */
            $task->update([
                'status' => $data['status'],
                'urutan' => $data['urutan'],
                'progress' => ! $task->pakaiTarget() && $data['status'] === config('pm.status_selesai')
                    ? 100
                    : $task->progress,
            ]);
        });

        return back();
    }

    /** Ubah progres saja — dipakai member dari kartu tanpa membuka form penuh. */
    public function progress(Request $request, Project $project, Task $task): RedirectResponse
    {
        $this->authorize('ubahProgress', $project);
        $this->pastikanMilikProject($project, $task);

        // Task bertarget diperbarui lewat realisasinya; progress ikut menyesuaikan.
        if ($task->pakaiTarget()) {
            $data = $request->validate([
                'realisasi' => ['required', 'numeric', 'min:0'],
            ]);

            $task->update($data);
            $this->selaraskanProgress($task);

            return back()->with('success', 'Realisasi task diperbarui.');
        }

        $data = $request->validate([
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $task->update($data);

        return back()->with('success', 'Progres task diperbarui.');
    }

    public function destroy(Request $request, Project $project, Task $task): RedirectResponse
    {
        $this->authorize('kelolaTask', $project);
        $this->pastikanMilikProject($project, $task);

        $task->delete();

        return back()->with('success', 'Task berhasil dihapus.');
    }

    /**
     * Menyimpan progress hasil hitungan bagi task yang punya target.
     *
     * Kolom `progress` sengaja tetap diisi, bukan dihitung saat dibaca, agar
     * progress project dan kontribusi anggota tidak perlu tahu soal target.
     */
    private function selaraskanProgress(Task $task): void
    {
        if (! $task->pakaiTarget()) {
            return;
        }

        $task->update(['progress' => $task->progressDariTarget()]);
    }

    /**
     * Task diikat ke project lewat URL bersarang; tanpa pemeriksaan ini,
     * id task dari project lain masih bisa disisipkan.
     */
    private function pastikanMilikProject(Project $project, Task $task): void
    {
        abort_unless($task->project_id === $project->id, 404);
    }
}
