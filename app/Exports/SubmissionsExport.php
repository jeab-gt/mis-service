<?php

namespace App\Exports;

use App\Models\AppSubmission;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SubmissionsExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected string $from,
        protected string $to,
        protected ?int $factoryId = null,
    ) {}

    public function collection()
    {
        return AppSubmission::with(['app', 'submitter', 'latestAssignment.assignee', 'dailyLogs'])
            ->whereBetween('created_at', [$this->from, $this->to . ' 23:59:59'])
            ->when($this->factoryId, fn($q) => $q->where('factory_id', $this->factoryId))
            ->get()
            ->map(fn($s) => [
                $s->id,
                $s->app?->name ?? '-',
                $s->submitter?->name ?? '-',
                $s->latestAssignment?->assignee?->name ?? '-',
                ucfirst(str_replace('_', ' ', $s->status)),
                $s->progress . '%',
                $s->created_at->format('d/m/Y H:i'),
                $s->submitted_at?->format('d/m/Y H:i') ?? '-',
                $s->closed_at?->format('d/m/Y H:i') ?? '-',
                $s->submitted_at && $s->closed_at
                    ? round($s->submitted_at->diffInHours($s->closed_at), 1) . ' hrs'
                    : '-',
            ]);
    }

    public function headings(): array
    {
        return ['#', 'App / Type', 'Submitter', 'Assignee', 'Status', 'Progress', 'Created', 'Submitted', 'Closed', 'Resolution Time'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            ],
        ];
    }
}
