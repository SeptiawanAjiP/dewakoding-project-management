<?php

namespace App\Filament\Pages;

use App\Exports\TicketsExport;
use App\Filament\Actions\ExportTicketsAction;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Epic;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Maatwebsite\Excel\Facades\Excel;

class ProjectBoard extends Page
{
    private const MODE_GLOBAL = 'global';

    private const MODE_INDEX = 'index';

    private const MODE_PROJECT = 'project';

    private const ALL_PROJECT_ROUTE = 'all-project';

    private const SELECTED_PROJECTS_ROUTE_PREFIX = 'selected-projects-';

    private const SESSION_SELECTED_GLOBAL_PROJECT_IDS = 'project_board.selected_global_project_ids';

    private const DEFAULT_COLUMN_LIMIT = 20;

    private const COLUMN_LIMIT_INCREMENT = 20;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';

    protected string $view = 'filament.pages.project-board';

    protected static ?string $title = 'Project Board';

    protected static ?string $navigationLabel = 'Project Board';

    protected static string|\UnitEnum|null $navigationGroup = 'Project Management';

    protected static ?int $navigationSort = 4;

    public function getSubheading(): ?string
    {
        return 'Kanban board for ticket management';
    }

    protected static ?string $slug = 'project-board/{project_id?}';

    public ?Project $selectedProject = null;

    public Collection $projects;

    // ticketStatuses is now a computed property - see getTicketStatusesProperty()

    public ?Ticket $selectedTicket = null;

    public ?int $selectedProjectId = null;

    public string $boardMode = self::MODE_INDEX;

    public array $sortOrders = [];

    public array $columnLimits = [];

    public array $selectedUserIds = [];

    public array $selectedGlobalProjectIds = [];

    public Collection $projectUsers;

    public string $searchProject = '';

    public function mount($project_id = null): void
    {
        $this->loadProjects();
        $this->projectUsers = collect();

        if ($project_id === null || $project_id === '') {
            if ($this->restorePersistedGlobalProjectFilter()) {
                return;
            }

            $this->showProjectIndex(navigate: false);

            return;
        }

        $routeProjectId = (string) $project_id;

        if ($routeProjectId === self::ALL_PROJECT_ROUTE) {
            $this->selectAllProjects(navigate: false);

            return;
        }

        if (str_starts_with($routeProjectId, self::SELECTED_PROJECTS_ROUTE_PREFIX)) {
            $this->selectSelectedProjects($this->selectedProjectIdsFromRoute($routeProjectId), navigate: false);

            return;
        }

        if (is_numeric($routeProjectId)) {
            $this->selectProject((int) $routeProjectId, navigate: false);

            return;
        }

        $this->showProjectIndex(navigate: false);
    }

    private function loadProjects(): void
    {
        $query = auth()->user()->hasRole(['super_admin'])
            ? Project::query()
            : auth()->user()->projects();

        $this->projects = $query
            ->select('projects.id', 'projects.name', 'projects.ticket_prefix', 'projects.color', 'projects.pinned_date')
            ->orderByRaw('pinned_date IS NULL')
            ->orderBy('pinned_date', 'desc')
            ->orderBy('name')
            ->get();
    }

    public function getFilteredProjectsProperty(): Collection
    {
        if (empty($this->searchProject)) {
            return $this->projects;
        }

        return $this->projects->filter(function ($project) {
            return str_contains(strtolower($project->name), strtolower($this->searchProject)) ||
                str_contains(strtolower($project->ticket_prefix ?? ''), strtolower($this->searchProject));
        });
    }

    public function updatedSelectedProjectId($value): void
    {
        if ($value) {
            $this->selectProject((int) $value);
        } else {
            $this->showProjectIndex();
        }
    }

    public function showProjectIndex(bool $navigate = true): void
    {
        $this->clearPersistedGlobalProjectFilter();
        $this->boardMode = self::MODE_INDEX;
        $this->selectedTicket = null;
        $this->selectedProject = null;
        $this->selectedProjectId = null;
        $this->selectedGlobalProjectIds = [];
        $this->projectUsers = collect();
        $this->selectedUserIds = [];
        $this->columnLimits = [];
        $this->loadTicketStatuses();

        if ($navigate) {
            $this->navigateToBoardUrl();
        }
    }

    public function selectAllProjects(bool $navigate = true): void
    {
        $this->clearPersistedGlobalProjectFilter();
        $this->boardMode = self::MODE_GLOBAL;
        $this->selectedTicket = null;
        $this->selectedProject = null;
        $this->selectedProjectId = null;
        $this->selectedGlobalProjectIds = [];
        $this->projectUsers = collect();
        $this->selectedUserIds = [];
        $this->columnLimits = [];
        $this->loadProjectUsers();
        $this->loadTicketStatuses();

        if ($navigate) {
            $this->navigateToBoardUrl(self::ALL_PROJECT_ROUTE);
        }
    }

