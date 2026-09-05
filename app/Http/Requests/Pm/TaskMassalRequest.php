<?php

namespace App\Http\Requests\Pm;

use App\Models\Pm\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Membuat beberapa task sekaligus dari satu formulir.
 *
 * Bentuknya sengaja dipisah dari TaskRequest: yang itu memvalidasi satu task
 * dan masih dipakai form ubah, sedangkan yang ini memvalidasi sebaris-sebaris.
 *
 * Status, prioritas, satuan, milestone, dan PIC berlaku untuk seluruh baris —
 * dalam pemakaian nyata (mis. task mingguan) yang berbeda antar baris hanya
 * judul, tenggat, dan angkanya.
 */
class TaskMassalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            && $this->user()->can('kelolaTask', $project);
    }

    public function rules(): array
    {
        $project = $this->route('project');

        return [
            // --- Berlaku untuk semua baris ---
            'status' => ['required', Rule::in(array_keys(config('pm.status_task')))],
            'prioritas' => ['required', Rule::in(array_keys(config('pm.prioritas')))],
            'satuan' => ['nullable', Rule::in(config('pm.satuan'))],
            'milestone_id' => [
                'nullable',
                Rule::exists('pm_milestones', 'id')->where('project_id', $project?->id),
            ],
            'assignees' => ['array'],
            'assignees.*' => [
                Rule::exists('pm_project_members', 'user_id')->where('project_id', $project?->id),
            ],

            // --- Per baris ---
            'tasks' => ['required', 'array', 'min:1', 'max:52'],
            'tasks.*.judul' => ['required', 'string', 'max:200'],
            'tasks.*.deskripsi' => ['nullable', 'string'],
            'tasks.*.deadline' => ['nullable', 'date'],
            'tasks.*.bobot' => ['required', 'numeric', 'min:0', 'max:999999'],
            'tasks.*.target' => ['nullable', 'numeric', 'min:0'],
            'tasks.*.realisasi' => ['nullable', 'numeric', 'min:0', 'required_with:tasks.*.target'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tasks' => 'daftar task',
            'tasks.*.judul' => 'judul',
            'tasks.*.bobot' => 'bobot',
            'tasks.*.target' => 'target',
            'tasks.*.realisasi' => 'realisasi',
            'tasks.*.deadline' => 'tenggat',
            'satuan' => 'satuan target',
            'milestone_id' => 'milestone',
            'assignees' => 'PIC',
        ];
    }

    public function messages(): array
    {
        return [
            'tasks.*.judul.required' => 'Judul pada baris :position belum diisi.',
            'tasks.*.realisasi.required_with' => 'Baris :position sudah punya target, jadi realisasinya harus diisi (boleh 0).',
        ];
    }
}
