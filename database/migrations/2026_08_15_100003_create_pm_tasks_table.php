<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('pm_projects')->cascadeOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained('pm_milestones')->nullOnDelete();

            $table->string('judul');
            $table->text('deskripsi')->nullable();

            // Kolom papan Kanban & prioritas; nilai dari config/pm.php.
            $table->string('status', 20)->default('backlog');
            $table->string('prioritas', 10)->default('sedang');

            $table->date('deadline')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);

            /*
             | Bobot relatif di dalam satu project — tidak wajib berjumlah 100.
             | Persentase kontribusi dihitung terhadap total bobot project.
             */
            $table->decimal('bobot', 8, 2)->default(1);

            // Urutan kartu di dalam satu kolom Kanban.
            $table->unsignedInteger('urutan')->default(0);

            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'status', 'urutan']);
            $table->index('deadline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_tasks');
    }
};