    public function selectSelectedProjects(array $projectIds, bool $navigate = true): void
    {
        $this->boardMode = self::MODE_GLOBAL;
        $this->selectedTicket = null;
        $this->selectedProject = null;
        $this->selectedProjectId = null;
        $this->selectedGlobalProjectIds = $this->normalizedGlobalProjectFilterIds($projectIds);
        $this->projectUsers = collect();
        $this->selectedUserIds = [];
        $this->columnLimits = [];
        $this->loadProjectUsers();
        $this->loadTicketStatuses();
        $this->persistGlobalProjectFilter();

        if ($navigate) {
            $this->navigateToBoardUrl($this->globalRouteParameter());
        }
    }

    public function selectProject(int $projectId, bool $navigate = true): void
    {
        $this->clearPersistedGlobalProjectFilter();
        $this->boardMode = self::MODE_PROJECT;
        $this->selectedTicket = null;
        $this->selectedProjectId = $projectId;
        $this->selectedProject = $this->projects->firstWhere('id', $projectId);
        $this->selectedGlobalProjectIds = [];
        $this->selectedUserIds = [];
        $this->columnLimits = [];

        if ($this->selectedProject) {
            $this->loadProjectUsers();
            $this->loadTicketStatuses();

            // Use wire:navigate for SPA-like navigation
            if ($navigate) {
                $this->navigateToBoardUrl((string) $projectId);
            }

            return;
        }

        $this->showProjectIndex(navigate: false);
    }

    #[Computed]
    public function ticketStatuses(): Collection
    {
        if ($this->isGlobalBoard()) {
            return $this->globalBoardColumns();
        }

        if (! $this->selectedProject) {
            return collect();
        }

        return $this->projectBoardColumns();
    }

    private function projectBoardColumns(): Collection
    {
        $statuses = $this->selectedProject->ticketStatuses()
            ->select('id', 'project_id', 'name', 'color', 'sort_order', 'is_completed')
            ->orderBy('sort_order')
            ->get();

        return $statuses->map(function ($status) {
            $sortOrder = $this->sortOrders[$status->id] ?? 'date_created_newest';
            $baseQuery = $this->projectTicketsQuery($status->id);

            $status->tickets_count = (clone $baseQuery)->count();
            $status->tickets = $this->applyQuerySorting(
                (clone $baseQuery)
                    ->with($this->ticketRelations())
                    ->select($this->ticketSelectColumns()),
                $sortOrder
            )
                ->limit($this->getColumnLimit($status->id))
                ->get();

            return $status;
        });
    }

    public function loadTicketStatuses(): void
    {
        // Force recompute of ticketStatuses by clearing cache
        unset($this->ticketStatuses);
    }

    public function loadProjectUsers(): void
    {
        if ($this->isProjectIndex()) {
            $this->projectUsers = collect();

            return;
        }

        $projectIds = $this->selectedProject
            ? [$this->selectedProject->id]
            : $this->visibleGlobalProjectIds();

        if (empty($projectIds)) {
            $this->projectUsers = collect();

            return;
        }

        $this->projectUsers = User::query()
            ->select('users.id', 'users.name')
            ->whereHas('assignedTickets', function (Builder $query) use ($projectIds) {
                $query->whereIn('tickets.project_id', $projectIds);
            })
            ->orderBy('name')
            ->get();
    }

    public function updatedSelectedUserIds(): void
    {
        $this->loadTicketStatuses();
    }

    public function clearUserFilter(): void
    {
        $this->selectedUserIds = [];
        $this->loadTicketStatuses();
    }

    public function setSortOrder($statusId, $sortOrder)
    {
        $this->sortOrders[$statusId] = $sortOrder;
        $this->loadTicketStatuses();
    }

    public function loadMoreTickets(string $columnId): void
    {
        $this->columnLimits[$columnId] = $this->getColumnLimit($columnId) + self::COLUMN_LIMIT_INCREMENT;
        $this->loadTicketStatuses();
        $this->dispatch('ticket-updated');
    }

    private function applyQuerySorting(Builder $query, string $sortOrder): Builder
    {
        switch ($sortOrder) {
            case 'date_created_newest':
                return $query->orderByDesc('created_at')->orderByDesc('id');
            case 'date_created_oldest':
                return $query->orderBy('created_at')->orderBy('id');
            case 'card_name_alphabetical':
                return $query->orderBy('name')->orderByDesc('created_at');
            case 'due_date':
                return $query->orderByRaw('due_date IS NULL')->orderBy('due_date')->orderByDesc('created_at');
            case 'priority':
                return $query->orderByRaw('priority_id IS NULL')->orderBy('priority_id')->orderByDesc('created_at');
            default:
                return $query->orderByDesc('created_at')->orderByDesc('id');
        }
    }

