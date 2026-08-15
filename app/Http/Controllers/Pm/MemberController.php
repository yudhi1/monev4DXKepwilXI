<?php

namespace App\Http\Controllers\Pm;

use App\Http\Controllers\Controller;
use App\Models\Pm\Project;
use App\Models\Pm\ProjectMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('kelolaAnggota', $project);

        $data = $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('pm_project_members', 'user_id')->where('project_id', $project->id),
            ],
            'peran' => ['required', Rule::in(array_keys(config('pm.peran')))],
        ], [], ['user_id' => 'anggota']);

        $project->anggotas()->create($data);

        return back()->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function update(Request $request, Project $project, ProjectMember $anggota): RedirectResponse
    {
        $this->authorize('kelolaAnggota', $project);
        abort_unless($anggota->project_id === $project->id, 404);

        $data = $request->validate([
            'peran' => ['required', Rule::in(array_keys(config('pm.peran')))],
        ]);

        if ($anggota->user_id === $project->pemilik_id && $data['peran'] !== 'manager') {
            return back()->with('error', 'Pemilik project harus tetap berperan sebagai Project Manager.');
        }

        $anggota->update($data);

        return back()->with('success', 'Peran anggota berhasil diperbarui.');
    }

    public function destroy(Request $request, Project $project, ProjectMember $anggota): RedirectResponse
    {
        $this->authorize('kelolaAnggota', $project);
        abort_unless($anggota->project_id === $project->id, 404);

        if ($anggota->user_id === $project->pemilik_id) {
            return back()->with('error', 'Pemilik project tidak dapat dikeluarkan.');
        }

        // Task yang PIC-nya dikeluarkan akan kehilangan assignee-nya, bukan ikut terhapus.
        $project->tasks()->each(fn ($task) => $task->assignees()->detach($anggota->user_id));

        $anggota->delete();

        return back()->with('success', 'Anggota berhasil dikeluarkan dari project.');
    }
}
