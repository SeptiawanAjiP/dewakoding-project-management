<x-filament-panels::page>
    @if(! $this->isProjectIndex())
    @php
        $selectedProjectBadges = $this->selectedGlobalProjectBadges();
    @endphp

    <div class="mb-4" x-data="{ open: false }">
        <div class="relative" @click.away="open = false">
            <button
                @click="open = !open"
                class="inline-flex max-w-full items-center gap-2 rounded-lg border-2 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 transition-colors hover:bg-gray-50 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700"
                style="border-color: {{ $selectedProject->color ?? '#D1D5DB' }};"
            >
                @if($selectedProject?->is_pinned)
                    @php
                        $pinColor = $selectedProject->color ?? '#6B7280';
                    @endphp
                    <span
                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full"
                        style="background-color: {{ $pinColor }};"
                        title="Pinned"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16 9V4h1c.55 0 1-.45 1-1s-.45-1-1-1H7c-.55 0-1 .45-1 1s.45 1 1 1h1v5c0 1.66-1.34 3-3 3v2h5.97v7l1 1 1-1v-7H19v-2c-1.66 0-3-1.34-3-3z" />
                        </svg>
                    </span>
                @endif
                @if($selectedProject?->ticket_prefix)
                    @php
                        $color = $selectedProject->color ?? '#6B7280';
                    @endphp
                    <span
                        class="rounded px-2 py-0.5 text-xs font-semibold"
                        style="background-color: {{ $color }}; color: {{ $this->getReadableTextColor($color) }};"
                    >
                        {{ $selectedProject->ticket_prefix }}
                    </span>
                @elseif($this->isGlobalBoard())
                    <span class="rounded bg-primary-100 px-2 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">
                        {{ $this->hasGlobalProjectFilter() ? 'SEL' : 'ALL' }}
                    </span>
                @endif

                <span class="truncate">{{ $this->boardTitle() }}</span>
                @if($this->isGlobalBoard() && $this->hasGlobalProjectFilter())
                    <span class="hidden rounded bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-900/40 dark:text-primary-300 sm:inline">
                        {{ $this->globalProjectFilterCount() }} selected
                    </span>
                @endif
                <svg class="h-4 w-4 flex-shrink-0 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute left-0 top-full z-50 mt-2 max-h-96 w-80 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
                style="display: none;"
            >
                <div class="p-2">
                    <div class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Project
                    </div>

                    <button
                        wire:click="showProjectIndex"
                        @click="open = false"
                        class="mb-1 flex w-full items-center gap-3 rounded-md px-3 py-2 text-left transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                            LIST
                        </span>
                        <div class="min-w-0 flex-1 text-sm font-medium text-gray-900 dark:text-white">
                            Project List
                        </div>
                    </button>

                    <button
                        wire:click="selectAllProjects"
                        @click="open = false"
                        class="mb-1 flex w-full items-center gap-3 rounded-md px-3 py-2 text-left transition-colors hover:bg-gray-50 dark:hover:bg-gray-700 {{ $this->isGlobalBoard() && ! $this->hasGlobalProjectFilter() ? 'bg-gray-50 dark:bg-gray-700' : '' }}"
                    >
                        <span class="rounded bg-primary-100 px-2 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">
                            ALL
                        </span>
                        <div class="min-w-0 flex-1 text-sm font-medium text-gray-900 dark:text-white">
                            All Projects
                        </div>
                        @if($this->isGlobalBoard() && ! $this->hasGlobalProjectFilter())
                            <svg class="h-4 w-4 flex-shrink-0 text-primary-600 dark:text-primary-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        @endif
                    </button>

                    @if($selectedProjectBadges->isNotEmpty())
                        <div class="mb-2 rounded-md bg-primary-50 px-3 py-2 dark:bg-primary-950/30">
                            <div class="text-xs font-medium text-primary-700 dark:text-primary-300">
                                Selected Projects
                            </div>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach($selectedProjectBadges as $projectBadge)
                                    <span
                                        class="rounded px-1.5 py-0.5 text-[10px] font-semibold"
                                        title="{{ $projectBadge['name'] }}"
                                        style="background-color: {{ $projectBadge['color'] }}; color: {{ $projectBadge['text_color'] }};"
                                    >
                                        {{ $projectBadge['code'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="mb-2 border-t border-gray-200 pt-2 dark:border-gray-700">
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="searchProject"
                                placeholder="Search projects..."
                                class="block w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm text-gray-900 placeholder-gray-400 focus:border-transparent focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                            />
                        </div>
                    </div>

                    @if($projects->isEmpty())
                        <div class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            No projects available
                        </div>
                    @elseif($this->filteredProjects->isEmpty())
                        <div class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            No projects found
                        </div>
                    @else
                        @foreach($this->filteredProjects as $project)
                            <button
                                wire:click="selectProject({{ $project->id }})"
                                @click="open = false"
                                class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left transition-colors hover:bg-gray-50 dark:hover:bg-gray-700 {{ $selectedProject && $project->id === $selectedProject->id ? 'bg-gray-50 dark:bg-gray-700' : '' }}"
                            >
                                @if($project->is_pinned)
                                    <div
                                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full"
                                        style="background-color: {{ $project->color ?? '#6B7280' }};"
                                        title="Pinned"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M16 9V4h1c.55 0 1-.45 1-1s-.45-1-1-1H7c-.55 0-1 .45-1 1s.45 1 1 1h1v5c0 1.66-1.34 3-3 3v2h5.97v7l1 1 1-1v-7H19v-2c-1.66 0-3-1.34-3-3z" />
                                        </svg>
                                    </div>
                                @endif
                                @if($project->ticket_prefix)
                                    @php
                                        $color = $project->color ?? '#6B7280';
                                    @endphp
                                    <span
                                        class="rounded px-2 py-0.5 text-xs font-semibold"
                                        style="background-color: {{ $color }}; color: {{ $this->getReadableTextColor($color) }};"
                                    >
                                        {{ $project->ticket_prefix }}
                                    </span>
                                @endif
                                <div class="min-w-0 flex-1 truncate text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $project->name }}
                                </div>
                                @if($selectedProject && $project->id === $selectedProject->id)
                                    <svg class="h-4 w-4 flex-shrink-0" style="color: {{ $project->color ?? '#3B82F6' }};" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </button>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        @if($selectedProjectBadges->isNotEmpty())
            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    Selected:
                </span>
                @foreach($selectedProjectBadges as $projectBadge)
                    <span
                        class="rounded px-1.5 py-0.5 text-[10px] font-semibold"
                        title="{{ $projectBadge['name'] }}"
                        style="background-color: {{ $projectBadge['color'] }}; color: {{ $projectBadge['text_color'] }};"
                    >
                        {{ $projectBadge['code'] }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>
    @endif

    @if($this->isProjectIndex())
        <div class="space-y-4">
            <div class="max-w-md">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="searchProject"
                        placeholder="Search projects..."
                        class="block w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm text-gray-900 placeholder-gray-400 focus:border-transparent focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                    />
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                <button
                    type="button"
                    wire:click="selectAllProjects"
                    class="flex min-h-24 items-center gap-3 rounded-lg border border-primary-200 bg-white p-4 text-left transition-colors hover:border-primary-400 hover:bg-primary-50 dark:border-primary-800 dark:bg-gray-900 dark:hover:bg-primary-950/30"
                >
                    <span class="rounded bg-primary-100 px-2 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">
                        ALL
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                            All Projects
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $projects->count() }} projects
                        </div>
                    </div>
                </button>

                @if($projects->isEmpty())
                    <div class="rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        No projects available
                    </div>
                @elseif($this->filteredProjects->isEmpty())
                    <div class="rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        No projects found
                    </div>
                @else
                    @foreach($this->filteredProjects as $project)
                        @php
                            $color = $project->color ?? '#6B7280';
                        @endphp
                        <button
                            type="button"
                            wire:click="selectProject({{ $project->id }})"
                            class="relative flex min-h-24 items-center gap-3 rounded-lg border border-gray-200 bg-white p-4 pr-10 text-left transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800"
                            style="border-left-width: 4px; border-left-color: {{ $color }};"
                        >
                            @if($project->is_pinned)
                                <div class="absolute right-2 top-2">
                                    <div
                                        class="flex h-6 w-6 items-center justify-center rounded-full shadow-sm"
                                        style="background-color: {{ $color }};"
                                        title="Pinned Project"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M16 9V4h1c.55 0 1-.45 1-1s-.45-1-1-1H7c-.55 0-1 .45-1 1s.45 1 1 1h1v5c0 1.66-1.34 3-3 3v2h5.97v7l1 1 1-1v-7H19v-2c-1.66 0-3-1.34-3-3z" />
                                        </svg>
                                    </div>
                                </div>
                            @endif
                            @if($project->ticket_prefix)
                                <span
                                    class="rounded px-2 py-0.5 text-xs font-semibold"
                                    style="background-color: {{ $color }}; color: {{ $this->getReadableTextColor($color) }};"
                                >
                                    {{ $project->ticket_prefix }}
                                </span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $project->name }}
                                </div>
                            </div>
                        </button>
                    @endforeach
                @endif
            </div>
        </div>
    @elseif($selectedProject || $this->isGlobalBoard())
        <div
            x-data="{
                draggingTicket: null,
                draggingProjectId: null,
                isTouchDevice: false,
                touchStartX: 0,
                touchStartY: 0,
                scrollStartX: 0,
                columnScrollPositions: {},
                canMoveTickets: {{ $this->canMoveTickets() ? 'true' : 'false' }},
                livewireComponentId: @js($this->getId()),
                visibilityListenersAttached: false,

                moveTicketToStatus(ticketId, statusId) {
                    const wire = window.Livewire?.find(this.livewireComponentId);

                    if (!wire || typeof wire.$call !== 'function') {
                        console.warn('Project Board Livewire component is not available for drag and drop.');
                        return;
                    }

                    wire.$call('moveTicket', parseInt(ticketId, 10), statusId);
                },

                isTicketMovable(ticket) {
                    return this.canMoveTickets && ticket?.dataset.canMove === 'true';
                },

                saveScrollPositions() {
                    const columns = this.$el.querySelectorAll('.status-column .overflow-y-auto');
                    columns.forEach((column, index) => {
                        this.columnScrollPositions[index] = column.scrollTop;
                    });
                },

                restoreScrollPositions() {
                    const columns = this.$el.querySelectorAll('.status-column .overflow-y-auto');
                    columns.forEach((column, index) => {
                        if (this.columnScrollPositions[index] !== undefined) {
                            column.scrollTop = this.columnScrollPositions[index];
                        }
                    });
                },

                isColumnAllowedForDraggedTicket(column) {
                    if (!this.draggingProjectId) return true;

                    const projectIds = column.getAttribute('data-project-ids');
                    if (!projectIds) return true;

                    return projectIds.split(',').includes(this.draggingProjectId);
                },

                updateColumnAvailability() {
                    this.$el.querySelectorAll('.status-column').forEach(column => {
                        if (this.isColumnAllowedForDraggedTicket(column)) {
                            column.classList.remove('hidden');
                            column.removeAttribute('title');
                        } else {
                            column.classList.add('hidden');
                            column.setAttribute('title', 'This ticket project does not have this status');
                        }
                    });
                },

                resetColumnAvailability() {
                    this.$el.querySelectorAll('.status-column').forEach(column => {
                        column.classList.remove('hidden', 'bg-primary-50', 'dark:bg-primary-950');
                        column.removeAttribute('title');
                    });
                },

                init() {
                    this.$nextTick(() => {
                        this.setupTouchScrolling();
                        this.isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
                        this.setupPageVisibilityListener();
                        if (this.canMoveTickets) {
                            this.removeAllEventListeners();
                            this.attachAllEventListeners();
                        }
                    });
                },

                setupPageVisibilityListener() {
                    if (this.visibilityListenersAttached) return;

                    this.visibilityListenersAttached = true;

                    document.addEventListener('visibilitychange', () => {
                        if (!document.hidden) {
                            this.saveScrollPositions();
                            setTimeout(() => {
                                if (this.canMoveTickets) {
                                    this.removeAllEventListeners();
                                    this.attachAllEventListeners();
                                }
                                this.restoreScrollPositions();
                            }, 100);
                        }
                    });

                    window.addEventListener('focus', () => {
                        this.saveScrollPositions();
                        setTimeout(() => {
                            if (this.canMoveTickets) {
                                this.removeAllEventListeners();
                                this.attachAllEventListeners();
                            }
                            this.restoreScrollPositions();
                        }, 100);
                    });

                    window.addEventListener('popstate', () => {
                        this.saveScrollPositions();
                        setTimeout(() => {
                            if (this.canMoveTickets) {
                                this.removeAllEventListeners();
                                this.attachAllEventListeners();
                            }
                            this.restoreScrollPositions();
                        }, 200);
                    });

                    document.addEventListener('livewire:navigated', () => {
                        this.saveScrollPositions();
                        setTimeout(() => {
                            if (this.canMoveTickets) {
                                this.removeAllEventListeners();
                                this.attachAllEventListeners();
                            }
                            this.restoreScrollPositions();
                        }, 300);
                    });

                    document.addEventListener('livewire:load', () => {
                        this.saveScrollPositions();
                        setTimeout(() => {
                            if (this.canMoveTickets) {
                                this.removeAllEventListeners();
                                this.attachAllEventListeners();
                            }
                            this.restoreScrollPositions();
                        }, 100);
                    });

                    document.addEventListener('livewire:updated', () => {
                        this.saveScrollPositions();
                        setTimeout(() => {
                            if (this.canMoveTickets) {
                                this.removeAllEventListeners();
                                this.attachAllEventListeners();
                            }
                            this.restoreScrollPositions();
                        }, 100);
                    });

                    window.addEventListener('ticket-updated', () => {
                        this.saveScrollPositions();
                        setTimeout(() => {
                            if (this.canMoveTickets) {
                                this.removeAllEventListeners();
                                this.attachAllEventListeners();
                            }
                            this.restoreScrollPositions();
                        }, 150);
                    });

                    setInterval(() => {
                        if (document.visibilityState === 'visible') {
                            this.saveScrollPositions();
                            this.ensureDragDropInitialized();
                            this.restoreScrollPositions();
                        }
                    }, 2000);
                },

                ensureDragDropInitialized() {
                    if (!this.canMoveTickets) return;

                    const tickets = this.$el.querySelectorAll('.ticket-card');
                    let needsReinitialization = false;

                    tickets.forEach(ticket => {
                        if (this.isTicketMovable(ticket) && ticket.dataset.dragBound !== 'true') {
                            needsReinitialization = true;
                        }
                    });

                    if (needsReinitialization && tickets.length > 0) {
                        this.attachAllEventListeners();
                    }
                },

                setupTouchScrolling() {
                    const container = this.$el;

                    if (container.dataset.touchScrollingBound === 'true') return;

                    container.dataset.touchScrollingBound = 'true';

                    container.addEventListener('touchstart', (e) => {
                        this.touchStartX = e.touches[0].clientX;
                        this.touchStartY = e.touches[0].clientY;
                        this.scrollStartX = container.scrollLeft;
                    }, { passive: true });

                    container.addEventListener('touchmove', (e) => {
                        if (e.touches.length !== 1) return;

                        const touchX = e.touches[0].clientX;
                        const touchY = e.touches[0].clientY;
                        const moveX = this.touchStartX - touchX;
                        const moveY = this.touchStartY - touchY;

                        if (Math.abs(moveX) > Math.abs(moveY)) {
                            e.preventDefault();
                            container.scrollLeft = this.scrollStartX + moveX;
                        }
                    }, { passive: false });
                },

                removeAllEventListeners() {
                    this.resetColumnAvailability();
                },

                attachAllEventListeners() {
                    if (!this.canMoveTickets) return;

                    const tickets = this.$el.querySelectorAll('.ticket-card');
                    tickets.forEach(ticket => {
                        if (!this.isTicketMovable(ticket)) {
                            ticket.setAttribute('draggable', false);

                            return;
                        }

                        if (ticket.dataset.dragBound === 'true') return;

                        ticket.dataset.dragBound = 'true';
                        ticket.setAttribute('draggable', true);

                        ticket.addEventListener('dragstart', (e) => {
                            if (!this.isTicketMovable(ticket)) {
                                e.preventDefault();

                                return;
                            }

                            this.draggingTicket = ticket.getAttribute('data-ticket-id');
                            this.draggingProjectId = ticket.getAttribute('data-ticket-project-id');
                            ticket.classList.add('opacity-50');
                            this.updateColumnAvailability();
                            e.dataTransfer.effectAllowed = 'move';
                        });

                        ticket.addEventListener('dragend', () => {
                            ticket.classList.remove('opacity-50');
                            this.draggingTicket = null;
                            this.draggingProjectId = null;
                            this.resetColumnAvailability();
                        });

                        let longPressTimer;
                        let isDragging = false;
                        let originalColumn;

                        ticket.addEventListener('touchstart', (e) => {
                            if (isDragging || !this.isTicketMovable(ticket)) return;

                            longPressTimer = setTimeout(() => {
                                if (!this.isTicketMovable(ticket)) return;

                                originalColumn = ticket.closest('.status-column');
                                this.draggingTicket = ticket.getAttribute('data-ticket-id');
                                this.draggingProjectId = ticket.getAttribute('data-ticket-project-id');
                                ticket.classList.add('opacity-50', 'relative', 'z-30');
                                isDragging = true;
                                this.updateColumnAvailability();
                                ticket.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
                            }, 500);
                        }, { passive: true });

                        ticket.addEventListener('touchmove', (e) => {
                            if (!isDragging) {
                                clearTimeout(longPressTimer);
                                return;
                            }

                            const touch = e.touches[0];
                            const columns = this.$el.querySelectorAll('.status-column');

                            columns.forEach(column => {
                                const rect = column.getBoundingClientRect();
                                if (touch.clientX >= rect.left &&
                                    touch.clientX <= rect.right &&
                                    touch.clientY >= rect.top &&
                                    touch.clientY <= rect.bottom &&
                                    this.isColumnAllowedForDraggedTicket(column)) {
                                    column.classList.add('bg-primary-50', 'dark:bg-primary-950');
                                } else {
                                    column.classList.remove('bg-primary-50', 'dark:bg-primary-950');
                                }
                            });
                        });

                        ticket.addEventListener('touchend', (e) => {
                            clearTimeout(longPressTimer);

                            if (!isDragging) return;

                            isDragging = false;
                            ticket.classList.remove('opacity-50', 'relative', 'z-30');
                            ticket.style.boxShadow = '';

                            const touch = e.changedTouches[0];
                            const columns = this.$el.querySelectorAll('.status-column');

                            let targetColumn = null;
                            columns.forEach(column => {
                                const rect = column.getBoundingClientRect();
                                if (touch.clientX >= rect.left &&
                                    touch.clientX <= rect.right &&
                                    touch.clientY >= rect.top &&
                                    touch.clientY <= rect.bottom) {
                                    targetColumn = column;
                                }
                                column.classList.remove('bg-primary-50', 'dark:bg-primary-950');
                            });

                            if (targetColumn && targetColumn !== originalColumn && this.isColumnAllowedForDraggedTicket(targetColumn)) {
                                const statusId = targetColumn.getAttribute('data-status-id');
                                const ticketId = this.draggingTicket;

                                this.moveTicketToStatus(ticketId, statusId);
                            }

                            this.draggingTicket = null;
                            this.draggingProjectId = null;
                            this.resetColumnAvailability();
                        });

                        ticket.addEventListener('touchcancel', () => {
                            clearTimeout(longPressTimer);
                            if (!isDragging) return;

                            isDragging = false;
                            ticket.classList.remove('opacity-50', 'relative', 'z-30');
                            ticket.style.boxShadow = '';
                            this.draggingTicket = null;
                            this.draggingProjectId = null;
                            this.resetColumnAvailability();

                            this.$el.querySelectorAll('.status-column').forEach(column => {
                                column.classList.remove('bg-primary-50', 'dark:bg-primary-950');
                            });
                        });
                    });

                    const columns = this.$el.querySelectorAll('.status-column');
                    columns.forEach(column => {
                        if (column.dataset.dropBound === 'true') return;

                        column.dataset.dropBound = 'true';

                        column.addEventListener('dragover', (e) => {
                            if (!this.isColumnAllowedForDraggedTicket(column)) {
                                e.dataTransfer.dropEffect = 'none';
                                column.classList.remove('bg-primary-50', 'dark:bg-primary-950');
                                return;
                            }

                            e.preventDefault();
                            e.dataTransfer.dropEffect = 'move';
                            column.classList.add('bg-primary-50', 'dark:bg-primary-950');
                        });

                        column.addEventListener('dragleave', () => {
                            column.classList.remove('bg-primary-50', 'dark:bg-primary-950');
                        });

                        column.addEventListener('drop', (e) => {
                            e.preventDefault();
                            column.classList.remove('bg-primary-50', 'dark:bg-primary-950');

                            if (this.draggingTicket && this.isColumnAllowedForDraggedTicket(column)) {
                                const statusId = column.getAttribute('data-status-id');
                                const ticketId = this.draggingTicket;
                                this.draggingTicket = null;
                                this.draggingProjectId = null;
                                this.resetColumnAvailability();
                                this.moveTicketToStatus(ticketId, statusId);
                            }
                        });
                    });
                }
            }"
            x-init="init()"
            @ticket-moved.window="init()"
            @ticket-updated.window="init()"
            @refresh-board.window="init()"
            wire:key="board-container-{{ $selectedProject?->id ?? 'all-projects' }}"
            class="relative overflow-x-auto pb-6 {{ !$this->canMoveTickets() ? 'view-only-mode' : '' }}"
            id="board-container"
        >
            {{-- Mobile swipe hint --}}
            <div class="md:hidden flex justify-center mb-2 text-xs text-gray-500 dark:text-gray-400 items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Swipe horizontally to view all columns</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </div>

            {{-- View Only Mode Indicator --}}
            @if(!$this->canMoveTickets())
                <div class="flex justify-center mb-4">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-amber-800 dark:text-amber-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span class="text-sm font-medium">View Only Mode</span>
                        <span class="text-xs opacity-75">You can view tickets but cannot move them</span>
                    </div>
                </div>
            @endif

            <div class="inline-flex gap-4 pb-2 min-w-full">
                @foreach ($this->ticketStatuses as $status)
                    <div
                        wire:key="status-column-{{ $status->id }}"
                        class="status-column rounded-xl border border-gray-200 dark:border-gray-700 flex flex-col bg-gray-50 dark:bg-gray-900 w-[calc(85vw-2rem)] min-w-[280px] max-w-[350px] h-[700px] sm:w-[calc((100vw-6rem)/2)] sm:h-[750px] lg:w-[calc((100vw-8rem)/3)] lg:h-[800px] xl:w-[calc((100vw-10rem)/4)] xl:h-[850px]"
                        data-status-id="{{ $status->id }}"
                        @if($this->isGlobalBoard() && isset($status->project_ids))
                            data-project-ids="{{ $status->project_ids->implode(',') }}"
                        @endif
                    >
                        <div
                            class="px-4 py-3 rounded-t-xl border-b border-gray-200 dark:border-gray-700 flex-shrink-0"
                            style="background-color: {{ $status->color ?? '#f3f4f6' }};"
                        >
                            <div class="flex items-center justify-between">
                                <h3 class="font-medium flex items-center gap-2" style="color: white; text-shadow: 0px 0px 1px rgba(0,0,0,0.5);">
                                    <span>{{ $status->name }}</span>
                                    <span class="text-sm opacity-80">{{ $status->tickets_count ?? $status->tickets->count() }}</span>
                                    @if($status->is_completed)
                                        <div class="flex items-center justify-center w-6 h-6 bg-green-500 rounded-full border-2 border-white shadow-lg" title="Completed Status">
                                            <svg class="w-3 h-3 text-white font-bold" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </h3>

                                <!-- Sort Menu Dropdown -->
                                <div class="relative" x-data="{ open: false }">
                                    <button
                                        @click="open = !open"
                                        @click.away="open = false"
                                        class="p-1 rounded hover:bg-black hover:bg-opacity-20 transition-colors"
                                        style="color: white;"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                        </svg>
                                    </button>

                                    <div
                                        x-show="open"
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="transform opacity-0 scale-95"
                                        x-transition:enter-end="transform opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="transform opacity-100 scale-100"
                                        x-transition:leave-end="transform opacity-0 scale-95"
                                        class="absolute top-8 left-0 w-52 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50"
                                        style="display: none; transform: translateX(-100%);"
                                    >
                                        <div class="p-2">
                                            <div class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">Sort list</span>
                                                <button @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>

                                            <div class="py-1">
                                                <button
                                                    wire:click="setSortOrder('{{ $status->id }}', 'date_created_newest')"
                                                    @click="open = false"
                                                    class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-white rounded"
                                                >
                                                    Date created (newest first)
                                                </button>
                                                <button
                                                    wire:click="setSortOrder('{{ $status->id }}', 'date_created_oldest')"
                                                    @click="open = false"
                                                    class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-white rounded"
                                                >
                                                    Date created (oldest first)
                                                </button>
                                                <button
                                                    wire:click="setSortOrder('{{ $status->id }}', 'card_name_alphabetical')"
                                                    @click="open = false"
                                                    class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-white rounded"
                                                >
                                                    Card name (alphabetically)
                                                </button>
                                                <button
                                                    wire:click="setSortOrder('{{ $status->id }}', 'due_date')"
                                                    @click="open = false"
                                                    class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-white rounded"
                                                >
                                                    Due date
                                                </button>
                                                <button
                                                    wire:click="setSortOrder('{{ $status->id }}', 'priority')"
                                                    @click="open = false"
                                                    class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-white rounded"
                                                >
                                                    Priority
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if($this->isGlobalBoard() && isset($status->project_badges))
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach($status->project_badges->take(6) as $projectBadge)
                                        <span
                                            class="rounded px-1.5 py-0.5 text-[10px] font-semibold shadow-sm"
                                            style="background-color: {{ $projectBadge['color'] }}; color: {{ $projectBadge['text_color'] }};"
                                        >
                                            {{ $projectBadge['code'] }}
                                        </span>
                                    @endforeach
                                    @if($status->project_badges->count() > 6)
                                        <span class="rounded bg-white/20 px-1.5 py-0.5 text-[10px] font-semibold text-white">
                                            +{{ $status->project_badges->count() - 6 }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="p-3 flex flex-col gap-3 flex-1 overflow-y-auto" style="max-height: calc(100% - 60px);">
                            @foreach ($status->tickets as $ticket)
                                @php
                                    $canMoveTicket = $this->canMoveTicket($ticket);
                                @endphp
                                <div
                                    wire:key="ticket-{{ $status->id }}-{{ $ticket->id }}"
                                    class="ticket-card bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 {{ $canMoveTicket ? 'cursor-move' : 'cursor-default' }}"
                                    data-ticket-id="{{ $ticket->id }}"
                                    data-ticket-project-id="{{ $ticket->project_id }}"
                                    data-can-move="{{ $canMoveTicket ? 'true' : 'false' }}"
                                    draggable="{{ $canMoveTicket ? 'true' : 'false' }}"
                                >
                                    <div class="flex justify-between items-start gap-2 mb-2">
                                        <div class="flex min-w-0 flex-wrap items-center gap-1">
                                            @if($this->isGlobalBoard() && $ticket->project)
                                                @php
                                                    $projectColor = $ticket->project->color ?? '#6B7280';
                                                @endphp
                                                <span
                                                    class="max-w-[120px] truncate rounded px-1.5 py-0.5 text-xs font-semibold"
                                                    style="background-color: {{ $projectColor }}; color: {{ $this->getReadableTextColor($projectColor) }};"
                                                >
                                                    {{ $this->projectCode($ticket->project) }}
                                                </span>
                                            @endif
                                            <span class="max-w-[130px] truncate rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-400 sm:max-w-none">
                                                {{ $ticket->uuid }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            @if ($ticket->priority)
                                                <span class="text-xs px-1.5 py-0.5 rounded whitespace-nowrap text-white font-medium" style="background-color: {{ $ticket->priority->color }};">
                                                    {{ $ticket->priority->name }}
                                                </span>
                                            @endif
                                            @if ($ticket->due_date)
                                                <span class="text-xs px-1.5 py-0.5 rounded whitespace-nowrap {{ $ticket->due_date->isPast() ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' }}">
                                                    {{ $ticket->due_date->format('M d') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">{{ $ticket->name }}</h4>

                                    @if ($ticket->description)
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3 line-clamp-2">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($ticket->description), 100) }}
                                        </p>
                                    @endif

                                    <div class="flex justify-between items-center mt-2">
                                       @if ($ticket->assignees->isNotEmpty())
                                            <div class="flex flex-wrap gap-1 max-w-[180px]">
                                                @foreach($ticket->assignees->take(2) as $assignee)
                                                    <div class="inline-flex items-center px-2 py-1 rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 gap-1">
                                                        <span class="w-4 h-4 rounded-full bg-primary-500 flex items-center justify-center text-xs text-white flex-shrink-0">
                                                            {{ substr($assignee->name, 0, 1) }}
                                                        </span>
                                                        <span class="text-xs font-medium truncate">{{ \Illuminate\Support\Str::limit($assignee->name, 8) }}</span>
                                                    </div>
                                                @endforeach
                                                @if($ticket->assignees->count() > 2)
                                                    <div class="inline-flex items-center px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-400">
                                                        <span class="text-xs font-medium">+{{ $ticket->assignees->count() - 2 }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="inline-flex items-center px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 dark:text-gray-500 mr-1 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                                </svg>
                                                <span class="text-xs font-medium">Unassigned</span>
                                            </div>
                                        @endif

                                        <a
                                            href="{{ $this->ticketViewUrl($ticket->id) }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            onclick="
                                                event.preventDefault();
                                                const container = this.closest('.overflow-y-auto');
                                                const scrollPos = container.scrollTop;
                                                window.open(this.href, '_blank');
                                                setTimeout(() => {
                                                    container.scrollTop = scrollPos;
                                                }, 0);
                                                return false;
                                            "
                                            class="inline-flex items-center justify-center w-8 h-8 text-sm font-medium rounded-lg border border-gray-200 dark:border-gray-700 text-primary-600 hover:text-primary-500 dark:text-primary-500 dark:hover:text-primary-400 flex-shrink-0"
                                        >
                                            <x-heroicon-m-eye class="w-4 h-4" />
                                        </a>
                                    </div>
                                </div>
                            @endforeach

                            @php
                                $loadedTickets = $status->tickets->count();
                                $totalTickets = $status->tickets_count ?? $loadedTickets;
                            @endphp

                            @if ($loadedTickets === 0)
                                <div class="flex items-center justify-center h-24 text-gray-500 dark:text-gray-400 text-sm italic border border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
                                    No tickets
                                </div>
                            @elseif($loadedTickets < $totalTickets)
                                <button
                                    type="button"
                                    wire:click="loadMoreTickets('{{ $status->id }}')"
                                    class="flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                >
                                    Load more
                                    <span class="ml-2 text-xs font-normal text-gray-500 dark:text-gray-400">
                                        {{ $loadedTickets }} / {{ $totalTickets }}
                                    </span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach

                @if ($this->ticketStatuses->isEmpty())
                    <div class="w-full flex items-center justify-center h-40 text-gray-500 dark:text-gray-400">
                        {{ $this->isGlobalBoard() ? 'No tickets found' : 'No status columns found for this project' }}
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-filament-panels::page>
