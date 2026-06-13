{{-- resources/views/filament/resources/ticket-resource/status-switcher.blade.php --}}

@php
    $record = $getRecord();
    $statuses = \App\Models\TicketStatus::query()
        ->where('project_id', $record->project_id)
        ->orderBy('sort_order')
        ->get();
    $currentId = $record->ticket_status_id;
    $current = $statuses->firstWhere('id', $currentId);
    $currentColor = $current->color ?? '#6B7280';
    $currentName = $current->name ?? 'Unknown';
    $canChange = $this->canChangeStatus();
@endphp

<div class="ticket-status-switcher">
    <dt class="fi-in-entry-label" style="display: block; margin-bottom: 0.5rem;">Status</dt>

    @if ($canChange && $statuses->isNotEmpty())
        <x-filament::dropdown placement="bottom-start" width="xs" wire:target="updateStatus">
            <x-slot name="trigger">
                <button
                    type="button"
                    wire:loading.attr="disabled"
                    wire:target="updateStatus"
                    class="tss-trigger"
                    style="background-color: {{ $currentColor }};"
                >
                    <span class="tss-dot" style="background-color: rgba(255, 255, 255, .6);"></span>
                    <span>{{ $currentName }}</span>
                    <svg class="tss-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                    </svg>
                </button>
            </x-slot>

            <x-filament::dropdown.list>
                @foreach ($statuses as $status)
                    @php
                        $isActive = $status->id === $currentId;
                        $color = $status->color ?? '#6B7280';
                    @endphp
                    <x-filament::dropdown.list.item
                        wire:click="updateStatus({{ $status->id }})"
                        :disabled="$isActive"
                    >
                        <span class="tss-item">
                            <span class="tss-dot" style="background-color: {{ $color }};"></span>
                            <span class="tss-item-name">{{ $status->name }}</span>
                            @if ($isActive)
                                <svg class="tss-check" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        </span>
                    </x-filament::dropdown.list.item>
                @endforeach
            </x-filament::dropdown.list>
        </x-filament::dropdown>
    @else
        <span class="tss-trigger tss-trigger-static" style="background-color: {{ $currentColor }};">
            <span class="tss-dot" style="background-color: rgba(255, 255, 255, .6);"></span>
            <span>{{ $currentName }}</span>
        </span>
    @endif

    <style>
        .ticket-status-switcher .tss-trigger {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            max-width: 100%;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1.5;
            white-space: nowrap;
            color: #fff;
            cursor: pointer;
            border: none;
            transition: filter .12s ease, transform .12s ease, box-shadow .12s ease;
        }

        .ticket-status-switcher .tss-trigger > span:not(.tss-dot) {
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ticket-status-switcher button.tss-trigger:hover {
            filter: brightness(1.06);
            box-shadow: 0 1px 4px rgba(0, 0, 0, .2);
        }

        .ticket-status-switcher button.tss-trigger:active {
            transform: translateY(1px);
        }

        .ticket-status-switcher button.tss-trigger:disabled {
            cursor: wait;
            opacity: .7;
        }

        .ticket-status-switcher .tss-trigger-static {
            cursor: default;
        }

        .ticket-status-switcher .tss-chevron {
            width: 12px;
            height: 12px;
            flex-shrink: 0;
            opacity: .85;
        }

        .ticket-status-switcher .tss-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .ticket-status-switcher .tss-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
        }

        .ticket-status-switcher .tss-item-name {
            flex: 1;
        }

        .ticket-status-switcher .tss-check {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            color: rgb(107 114 128);
        }
    </style>
</div>
