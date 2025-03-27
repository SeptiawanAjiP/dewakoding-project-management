<?php

namespace App\Filament\Pages;

use App\Filament\Resources\TicketResource;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class ProjectBoard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    protected static string $view = 'filament.pages.project-board';

    protected static ?string $title = 'Project Board';

    protected static ?string $navigationLabel = 'Project Board';
    protected static ?string $navigationGroup = 'Project Visualization';

    protected static ?int $navigationSort = 2;

    public ?Project $selectedProject = null;
    public Collection $projects;
    public Collection $ticketStatuses;
    public ?Ticket $selectedTicket = null;

    public function mount(): void
    {
        $projectId = request()->query('project_id');

        if (auth()->user()->hasRole(['super_admin', 'admin'])) {
            $this->projects = Project::all();
        } else {
            $this->projects = auth()->user()->projects;
        }

        if ($projectId && $this->projects->contains('id', $projectId)) {
            $this->selectProject($projectId);
        } elseif ($this->projects->isNotEmpty()) {
            $this->selectProject($this->projects->first()->id);
        }
    }

    public function selectProject(int $projectId): void
    {
        $this->selectedTicket = null;
        $this->ticketStatuses = collect();

        $this->selectedProject = Project::find($projectId);

        $this->loadTicketStatuses();
    }

    public function loadTicketStatuses(): void
    {
        if (!$this->selectedProject) {
            $this->ticketStatuses = collect();
            return;
        }

        $this->ticketStatuses = $this->selectedProject->ticketStatuses()
            ->with(['tickets' => function ($query) {
                $query->with(['assignee', 'status'])
                    ->orderBy('created_at', 'desc');
            }])
            ->orderBy('id')
            ->get();
    }

    #[On('ticket-moved')]
    public function moveTicket($ticketId, $newStatusId): void
    {
        $ticket = Ticket::find($ticketId);

        if ($ticket && $ticket->project_id === $this->selectedProject?->id) {
            $ticket->update([
                'ticket_status_id' => $newStatusId
            ]);

            $this->loadTicketStatuses();

            $this->dispatch('ticket-updated');

            Notification::make()
                ->title('Ticket Successfully Moved')
                ->success()
                ->send();
        }
    }

    #[On('refresh-board')]
    public function refreshBoard(): void
    {
        $this->loadTicketStatuses();
        $this->dispatch('ticket-updated');
    }

    public function showTicketDetails(int $ticketId): void
    {
        $ticket = Ticket::with(['assignee', 'status', 'project'])->find($ticketId);

        if (!$ticket) {
            Notification::make()
                ->title('Ticket Not Found')
                ->danger()
                ->send();
            return;
        }

        $this->redirect(TicketResource::getUrl('view', ['record' => $ticketId]));
    }

    public function closeTicketDetails(): void
    {
        $this->selectedTicket = null;
    }

    public function editTicket(int $ticketId): void
    {
        $ticket = Ticket::find($ticketId);

        if (!$this->canEditTicket($ticket)) {
            Notification::make()
                ->title('Permission Denied')
                ->body('You do not have permission to edit this ticket.')
                ->danger()
                ->send();
            return;
        }

        $this->redirect(TicketResource::getUrl('edit', ['record' => $ticketId]));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new_ticket')
                ->label('New Ticket')
                ->icon('heroicon-m-plus')
                ->visible(fn() => $this->selectedProject !== null && auth()->user()->hasRole(['super_admin', 'admin']))
                ->url(fn(): string => TicketResource::getUrl('create', [
                    'project_id' => $this->selectedProject?->id,
                    'ticket_status_id' => $this->selectedProject?->ticketStatuses->first()?->id
                ])),

            Action::make('refresh_board')
                ->label('Refresh Board')
                ->icon('heroicon-m-arrow-path')
                ->action('refreshBoard'),
        ];
    }

    private function canViewTicket(?Ticket $ticket): bool
    {
        if (!$ticket) return false;

        return auth()->user()->hasRole(['super_admin', 'admin'])
            || $ticket->user_id === auth()->id();
    }

    private function canEditTicket(?Ticket $ticket): bool
    {
        if (!$ticket) return false;

        return auth()->user()->hasRole(['super_admin', 'admin'])
            || $ticket->user_id === auth()->id();
    }

    private function canManageTicket(?Ticket $ticket): bool
    {
        if (!$ticket) return false;

        return auth()->user()->hasRole(['super_admin', 'admin'])
            || $ticket->user_id === auth()->id();
    }
}
