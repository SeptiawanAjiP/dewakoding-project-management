<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected $tickets;

    protected $selectedColumns;

    protected $availableColumns = [
        'uuid' => 'Ticket ID',
        'name' => 'Title',
        'description' => 'Description',
        'status' => 'Status',
        'assignee' => 'Assignee',
        'project_code' => 'Project Code',
        'project' => 'Project',
        'epic' => 'Epic',
        'start_date' => 'Start Date',
        'due_date' => 'Due Date',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ];

    public function __construct(Collection $tickets, array $selectedColumns)
    {
        $this->tickets = $tickets;
        $this->selectedColumns = $selectedColumns;
    }

    public function collection()
    {
        return $this->tickets;
    }

    public function headings(): array
    {
        $headings = [];
        foreach ($this->selectedColumns as $column) {
            if (isset($this->availableColumns[$column])) {
                $headings[] = $this->availableColumns[$column];
            }
        }

        return $headings;
    }

    public function map($ticket): array
    {
        $row = [];

        foreach ($this->selectedColumns as $column) {
            switch ($column) {
                case 'uuid':
                    $row[] = $ticket->uuid;
                    break;
                case 'name':
                    $row[] = $ticket->name;
                    break;
                case 'description':
                    $row[] = strip_tags($ticket->description ?? '');
                    break;
                case 'status':
                    $row[] = $ticket->status?->name ?? 'No Status';
                    break;
                case 'assignee':
                    $row[] = $ticket->assignees->pluck('name')->implode(', ');
                    break;
                case 'project_code':
                    $row[] = $this->projectCode($ticket->project);
                    break;
                case 'project':
                    $row[] = $ticket->project?->name ?? 'No Project';
                    break;
                case 'epic':
                    $row[] = $ticket->epic?->name ?? 'No Epic';
                    break;
                case 'start_date':
                    $row[] = $ticket->start_date ? $ticket->start_date->format('Y-m-d') : '';
                    break;
                case 'due_date':
                    $row[] = $ticket->due_date ? $ticket->due_date->format('Y-m-d') : '';
                    break;
                case 'created_at':
                    $row[] = $ticket->created_at->format('Y-m-d H:i:s');
                    break;
                case 'updated_at':
                    $row[] = $ticket->updated_at->format('Y-m-d H:i:s');
                    break;
                default:
                    $row[] = '';
                    break;
            }
        }

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FF366092',
                    ],
                ],
                'font' => [
                    'color' => [
                        'argb' => 'FFFFFFFF',
                    ],
                    'bold' => true,
                ],
            ],
        ];
    }

    public function getAvailableColumns(): array
    {
        return $this->availableColumns;
    }

    private function projectCode($project): string
    {
        if (! $project) {
            return 'PRJ';
        }

        if ($project->ticket_prefix) {
            return $project->ticket_prefix;
        }

        $words = str($project->name)
            ->replaceMatches('/[^A-Za-z0-9\s]/', ' ')
            ->squish()
            ->explode(' ')
            ->filter();

        if ($words->count() <= 1) {
            return str($project->name)->upper()->substr(0, 4)->toString();
        }

        return $words
            ->take(4)
            ->map(fn (string $word) => str($word)->substr(0, 1)->upper()->toString())
            ->implode('');
    }
}
