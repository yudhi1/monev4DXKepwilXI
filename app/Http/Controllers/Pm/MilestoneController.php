<?php

namespace App\Http\Controllers\Pm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pm\MilestoneRequest;
use App\Models\Pm\Milestone;
use App\Models\Pm\Project;
use Illuminate\Http\RedirectResponse;

/**
 * Milestone — tahapan besar sebuah project.
 *
 * Seluruhnya opsional: project boleh tidak punya milestone sama sekali, dan
 * task boleh berdiri tanpa milestone. Karena itu tidak ada satu pun aturan di
 * sini yang memaksa project memilikinya, dan menghapus milestone tidak ikut
 * menghapus task — task-nya cukup kembali menjadi "tanpa milestone"
 * (foreign key-nya nullOnDelete).
 */
class MilestoneController extends Controller
{
    public function store(MilestoneRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('kelolaMilestone', $project);

        $data = $request->validated();

        $project->milestones()->create([
            ...$data,
            // Tanpa urutan eksplisit, milestone baru diletakkan paling akhir.
            'urutan' => $data['urutan'] ?? ((int) $project->milestones()->max('urutan') + 1),
        ]);

        return back()->with('success', 'Milestone berhasil ditambahkan.');
    }

    public function update(MilestoneRequest $request, Project $project, Milestone $milestone): RedirectResponse
    {
        $this->authorize('kelolaMilestone', $project);
        abort_unless($milestone->project_id === $project->id, 404);

        $milestone->update($request->validated());

        return back()->with('success', 'Milestone berhasil diperbarui.');
    }

    public function destroy(Project $project, Milestone $milestone): RedirectResponse
    {
        $this->authorize('kelolaMilestone', $project);
        abort_unless($milestone->project_id === $project->id, 404);

        $jumlahTask = $milestone->tasks()->count();
        $milestone->delete();

        return back()->with('success', $jumlahTask > 0
            ? "Milestone dihapus. {$jumlahTask} task di dalamnya tetap ada, kini tanpa milestone."
            : 'Milestone berhasil dihapus.');
    }
}