    #[On('ticket-moved')]
    public function moveTicket($ticketId, $newStatusId): void
    {
        $ticket = Ticket::find($ticketId);

        if (! $ticket) {
            Notification::make()
                ->title('Ticket Not Found')
                ->danger()
                ->send();

            return;
        }

        if (! $this->canManageTicket($ticket)) {
            Notification::make()
                ->title('Permission Denied')
                ->body('You do not have permission to move this ticket.')
                ->danger()
                ->send();

            return;
        }

        $targetStatus = $this->resolveTargetStatus($ticket, (string) $newStatusId);

        if (! $targetStatus) {
            Notification::make()
                ->title('Status Not Found')
                ->body("This ticket's project does not have that exact status.")
                ->warning()
                ->send();

            return;
        }

        if ($ticket->ticket_status_id === $targetStatus->id) {
            return;
        }

        $ticket->update([
            'ticket_status_id' => $targetStatus->id,
        ]);

        $this->loadTicketStatuses();

        $this->dispatch('ticket-updated');

        Notification::make()
            ->title('Ticket Updated')
            ->success()
            ->send();
    }

    #[On('refresh-board')]
    public function refreshBoard(): void
    {
        $this->loadTicketStatuses();
        $this->dispatch('ticket-updated');

        Notification::make()
            ->title('Board Refreshed')
            ->success()
            ->send();
    }

    public function showTicketDetails(int $ticketId): void
    {
        $ticket = Ticket::with(['assignees', 'status', 'project', 'priority'])->find($ticketId);

        if (! $ticket) {
            Notification::make()
                ->title('Ticket Not Found')
                ->danger()
                ->send();

            return;
        }

        $url = $this->ticketViewUrl($ticketId);
        $this->js("window.open('{$url}', '_blank')");
    }

    public function closeTicketDetails(): void
    {
        $this->selectedTicket = null;
    }

