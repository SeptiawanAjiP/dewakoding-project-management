<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Pages\ProjectBoard;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use App\Models\TicketComment;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    public ?int $editingCommentId = null;

    public function editCommentAction(): Action
    {
        return Action::make('editComment')
            ->form([
                Hidden::make('comment_id'),
                RichEditor::make('comment')
                    ->label('Edit Comment')
                    ->required()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('attachments')
                    ->fileAttachmentsVisibility('public')
                    ->extraInputAttributes(['style' => 'min-height: 10rem;']),
            ])
            ->fillForm(function (array $arguments): array {
                $comment = TicketComment::find($arguments['commentId']);

                if (! $comment) {
                    return [];
                }

                return [
                    'comment_id' => $comment->id,
                    'comment' => $comment->comment,
                ];
            })
            ->action(function (array $data): void {
                $comment = TicketComment::find($data['comment_id']);

                if (! $comment) {
                    Notification::make()
                        ->title('Comment not found')
                        ->danger()
                        ->send();

                    return;
                }

                if ($comment->user_id !== auth()->id() && ! auth()->user()->hasRole(['super_admin'])) {
                    Notification::make()
                        ->title('You do not have permission to edit this comment')
                        ->danger()
                        ->send();

                    return;
                }

                $comment->update([
                    'comment' => $data['comment'],
                ]);

                Notification::make()
                    ->title('Comment updated successfully')
                    ->success()
                    ->send();

                $this->dispatch('comment-updated');
            })
            ->modalHeading('Edit Comment')
            ->modalSubmitActionLabel('Update')
            ->modalWidth('2xl');
    }

    public function deleteCommentAction(): Action
    {
        return Action::make('deleteComment')
            ->requiresConfirmation()
            ->modalHeading('Delete Comment')
            ->modalDescription('Are you sure you want to delete this comment? This action cannot be undone.')
            ->modalSubmitActionLabel('Yes, delete it')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->action(function (array $arguments): void {
                $comment = TicketComment::find($arguments['commentId']);

                if (! $comment) {
                    Notification::make()
                        ->title('Comment not found')
                        ->danger()
                        ->send();

                    return;
                }

                if ($comment->user_id !== auth()->id() && ! auth()->user()->hasRole(['super_admin'])) {
                    Notification::make()
                        ->title('You do not have permission to delete this comment')
                        ->danger()
                        ->send();

                    return;
                }

                $comment->delete();

                Notification::make()
                    ->title('Comment deleted successfully')
                    ->success()
                    ->send();

                $this->dispatch('comment-deleted');
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(function () {
                    $ticket = $this->getRecord();

                    return auth()->user()->hasRole(['super_admin'])
                        || $ticket->created_by === auth()->id()
                        || $ticket->assignees()->where('users.id', auth()->id())->exists();
                }),

            Action::make('back')
                ->label('Back to Board')
                ->color('gray')
                ->url(fn () => ProjectBoard::getUrl(['project_id' => $this->record->project_id])),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Ticket Information')
                    ->icon('heroicon-o-ticket')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2, 'lg' => 3])
                            ->schema([
                                TextEntry::make('uuid')
                                    ->label('Ticket ID')
                                    ->copyable()
                                    ->icon('heroicon-o-hashtag'),

                                TextEntry::make('name')
                                    ->label('Ticket Name')
                                    ->icon('heroicon-o-document-text')
                                    ->weight('bold'),

                                TextEntry::make('project.name')
                                    ->label('Project')
                                    ->icon('heroicon-o-folder'),
                            ]),
                    ]),

                Section::make('Status & Assignment')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2, 'lg' => 4])
                            ->schema([
                                TextEntry::make('status.name')
                                    ->label('Status')
                                    ->formatStateUsing(function ($record) {
                                        $color = e($record->status?->color ?? '#6B7280');
                                        $name = e($record->status?->name ?? 'Unknown');

                                        return new HtmlString(<<<HTML
                                        <span class="fi-badge fi-size-sm" style="color: #fff; background-color: {$color};">
                                            {$name}
                                        </span>
                                    HTML);
                                    }),

                                TextEntry::make('assignees.name')
                                    ->label('Assigned To')
                                    ->badge()
                                    ->separator(',')
                                    ->default('Unassigned')
                                    ->color('info'),

                                TextEntry::make('creator.name')
                                    ->label('Created By')
                                    ->default('Unknown')
                                    ->icon('heroicon-o-user'),

                                TextEntry::make('due_date')
                                    ->label('Due Date')
                                    ->date('d M Y')
                                    ->icon('heroicon-o-calendar')
                                    ->color(fn ($record) => $record->due_date && $record->due_date->isPast() ? 'danger' : 'success'),
                            ]),
                    ]),

                Section::make('Description')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextEntry::make('description')
                            ->hiddenLabel()
                            ->html()
                            ->prose()
                            ->columnSpanFull()
                            ->placeholder('No description provided'),
                    ])
                    ->columnSpanFull(),

                Section::make('Comments')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->description('Discussion about this ticket')
                    ->schema([
                        TextEntry::make('comments_list')
                            ->hiddenLabel()
                            ->state(function (Ticket $record) {
                                if (method_exists($record, 'comments')) {
                                    return $record->comments()->with('user')->oldest()->get();
                                }

                                return collect();
                            })
                            ->view('filament.resources.ticket-resource.comments-section')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),

                Grid::make(['default' => 1, 'lg' => 2])
                    ->schema([
                        Section::make('Metadata')
                            ->icon('heroicon-o-information-circle')
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime('d M Y H:i')
                                    ->icon('heroicon-o-clock'),

                                TextEntry::make('updated_at')
                                    ->label('Updated At')
                                    ->dateTime('d M Y H:i')
                                    ->icon('heroicon-o-arrow-path'),

                                TextEntry::make('epic.name')
                                    ->label('Epic')
                                    ->default('No Epic')
                                    ->badge()
                                    ->color('warning')
                                    ->icon('heroicon-o-flag'),
                            ]),

                        Section::make('Status History')
                            ->icon('heroicon-o-clock')
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                TextEntry::make('histories')
                                    ->hiddenLabel()
                                    ->view('filament.resources.ticket-resource.timeline-history')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
