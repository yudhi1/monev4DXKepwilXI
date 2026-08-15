<?php

namespace App\Policies\Pm;

use App\Models\Pm\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Melihat project: pemegang `pm.lihat-semua` (admin & pimpinan), atau
     * siapa pun yang terdaftar sebagai anggota/pemilik project itu.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->can('pm.lihat-semua') || $project->peranUser($user) !== null;
    }

    public function create(User $user): bool
    {
        return $user->can('pm.project.buat');
    }

    /** Mengubah project: hanya manager project itu, atau admin. */
    public function update(User $user, Project $project): bool
    {
        return $user->can('pm.kelola') || $project->peranUser($user) === 'manager';
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    /** Menambah/menghapus anggota dan mengubah perannya. */
    public function kelolaAnggota(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    /** Membuat, mengubah, memindahkan, dan menghapus task di project ini. */
    public function kelolaTask(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    /**
     * Memperbarui progres/status task.
     *
     * Berbeda dari kelolaTask: member biasa boleh menggerakkan pekerjaannya
     * sendiri, tapi tidak boleh membuat atau menghapus task.
     */
    public function ubahProgress(User $user, Project $project): bool
    {
        return $this->update($user, $project)
            || in_array($project->peranUser($user), ['manager', 'member'], true);
    }
}