    public function editTicket(int $ticketId): void
    {
        $ticket = Ticket::find($ticketId);

        if (! $this->canEditTicket($ticket)) {
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
                ->name('ticket_on_board')
                ->label('New Ticket')
                ->icon('heroicon-m-plus')
                ->visible(fn () => ! $this->isProjectIndex() && auth()->user()->can('create_ticket'))
                ->schema(function ($schema) {
                    // When in global board mode, we need a project selector first
                    if ($this->isGlobalBoard()) {
                        return $this->globalBoardTicketForm($schema);
                    }

                    return TicketResource::form($schema)->columns(3);
                })
                ->model(Ticket::class)
                ->fillForm(function () {
                    if ($this->isGlobalBoard()) {
                        return [
                            'project_id' => null,
                            'ticket_status_id' => null,
                            'assignees' => [],
                        ];
                    }

                    $assignees = [];

                    // Auto-assign current user if they're a project member
                    if ($project = $this->selectedProject) {
                        $isCurrentUserMember = $project->members()->where('users.id', auth()->id())->exists();
                        $assignees = $isCurrentUserMember ? [auth()->id()] : [];
                    }

                    return [
                        'project_id' => $this->selectedProject?->id,
                        'ticket_status_id' => $this->ticketStatuses?->first()?->id,
                        'assignees' => $assignees,
                    ];
                })
                ->action(function (array $data, $schema) {
                    $data['created_by'] = auth()->id();

                    // Extract assignees — not a DB column, handle separately
                    $assigneeIds = $data['assignees'] ?? [];
                    unset($data['assignees']);

                    $model = $schema->getModel();
                    $record = $model::create($data);

                    // Sync assignees to pivot table
                    if (! empty($assigneeIds)) {
                        $record->assignees()->sync($assigneeIds);
                    } elseif (! empty($data['project_id'])) {
                        // Auto-assign current user if they're a project member
                        $project = Project::find($data['project_id']);
                        if ($project && $project->members()->where('users.id', auth()->id())->exists()) {
                            $record->assignees()->sync([auth()->id()]);
                        }
                    }

                    Notification::make()
                        ->title('Ticket Created')
                        ->body('The ticket has been created successfully.')
                        ->success()
                        ->send();

                    $this->loadTicketStatuses();
                    $this->dispatch('ticket-updated');
                }),

            Action::make('refresh_board')
                ->label('Refresh Board')
                ->icon('heroicon-m-arrow-path')
                ->action('refreshBoard')
                ->visible(fn () => ! $this->isProjectIndex())
                ->color('warning'),
            Action::make('filter_projects')
                ->label(fn () => $this->projectFilterActionLabel())
                ->icon('heroicon-m-funnel')
                ->visible(fn () => ($this->isProjectIndex() || $this->isGlobalBoard()) && $this->projects->isNotEmpty())
                ->schema([
                    CheckboxList::make('selectedGlobalProjectIds')
                        ->label('Projects to Show')
                        ->helperText('Leave empty to show every accessible project.')
                        ->options(fn () => $this->projectFilterOptionLabels())
                        ->allowHtml()
                        ->columns(2)
                        ->searchable()
                        ->bulkToggleable(),
                ])
                ->action(function (array $data) {
                    $selectedProjectIds = $this->normalizedGlobalProjectFilterIds($data['selectedGlobalProjectIds'] ?? []);

                    if (empty($selectedProjectIds)) {
                        $this->selectAllProjects();
                    } else {
                        $this->selectSelectedProjects($selectedProjectIds);
                    }

                    $projectCount = count($selectedProjectIds);
                    if ($projectCount > 0) {
                        Notification::make()
                            ->title('Project Filter Applied')
                            ->body("Showing tickets for {$projectCount} selected project(s).")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Project Filter Cleared')
                            ->body('Showing tickets from all accessible projects.')
                            ->info()
                            ->send();
                    }
                })
                ->fillForm(fn () => [
                    'selectedGlobalProjectIds' => $this->normalizedGlobalProjectFilterIds($this->selectedGlobalProjectIds),
                ])
                ->modalWidth('lg')
                ->color('gray'),
            ExportTicketsAction::make()
                ->visible(fn () => ! $this->isProjectIndex() && auth()->user()->hasRole(['super_admin'])),

            Action::make('filter_users')
                ->label('Filter by User')
                ->icon('heroicon-m-user-group')
                ->visible(fn () => ! $this->isProjectIndex() && $this->projectUsers->isNotEmpty())
                ->schema([
                    CheckboxList::make('selectedUserIds')
                        ->label('Select Users to Filter')
                        ->options(fn () => $this->projectUsers->pluck('name', 'id')->toArray())
                        ->columns(2)
                        ->searchable()
                        ->bulkToggleable(),
                ])
                ->action(function (array $data) {
                    $this->selectedUserIds = $data['selectedUserIds'] ?? [];
                    $this->loadTicketStatuses();

                    $userCount = count($this->selectedUserIds);
                    if ($userCount > 0) {
                        Notification::make()
                            ->title('Filter Applied')
                            ->body("Showing tickets for {$userCount} selected user(s)")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Filter Cleared')
                            ->body('Showing all tickets')
                            ->info()
                            ->send();
                    }
                })
                ->fillForm([
                    'selectedUserIds' => $this->selectedUserIds,
                ])
                ->modalWidth('md')
                ->color('info'),
        ];
    }

    private function canViewTicket(?Ticket $ticket): bool
    {
        if (! $ticket) {
            return false;
        }

        if (! auth()->user()->can('view_ticket')) {
            return false;
        }

        return auth()->user()->hasRole(['super_admin'])
            || $ticket->created_by === auth()->id()
            || $this->userIsTicketAssignee($ticket);
    }

    private function canEditTicket(?Ticket $ticket): bool
    {
        if (! $ticket) {
            return false;
        }

        // Check Filament Shield permission for updating tickets
        if (! auth()->user()->can('update_ticket')) {
            return false;
        }

        // Additional business logic: user can edit if they are:
        // 1. Super admin (already covered by permission above)
        // 2. The ticket creator
        // 3. Assigned to the ticket
        return auth()->user()->hasRole(['super_admin'])
            || $ticket->created_by === auth()->id()
            || $this->userIsTicketAssignee($ticket);
    }

    private function canManageTicket(?Ticket $ticket): bool
    {
        if (! $ticket) {
            return false;
        }
        if (! auth()->user()->can('update_ticket')) {
            return false;
        }

        return auth()->user()->hasRole(['super_admin'])
            || $ticket->created_by === auth()->id()
            || $this->userIsTicketAssignee($ticket);
    }

    public function canMoveTicket(?Ticket $ticket): bool
    {
        return $this->canManageTicket($ticket);
    }

    private function userIsTicketAssignee(Ticket $ticket): bool
    {
        if ($ticket->relationLoaded('assignees')) {
            return $ticket->assignees->contains('id', auth()->id());
        }

        return $ticket->assignees()->where('users.id', auth()->id())->exists();
    }

