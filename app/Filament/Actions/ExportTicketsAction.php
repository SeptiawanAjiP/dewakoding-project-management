<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Section;

class ExportTicketsAction
{
    public static function make(): Action
    {
        return Action::make('export_tickets')
            ->label('Export to Excel')
            ->icon('heroicon-m-arrow-down-tray')
            ->color('success')
            ->schema([
                Section::make('Select Columns to Export')
                    ->description('Choose which columns you want to include in the Excel export')
                    ->schema([
                        CheckboxList::make('columns')
                            ->label('Columns')
                            ->options([
                                'uuid' => 'Ticket ID',
                                'name' => 'Title',
                                'description' => 'Description',
                                'status' => 'Status',
                                'assignee' => 'Assignee',
                                'project_code' => 'Project Code',
                                'project' => 'Project',
                                'epic' => 'Epic',
                                'due_date' => 'Due Date',
                                'created_at' => 'Created At',
                                'updated_at' => 'Updated At',
                            ])
                            ->default(['uuid', 'name', 'status', 'assignee', 'project_code', 'project', 'due_date', 'created_at'])
                            ->required()
                            ->minItems(1)
                            ->columns(2)
                            ->gridDirection('row'),
                    ]),
            ])
            ->action(function (array $data, $livewire): void {
                $livewire->exportTickets($data['columns'] ?? []);
            });
    }
}
