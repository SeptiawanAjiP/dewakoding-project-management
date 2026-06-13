<div class="space-y-6" x-data @comment-added.window="$wire.$refresh()">
    {{-- Add Comment Form --}}
    @php
        $ticket = $getRecord();
        $project = $ticket->project;
        $canComment = $project->members()->where('users.id', auth()->id())->exists();
        
        // Helper function to convert video img tags to video tags
        function convertVideoImgsToVideoTags($html) {
            // Pattern to match img tags with video file extensions
            $pattern = '/<img\s+[^>]*src=["\']([^"\']*\.(mp4|webm|mov|avi|mkv))["\'][^>]*\/?>/i';
            
            return preg_replace_callback($pattern, function($matches) {
                $videoUrl = $matches[1];
                return '<video controls class="max-w-full rounded-lg my-2" style="max-height: 400px;">
                    <source src="' . $videoUrl . '" type="video/' . pathinfo($videoUrl, PATHINFO_EXTENSION) . '">
                    Your browser does not support the video tag.
                </video>';
            }, $html);
        }
    @endphp

    {{-- Comments List --}}
    @if($getState() && $getState()->count() > 0)
        <div class="space-y-4">
            @foreach($getState() as $comment)
                <div class="py-4 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                    <div class="flex items-start gap-x-4">
                        <div class="shrink-0">
                            <div
                                class="w-8 h-8 rounded-full bg-primary-500 flex items-center justify-center text-white font-medium text-sm">
                                {{ $comment->user ? substr($comment->user->name, 0, 1) : '?' }}
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-2">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $comment->user->name ?? 'Unknown User' }}
                                </div>
                                <div class="flex items-center gap-x-2">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $comment->created_at->diffForHumans() }}
                                    </div>

                                    @if(auth()->user()->hasRole(['super_admin']) || $comment->user_id === auth()->id())
                                        <div class="flex gap-x-1">
                                            <!-- Edit Button -->
                                            <button type="button"
                                                wire:click="mountAction('editComment', { commentId: {{ $comment->id }} })"
                                                class="p-1 text-gray-400 hover:text-blue-500 transition-colors rounded-full hover:bg-gray-100 dark:hover:bg-gray-700"
                                                title="Edit comment">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>

                                            <!-- Delete Button -->
                                            <button type="button"
                                                wire:click="mountAction('deleteComment', { commentId: {{ $comment->id }} })"
                                                class="p-1 text-gray-400 hover:text-red-500 transition-colors rounded-full hover:bg-gray-100 dark:hover:bg-gray-700"
                                                title="Delete comment">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="prose prose-sm dark:prose-invert max-w-none
                                [&_a]:text-primary-600 [&_a]:underline [&_a]:underline-offset-2
                                dark:[&_a]:text-primary-400
                                [&_a:hover]:text-primary-800 dark:[&_a:hover]:text-primary-300">
                                {!! convertVideoImgsToVideoTags($comment->comment) !!}
                            </div>
                            @if($comment->created_at != $comment->updated_at)
                                <div class=" text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    (edited {{ $comment->updated_at->diffForHumans() }})
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-400" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <p class="text-sm">No comments yet. Be the first to comment!</p>
        </div>
    @endif
    @if($canComment)
        @livewire('ticket-comment-form', ['ticket' => $ticket])
    @endif
</div>
