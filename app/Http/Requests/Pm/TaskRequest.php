<?php

namespace App\Http\Requests\Pm;

use App\Models\Pm\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
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
            'judul' => ['required', 'max:200'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(config('pm.status_task')))],
            'prioritas' => ['required', Rule::in(array_keys(config('pm.prioritas')))],
            'deadline' => ['nullable', 'date'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'bobot' => ['required', 'numeric', 'min:0', 'max:999999'],

            // Milestone harus milik project yang sama.
            'milestone_id' => [
                'nullable',
                Rule::exists('pm_milestones', 'id')->where('project_id', $project?->id),
            ],

            // Assignee harus anggota project ini.
            'assignees' => ['array'],
            'assignees.*' => [
                Rule::exists('pm_project_members', 'user_id')->where('project_id', $project?->id),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'judul' => 'judul task',
            'milestone_id' => 'milestone',
            'assignees' => 'PIC',
        ];
    }
}
