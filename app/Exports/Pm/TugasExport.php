<?php

namespace App\Exports\Pm;

use App\Models\Pm\Task;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Ekspor daftar tugas — baik milik sendiri maupun tugas tim.
 *
 * Isinya mengikuti persis apa yang sedang tersaring di layar, sehingga
 * berkas yang terunduh selalu sama dengan yang dilihat. Kolomnya sengaja
 * memakai label berbahasa Indonesia yang sama dengan tabelnya.
 */
class TugasExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    private int $nomor = 0;

    /**
     * @param  Collection<int, Task>  $tasks
     * @param  array<string, mixed>  $filter
     */
    public function __construct(
        private Collection $tasks,
        private array $filter = [],
    ) {}

    public function title(): string
    {
        return ($this->filter['lingkup'] ?? 'saya') === 'tim' ? 'Tugas Tim' : 'Tugas Saya';
    }

    public function collection(): Collection
    {
        return $this->tasks;
    }

    public function headings(): array
    {
        return [
            'No', 'Tugas', 'Project', 'Kode Project', 'Milestone',
            'Status', 'Prioritas', 'PIC',
            'Progres (%)', 'Satuan', 'Target', 'Realisasi',
            'Tenggat', 'Keterangan',
        ];
    }

    /** @param  Task  $task */
    public function map($task): array
    {
        $statusTask = config('pm.status_task');
        $prioritas = config('pm.prioritas');

        return [
            ++$this->nomor,
            $task->judul,
            $task->project?->nama,
            $task->project?->kode,
            $task->milestone?->nama,
            $statusTask[$task->status]['label'] ?? $task->status,
            $prioritas[$task->prioritas]['label'] ?? $task->prioritas,
            $task->assignees->pluck('name')->join(', '),
            $task->progress,
            $task->satuan,
            // Dikirim sebagai angka, bukan teks, agar bisa dijumlah di Excel.
            $task->target !== null ? (float) $task->target : null,
            $task->realisasi !== null ? (float) $task->realisasi : null,
            $task->deadline?->format('d/m/Y'),
            $this->keterangan($task),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /** Menandai yang butuh tindakan, agar terbaca tanpa membandingkan tanggal. */
    private function keterangan(Task $task): string
    {
        // Kalimatnya milik model supaya sama persis dengan yang tampil di layar.
        return $task->keteranganTenggat();
    }
}
