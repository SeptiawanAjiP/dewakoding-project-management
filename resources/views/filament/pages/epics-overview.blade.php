<x-filament-panels::page>
    <div class="w-full space-y-4">
        <x-filament::section>
            {{ $this->form }}
        </x-filament::section>
        
        <x-filament::section>
            <x-slot name="heading">
                Epics Overview
            </x-slot>
            
            <div class="w-full space-y-3">
                @forelse($epics as $epic)
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <div
                            class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 px-4 py-3 flex justify-between items-center cursor-pointer"
                            wire:click="toggleEpic({{ $epic->id }})"
                        >
                            <div class="flex items-center space-x-4">
                                <div>
                                    <h3 class="text-base font-medium text-gray-900 dark:text-gray-100">{{ $epic->name }}</h3>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 hidden md:block">
                                        {{ $epic->start_date ? $epic->start_date->format('M d, Y') : '-' }} - 
                                        {{ $epic->end_date ? $epic->end_date->format('M d, Y') : '-' }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="bg-gray-200 dark:bg-gray-600 text-gray-900 dark:text-gray-300 text-sm rounded-full px-3 py-1">
                                    {{ $epic->tickets->count() }} tickets
                                </div>
                                <button class="text-gray-400 hover:text-primary-500 focus:outline-none">
                                    @if($this->isExpanded($epic->id))
                                        <x-heroicon-s-chevron-down class="h-5 w-5 text-primary-500 dark:text-primary-400" />
                                    @else
                                        <x-heroicon-s-chevron-right class="h-5 w-5 dark:text-gray-400" />
                                    @endif
                                </button>
                            </div>
                        </div>
                        
                        <!-- Epic Content - Accordion Content -->
                        @if($this->isExpanded($epic->id))
                            <div class="p-4">
                                <!-- Epic Description -->
                                @if($epic->description)
                                    <div class="mb-4">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-300 mb-2">Description</h4>
                                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-md text-sm text-gray-900 dark:text-gray-300">
                                            {!! $epic->description !!}
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- Tickets -->
                                <div class="w-full">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-300">Tickets</h4>
                                        <a href="{{ route('filament.admin.resources.tickets.create', ['epic_id' => $epic->id]) }}" class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300">
                                            <x-heroicon-s-plus class="w-4 h-4 inline-block mr-1" />
                                            Add Ticket
                                        </a>
                                    </div>
                                    
                                    @if($epic->tickets->isEmpty())
                                        <div class="text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 p-4 rounded-md text-center border border-dashed border-gray-300 dark:border-gray-600 w-full">
                                            No tickets found for this epic.
                                        </div>
                                    @else
                                        <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-md w-full">
                                            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                                                <thead class="bg-gray-50 dark:bg-gray-700">
                                                    <tr>
                                                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                                                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ticket</th>
                                                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden sm:table-cell">Assignee</th>
                                                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">Due Date</th>
                                                        <th scope="col" class="relative px-3 py-2">
                                                            <span class="sr-only">Actions</span>
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                    @foreach($epic->tickets as $ticket)
                                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                            <td class="px-3 py-2 whitespace-nowrap text-xs font-medium text-gray-900 dark:text-gray-100">
                                                                {{ $ticket->uuid }}
                                                            </td>
                                                            <td class="px-3 py-2 text-xs text-gray-900 dark:text-gray-100">
                                                                {{ $ticket->name }}
                                                            </td>
                                                            <td class="px-3 py-2 whitespace-nowrap text-xs">
                                                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold 
                                                                    {{ match($ticket->status->name ?? '') {
                                                                        'To Do' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
                                                                        'In Progress' => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
                                                                        'Review' => 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200',
                                                                        'Done' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                                                                        default => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200',
                                                                    } }}">
                                                                    {{ $ticket->status->name ?? 'No Status' }}
                                                                </span>
                                                            </td>
                                                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400 hidden sm:table-cell">
                                                                {{ $ticket->assignee->name ?? 'Unassigned' }}
                                                            </td>
                                                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400 hidden md:table-cell">
                                                                {{ $ticket->due_date ? $ticket->due_date->format('M d, Y') : '-' }}
                                                            </td>
                                                            <td class="px-3 py-2 whitespace-nowrap text-right text-xs font-medium">
                                                                <a href="{{ route('filament.admin.resources.tickets.edit', ['record' => $ticket->id]) }}" class="text-primary-600 dark:text-primary-400 hover:text-primary-900 dark:hover:text-primary-300 mr-2">
                                                                    Edit
                                                                </a>
                                                                <a href="{{ route('filament.admin.resources.tickets.view', ['record' => $ticket->id]) }}" class="text-primary-600 dark:text-primary-400 hover:text-primary-900 dark:hover:text-primary-300">
                                                                    View
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                                
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 text-center">
                        <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-1">No epics found</h3>
                    </div>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>