{{-- resources/views/filament/resources/ticket-resource/timeline-history.blade.php --}}

<div class="timeline-history">
    <style>
        .timeline-history .vertical-line {
            position: absolute;
            left: 0;
            top: 5px;
            bottom: 5px;
            width: 2px;
            background-color: #94a3b8;
        }
        
        .timeline-history .timeline-item {
            position: relative;
            padding-left: 25px;
            padding-bottom: 1.25rem;
        }
        
        .timeline-history .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-history .timeline-dot {
            position: absolute;
            left: -9px;
            top: 5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.2);
            box-shadow: 0 0 0 2px rgba(0,0,0,0.1);
        }

        .timeline-history .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 2px 10px 2px 6px;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            line-height: 1.5;
        }

        .timeline-history .status-badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }
    </style>

    @php
        $histories = $getRecord()->histories()->with(['user', 'status'])->orderBy('created_at', 'desc')->get();
    @endphp
    
    <div class="relative">
        {{-- Vertical line --}}
        <div class="vertical-line"></div>
        
        {{-- Timeline items --}}
        <div class="space-y-5">
            @foreach($histories as $history)
                @php
                    $color = $history->status->color ?? '#94a3b8';
                    // Convert hex to RGB for background with opacity
                    $hex = ltrim($color, '#');
                    $r = hexdec(substr($hex, 0, 2));
                    $g = hexdec(substr($hex, 2, 2));
                    $b = hexdec(substr($hex, 4, 2));
                @endphp
                <div class="timeline-item">
                    {{-- Dot marker with status color --}}
                    <div class="timeline-dot" style="background-color: {{ $color }};"></div>
                    
                    {{-- Content --}}
                    <div>
                        <div>
                            {{-- Status badge with color --}}
                            <span class="status-badge"
                                style="background-color: {{ $color }}; color: #ffffff;">
                                <span class="status-badge-dot" style="background-color: rgba(255,255,255,0.5);"></span>
                                {{ $history->status->name }}
                            </span>
                        </div>
                        
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 flex items-center gap-x-1">
                            <span>Updated by: {{ $history->user->name ?? 'System' }}</span>
                            <span class="text-gray-400 dark:text-gray-500 mx-1">•</span>
                            <span>{{ $history->created_at->format('d M H:i') }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
