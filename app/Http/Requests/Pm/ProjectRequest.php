<?php

namespace App\Http\Requests\Pm;

use App\Models\Pm\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            ? $this->user()->can('update', $project)
            : $this->user()->can('create', Project::class);
    }

    public function rules(): array
    {
        return [
            'kode' => [
                'required', 'max:30',
                Rule::unique('pm_projects', 'kode')->ignore($this->route('project')?->id),
            ],
            'nama' => ['required', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(config('pm.status_project')))],
            'prioritas' => ['required', Rule::in(array_keys(config('pm.prioritas')))],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ];
    }

    public function attributes(): array
    {
        return [
            'kode' => 'kode project',
            'nama' => 'nama project',
            'tanggal_mulai' => 'tanggal mulai',
            'tanggal_selesai' => 'tanggal selesai',
        ];
    }
}