    public function exportTickets(array $selectedColumns): void
    {
        if (empty($selectedColumns)) {
            Notification::make()
                ->title('Export Failed')
                ->body('Please select at least one column to export.')
                ->danger()
                ->send();

            return;
        }

        if ($this->isGlobalBoard()) {
            $insertAfter = array_search('assignee', $selectedColumns, true);
            $insertAt = $insertAfter === false ? 0 : $insertAfter + 1;
            $requiredProjectColumns = array_values(array_filter(
                ['project_code', 'project'],
                fn (string $column) => ! in_array($column, $selectedColumns, true)
            ));

            if (! empty($requiredProjectColumns)) {
                array_splice($selectedColumns, $insertAt, 0, $requiredProjectColumns);
            }
        }

        if ($this->selectedProject) {
            $tickets = Ticket::query()
                ->where('project_id', $this->selectedProject->id)
                ->when(! empty($this->selectedUserIds), function (Builder $query) {
                    $query->whereHas('assignees', function (Builder $assigneeQuery) {
                        $assigneeQuery->whereIn('users.id', $this->selectedUserIds);
                    });
                })
                ->with(['assignees', 'status', 'project', 'epic'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $projectIds = $this->visibleGlobalProjectIds();
            $tickets = Ticket::query()
                ->when(
                    empty($projectIds),
                    fn (Builder $query) => $query->whereRaw('1 = 0'),
                    fn (Builder $query) => $query->whereIn('project_id', $projectIds)
                )
                ->when(! empty($this->selectedUserIds), function (Builder $query) {
                    $query->whereHas('assignees', function (Builder $assigneeQuery) {
                        $assigneeQuery->whereIn('users.id', $this->selectedUserIds);
                    });
                })
                ->with(['assignees', 'status', 'project', 'epic'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($tickets->isEmpty()) {
            Notification::make()
                ->title('Export Failed')
                ->body('No tickets found to export.')
                ->warning()
                ->send();

            return;
        }

        try {
            $globalFileName = $this->hasGlobalProjectFilter() ? 'selected_projects' : 'all_projects';
            $fileName = 'tickets_'.($this->selectedProject?->name ?? $globalFileName).'_'.now()->format('Y-m-d_H-i-s').'.xlsx';
            $fileName = Str::slug($fileName, '_').'.xlsx';
            $export = new TicketsExport($tickets, $selectedColumns);
            Excel::store($export, 'exports/'.$fileName, 'public');
            $downloadUrl = asset('storage/exports/'.$fileName);
            $this->js("
                fetch('{$downloadUrl}')
                    .then(response => response.blob())
                    .then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.style.display = 'none';
                        a.href = url;
                        a.download = '{$fileName}';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                    });
            ");

            Notification::make()
                ->title('Export Successful')
                ->body('Your Excel file is being downloaded.')
                ->success()
                ->send();

        } catch (Exception $e) {
            Notification::make()
                ->title('Export Failed')
                ->body('An error occurred while exporting: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function canMoveTickets(): bool
    {
        return auth()->user()->can('update_ticket');
    }

    public function isGlobalBoard(): bool
    {
        return $this->boardMode === self::MODE_GLOBAL;
    }

    public function isProjectIndex(): bool
    {
        return $this->boardMode === self::MODE_INDEX;
    }

    public function ticketViewUrl(int $ticketId): string
    {
        $parameters = ['record' => $ticketId];

        if ($from = $this->currentBoardRouteParameter()) {
            $parameters['from'] = $from;
        }

        return TicketResource::getUrl('view', $parameters);
    }

    private function currentBoardRouteParameter(): ?string
    {
        if ($this->isGlobalBoard()) {
            return str_replace(',', '-', $this->globalRouteParameter());
        }

        if ($this->selectedProjectId) {
            return (string) $this->selectedProjectId;
        }

        return null;
    }

    public function boardTitle(): string
    {
        if ($this->selectedProject) {
            return $this->selectedProject->name;
        }

        if ($this->hasGlobalProjectFilter()) {
            return 'Selected Projects';
        }

        return 'All Projects';
    }

    public function projectFilterActionLabel(): string
    {
        if ($this->isProjectIndex()) {
            return 'Selected Projects';
        }

        if ($this->hasGlobalProjectFilter()) {
            return 'Projects ('.$this->globalProjectFilterCount().')';
        }

        return 'Projects';
    }

    public function selectedGlobalProjectBadges(): Collection
    {
        $projectIds = $this->normalizedGlobalProjectFilterIds($this->selectedGlobalProjectIds);

        if (empty($projectIds)) {
            return collect();
        }

        return $this->projects
            ->whereIn('id', $projectIds)
            ->map(fn (Project $project) => $this->projectBadge($project))
            ->values();
    }

    public function hasGlobalProjectFilter(): bool
    {
        return $this->isGlobalBoard() && $this->globalProjectFilterCount() > 0;
    }

    public function globalProjectFilterCount(): int
    {
        return count($this->normalizedGlobalProjectFilterIds($this->selectedGlobalProjectIds));
    }

    public function getReadableTextColor(?string $color): string
    {
        $hex = ltrim($color ?: '#6B7280', '#');

        if (strlen($hex) !== 6) {
            return '#FFFFFF';
        }

        $red = hexdec(substr($hex, 0, 2));
        $green = hexdec(substr($hex, 2, 2));
        $blue = hexdec(substr($hex, 4, 2));
        $brightness = (($red * 299) + ($green * 587) + ($blue * 114)) / 1000;

        return $brightness > 155 ? '#1F2937' : '#FFFFFF';
    }

    private function globalBoardColumns(): Collection
    {
        $projectIds = $this->visibleGlobalProjectIds();

        if (empty($projectIds)) {
            return collect();
        }

        $statusGroups = TicketStatus::query()
            ->whereIn('project_id', $projectIds)
            ->select('id', 'project_id', 'name', 'color', 'sort_order', 'is_completed')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('name')
            ->sortBy(function (Collection $statuses, string $name) {
                return str_pad((string) ($statuses->min('sort_order') ?? 0), 10, '0', STR_PAD_LEFT).'_'.$name;
            });

        return $statusGroups->map(function (Collection $statuses, string $statusName) {
            $columnId = $this->globalColumnId($statusName);
            $sortOrder = $this->sortOrders[$columnId] ?? 'due_date';
            $statusIds = $statuses->pluck('id')->all();
            $baseQuery = $this->globalTicketsQuery($statusIds);
            $uniqueColors = $statuses->pluck('color')->filter()->unique()->values();
            $projectBadges = $this->projects
                ->whereIn('id', $statuses->pluck('project_id')->unique())
                ->map(fn (Project $project) => $this->projectBadge($project))
                ->values();

            return (object) [
                'id' => $columnId,
                'name' => $statusName,
                'color' => $uniqueColors->count() === 1 ? $uniqueColors->first() : '#4B5563',
                'is_completed' => $statuses->contains(fn (TicketStatus $status) => $status->is_completed),
                'project_ids' => $statuses->pluck('project_id')->unique()->values(),
                'project_badges' => $projectBadges,
                'project_count' => $statuses->pluck('project_id')->unique()->count(),
                'tickets_count' => (clone $baseQuery)->count(),
                'tickets' => $this->applyQuerySorting(
                    (clone $baseQuery)
                        ->with($this->ticketRelations())
                        ->select($this->ticketSelectColumns()),
                    $sortOrder
                )
                    ->limit($this->getColumnLimit($columnId))
                    ->get(),
            ];
        })->values();
    }

    private function projectTicketsQuery(int $statusId): Builder
    {
        return Ticket::query()
            ->where('project_id', $this->selectedProject->id)
            ->where('ticket_status_id', $statusId)
            ->when(! empty($this->selectedUserIds), function (Builder $query) {
                $query->whereHas('assignees', function (Builder $assigneeQuery) {
                    $assigneeQuery->whereIn('users.id', $this->selectedUserIds);
                });
            });
    }

    private function globalTicketsQuery(array $statusIds): Builder
    {
        return Ticket::query()
            ->when(
                empty($statusIds),
                fn (Builder $query) => $query->whereRaw('1 = 0'),
                fn (Builder $query) => $query->whereIn('ticket_status_id', $statusIds)
            )
            ->when(! empty($this->selectedUserIds), function (Builder $query) {
                $query->whereHas('assignees', function (Builder $assigneeQuery) {
                    $assigneeQuery->whereIn('users.id', $this->selectedUserIds);
                });
            });
    }

    private function accessibleProjectIds(): array
    {
        return $this->projects->pluck('id')->all();
    }

    private function visibleGlobalProjectIds(): array
    {
        if (! $this->isGlobalBoard()) {
            return [];
        }

        $filteredProjectIds = $this->normalizedGlobalProjectFilterIds($this->selectedGlobalProjectIds);

        if (empty($filteredProjectIds)) {
            return $this->accessibleProjectIds();
        }

        return $filteredProjectIds;
    }

    private function projectFilterOptionLabels(): array
    {
        return $this->projects
            ->mapWithKeys(fn (Project $project) => [
                $project->id => $this->projectFilterOptionLabel($project),
            ])
            ->toArray();
    }

    private function projectFilterOptionLabel(Project $project): string
    {
        $badge = $this->projectBadge($project);
        $pinnedIcon = $badge['is_pinned']
            ? sprintf(
                '<span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full" style="background-color: %s;" title="Pinned"><svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M16 9V4h1c.55 0 1-.45 1-1s-.45-1-1-1H7c-.55 0-1 .45-1 1s.45 1 1 1h1v5c0 1.66-1.34 3-3 3v2h5.97v7l1 1 1-1v-7H19v-2c-1.66 0-3-1.34-3-3z" /></svg></span>',
                e($badge['color']),
            )
            : '';

        return sprintf(
            '<span class="flex min-w-0 items-center gap-2">%s<span class="shrink-0 rounded px-2 py-0.5 text-xs font-semibold" style="background-color: %s; color: %s;">%s</span><span class="min-w-0 truncate text-sm font-medium">%s</span></span>',
            $pinnedIcon,
            e($badge['color']),
            e($badge['text_color']),
            e($badge['code']),
            e($badge['name']),
        );
    }

    private function projectBadge(Project $project): array
    {
        $color = $this->safeProjectColor($project->color);

        return [
            'id' => $project->id,
            'name' => $project->name,
            'code' => $this->projectCode($project),
            'color' => $color,
            'is_pinned' => $project->is_pinned,
            'text_color' => $this->getReadableTextColor($color),
        ];
    }

    private function safeProjectColor(?string $color): string
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color ?? '') ? $color : '#6B7280';
    }

    private function navigateToBoardUrl(?string $projectId = null): void
    {
        $url = $projectId === null
            ? static::getUrl()
            : static::getUrl(['project_id' => $projectId]);

        $this->js("Livewire.navigate('{$url}')");
    }

    private function globalRouteParameter(): string
    {
        $projectIds = $this->normalizedGlobalProjectFilterIds($this->selectedGlobalProjectIds);

        if (empty($projectIds)) {
            return self::ALL_PROJECT_ROUTE;
        }

        return self::SELECTED_PROJECTS_ROUTE_PREFIX.implode(',', $projectIds);
    }

    private function selectedProjectIdsFromRoute(string $routeProjectId): array
    {
        $projectIds = Str::after($routeProjectId, self::SELECTED_PROJECTS_ROUTE_PREFIX);

        return collect(preg_split('/[,-]+/', $projectIds) ?: [])
            ->filter(fn (string $projectId) => ctype_digit($projectId))
            ->map(fn (string $projectId) => (int) $projectId)
            ->values()
            ->all();
    }

    private function restorePersistedGlobalProjectFilter(): bool
    {
        $projectIds = session(self::SESSION_SELECTED_GLOBAL_PROJECT_IDS, []);

        if (! is_array($projectIds)) {
            $this->clearPersistedGlobalProjectFilter();

            return false;
        }

        $projectIds = $this->normalizedGlobalProjectFilterIds($projectIds);

        if (empty($projectIds)) {
            $this->clearPersistedGlobalProjectFilter();

            return false;
        }

        $this->selectSelectedProjects($projectIds, navigate: false);

        return true;
    }

    private function persistGlobalProjectFilter(): void
    {
        $projectIds = $this->normalizedGlobalProjectFilterIds($this->selectedGlobalProjectIds);

        if (empty($projectIds)) {
            $this->clearPersistedGlobalProjectFilter();

            return;
        }

        session([self::SESSION_SELECTED_GLOBAL_PROJECT_IDS => $projectIds]);
    }

    private function clearPersistedGlobalProjectFilter(): void
    {
        session()->forget(self::SESSION_SELECTED_GLOBAL_PROJECT_IDS);
    }

    private function normalizedGlobalProjectFilterIds(array $projectIds): array
    {
        $projectIds = $this->sanitizeProjectIds($projectIds);

        if (count($projectIds) === count($this->accessibleProjectIds())) {
            return [];
        }

        return $projectIds;
    }

    private function sanitizeProjectIds(array $projectIds): array
    {
        $accessibleProjectIds = collect($this->accessibleProjectIds())
            ->map(fn ($projectId) => (int) $projectId)
            ->values();

        return collect($projectIds)
            ->map(fn ($projectId) => (int) $projectId)
            ->unique()
            ->filter(fn (int $projectId) => $accessibleProjectIds->containsStrict($projectId))
            ->values()
            ->all();
    }

    private function resolveTargetStatus(Ticket $ticket, string $targetStatusId): ?TicketStatus
    {
        if (is_numeric($targetStatusId)) {
            return TicketStatus::query()
                ->where('id', (int) $targetStatusId)
                ->where('project_id', $ticket->project_id)
                ->first();
        }

        $targetStatusName = $this->globalColumnName($targetStatusId);

        if ($targetStatusName === null) {
            return null;
        }

        return TicketStatus::query()
            ->where('project_id', $ticket->project_id)
            ->where('name', $targetStatusName)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }

    private function globalColumnId(string $statusName): string
    {
        return 'status:'.rtrim(strtr(base64_encode($statusName), '+/', '-_'), '=');
    }

    private function globalColumnName(string $columnId): ?string
    {
        if (! str_starts_with($columnId, 'status:')) {
            return null;
        }

        $encoded = strtr(substr($columnId, strlen('status:')), '-_', '+/');
        $encoded .= str_repeat('=', (4 - strlen($encoded) % 4) % 4);
        $decoded = base64_decode($encoded, true);

        return $decoded === false ? null : $decoded;
    }

    public function projectCode(?Project $project): string
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

    private function ticketRelations(): array
    {
        return [
            'assignees:id,name',
            'status:id,project_id,name,color,is_completed',
            'priority:id,name,color',
            'creator:id,name',
            'project:id,name,ticket_prefix,color',
        ];
    }

    private function ticketSelectColumns(): array
    {
        return [
            'id',
            'project_id',
            'ticket_status_id',
            'priority_id',
            'name',
            'description',
            'uuid',
            'due_date',
            'created_at',
            'updated_at',
            'created_by',
        ];
    }

    private function getColumnLimit(int|string $columnId): int
    {
        return $this->columnLimits[(string) $columnId] ?? self::DEFAULT_COLUMN_LIMIT;
    }

    private function globalBoardTicketForm(Schema $schema): Schema
    {
        // Resolve which project IDs are visible based on current filter
        $visibleProjectIds = $this->visibleGlobalProjectIds();

        $projectOptions = $this->projects
            ->when(
                ! empty($visibleProjectIds),
                fn (Collection $projects) => $projects->whereIn('id', $visibleProjectIds)
            )
            ->pluck('name', 'id')
            ->toArray();

        return $schema
            ->columns(3)
            ->components([
                Select::make('project_id')
                    ->label('Project')
                    ->options($projectOptions)
                    ->required()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (callable $set) {
                        $set('ticket_status_id', null);
                        $set('assignees', []);
                        $set('epic_id', null);
                    })
                    ->columnSpanFull(),

                Select::make('ticket_status_id')
                    ->label('Status')
                    ->options(function (callable $get) {
                        $projectId = $get('project_id');
                        if (! $projectId) {
                            return [];
                        }

                        return TicketStatus::where('project_id', $projectId)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->required()
                    ->searchable()
                    ->hidden(fn (callable $get): bool => ! $get('project_id')),

                Select::make('priority_id')
                    ->label('Priority')
                    ->options(TicketPriority::pluck('name', 'id')->toArray())
                    ->searchable()
                    ->nullable()
                    ->hidden(fn (callable $get): bool => ! $get('project_id')),

                Select::make('epic_id')
                    ->label('Epic')
                    ->options(function (callable $get) {
                        $projectId = $get('project_id');
                        if (! $projectId) {
                            return [];
                        }

                        return Epic::where('project_id', $projectId)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->nullable()
                    ->hidden(fn (callable $get): bool => ! $get('project_id')),

                TextInput::make('name')
                    ->label('Ticket Name')
                    ->required()
                    ->maxLength(255)
                    ->hidden(fn (callable $get): bool => ! $get('project_id'))
                    ->columnSpanFull(),

                Select::make('assignees')
                    ->label('Assigned to')
                    ->multiple()
                    ->options(function (callable $get) {
                        $projectId = $get('project_id');
                        if (! $projectId) {
                            return [];
                        }

                        $project = Project::find($projectId);
                        if (! $project) {
                            return [];
                        }

                        return $project->members()
                            ->orderBy('name')
                            ->pluck('name', 'users.id')
                            ->toArray();
                    })
                    ->searchable()
                    ->live()
                    ->hidden(fn (callable $get): bool => ! $get('project_id'))
                    ->helperText('Only project members can be assigned.'),

                DatePicker::make('start_date')
                    ->label('Start Date')
                    ->default(now())
                    ->nullable()
                    ->hidden(fn (callable $get): bool => ! $get('project_id')),

                DatePicker::make('due_date')
                    ->label('Due Date')
                    ->nullable()
                    ->hidden(fn (callable $get): bool => ! $get('project_id')),

                RichEditor::make('description')
                    ->label('Description')
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('attachments')
                    ->fileAttachmentsVisibility('public')
                    ->nullable()
                    ->hidden(fn (callable $get): bool => ! $get('project_id'))
                    ->columnSpanFull(),
            ]);
    }
}
