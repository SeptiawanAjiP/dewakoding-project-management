<?php

namespace App\Livewire;

use App\Models\ExternalAccess;
use App\Models\TicketHistory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.external')]
class ExternalDashboard extends Component
{
    use WithPagination;

    private const SESSION_LIFETIME_MINUTES = 720;

    public $project;

    public $token;

    public $selectedStatus = '';

    public $selectedPriority = null;

    public $searchTerm = '';

    public $totalTickets = 0;

    public $completedTickets = 0;

    public $progressPercentage = 0;

    public $statuses;

    public $priorities;

    public $activeTab = 'tasks';

    public $ticketsByStatus = [];

    public $ticketsByPriority = [];

    public $recentTickets = [];

    public $projectStats = [];

    public $monthlyTrend = [];

    public $overdueTickets = 0;

    public $newTicketsThisWeek = 0;

    public $completedThisWeek = 0;

    public $staticDataLoaded = false;

    protected $paginationTheme = 'tailwind';

    protected $listeners = ['refreshData'];

    public function refreshData()
    {
        try {
            $this->loadWidgetData();

            $this->resetPage('tickets');
            $this->resetPage('activities');

            $this->dispatch('data-refreshed');

            session()->flash('message', 'Data refreshed successfully!');
        } catch (Exception $e) {
            session()->flash('error', 'Failed to refresh data. Please try again.');
        }
    }

    public function mount($token)
    {
        $this->token = $token;

        if (! Session::get('external_authenticated_'.$token)) {
            return redirect()->route('external.login', $token);
        }

        $authenticatedAt = Session::get('external_authenticated_at_'.$token);

        if (! $authenticatedAt || now()->timestamp - $authenticatedAt > self::SESSION_LIFETIME_MINUTES * 60) {
            Session::forget([
                'external_authenticated_'.$token,
                'external_authenticated_at_'.$token,
            ]);

            return redirect()->route('external.login', $token);
        }

        $externalAccess = ExternalAccess::where('access_token', $token)
            ->where('is_active', true)
            ->first();

        if (! $externalAccess) {
            abort(404, 'External access not found');
        }

        $this->project = $externalAccess->project;

        $this->statuses = TicketStatus::where('project_id', $this->project->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $this->priorities = TicketPriority::orderBy('name')->get();

        $externalAccess->updateLastAccessed();

        $this->loadWidgetData();
    }

    public function getTicketsProperty()
    {
        $query = $this->project->tickets()
            ->with(['status', 'priority', 'assignees'])
            ->when($this->selectedStatus, function ($q) {
                $q->where('ticket_status_id', $this->selectedStatus);
            })
            ->when($this->selectedPriority, function ($q) {
                $q->where('priority_id', $this->selectedPriority);
            })
            ->when($this->searchTerm, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', '%'.$this->searchTerm.'%')
                        ->orWhere('description', 'like', '%'.$this->searchTerm.'%')
                        ->orWhere('uuid', 'like', '%'.$this->searchTerm.'%');
                });
            })
            ->orderBy('id', 'asc');

        return $query->paginate(10, ['*'], 'tickets');
    }

    public function getRecentActivitiesProperty()
    {
        return TicketHistory::whereHas('ticket', function ($q) {
            $q->where('project_id', $this->project->id);
        })
            ->with(['ticket', 'status'])
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'activities');
    }

    public function updatingSelectedStatus()
    {
        $this->resetPage('tickets');
    }

    public function updatingSearchTerm()
    {
        $this->resetPage('tickets');
    }

    public function updatingSelectedPriority()
    {
        $this->resetPage('tickets');
    }

    public function clearFilters()
    {
        $this->selectedStatus = '';
        $this->selectedPriority = null;
        $this->searchTerm = '';
        $this->resetPage('tickets');
    }

    public function loadStaticData()
    {
        if ($this->staticDataLoaded) {
            return;
        }

        $this->staticDataLoaded = true;
    }

    public function gotoPage($page, $pageName = 'tickets')
    {
        $this->setPage($page, $pageName);
        $this->dispatch('pagination-updated');
    }

    public function loadDashboardData()
    {
        $this->ticketsByStatus = TicketStatus::where('project_id', $this->project->id)
            ->withCount([
                'tickets' => function ($query) {
                    $query->where('project_id', $this->project->id);
                },
            ])
            ->orderBy('name')
            ->get()
            ->map(function ($status) {
                return [
                    'status_name' => $status->name,
                    'color' => $status->color ?? '#6B7280',
                    'count' => $status->tickets_count,
                ];
            })
            ->toArray();

        $this->recentTickets = $this->project->tickets()
            ->with(['status', 'priority'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function loadWidgetData()
    {
        $remainingDays = null;
        if ($this->project->end_date) {
            $remainingDays = (int) Carbon::now()->diffInDays(Carbon::parse($this->project->end_date), false);
        }

        $this->projectStats = [
            'total_team' => $this->project->users()->count(),
            'total_tickets' => $this->project->tickets()->count(),
            'remaining_days' => $remainingDays,
            'progress_percentage' => $this->project->progress_percentage,
        ];
    }

    private function truncateName($name, $length = 50): string
    {
        return strlen($name) > $length ? substr($name, 0, $length).'...' : $name;
    }

    private function getSimpleProgress($statusName): int
    {
        if (! $this->project || empty($statusName)) {
            return 0;
        }

        try {
            $statuses = $this->project->ticketStatuses()
                ->orderBy('sort_order')
                ->get();

            if ($statuses->isEmpty()) {
                return 0;
            }

            $currentStatus = $statuses->firstWhere('name', $statusName);

            if (! $currentStatus) {
                return 0;
            }

            $totalStatuses = $statuses->count();
            $currentPosition = $statuses->search(function ($status) use ($currentStatus) {
                return $status->id === $currentStatus->id;
            });

            if ($currentPosition === false) {
                return 0;
            }

            $progress = (($currentPosition + 1) / $totalStatuses) * 100;

            return (int) round(max(0, min(100, $progress)));
        } catch (Exception $e) {
            \Log::error('Error calculating progress: '.$e->getMessage());

            return 0;
        }
    }

    public function switchTab($tabName)
    {
        $this->activeTab = $tabName;

        if ($tabName === 'timeline') {
            $this->dispatch('switch-to-timeline');
        }
    }

    private function determineWeekStatus($deviation): string
    {
        if ($deviation >= 0) {
            return 'ontrack'; // Green - on track or ahead
        } elseif ($deviation >= -10) {
            return 'risk'; // Yellow - risk of delay (within 10% behind)
        } else {
            return 'delay'; // Red - significant delay (more than 10% behind)
        }
    }

    public function exportGanttData()
    {
        try {
            $ganttData = $this->ganttData;

            // Log export activity
            \Log::info('Gantt chart export requested for project: '.$this->project->id);

            // Return the gantt data for frontend processing
            return response()->json([
                'success' => true,
                'data' => $ganttData,
                'project_name' => $this->project->name,
                'export_timestamp' => now()->toISOString(),
            ]);

        } catch (Exception $e) {
            \Log::error('Error exporting gantt data: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to export gantt data',
            ], 500);
        }
    }

    public function render()
    {
        return view('livewire.external-dashboard');
    }

    public function logout()
    {
        Session::forget([
            'external_authenticated_'.$this->token,
            'external_authenticated_at_'.$this->token,
            'external_project_id',
            'external_authenticated',
        ]);

        return redirect()->route('external.login', ['token' => $this->token]);
    }
}
