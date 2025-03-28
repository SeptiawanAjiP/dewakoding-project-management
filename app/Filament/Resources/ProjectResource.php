<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('description')
                        ->columnSpanFull(),
                ]),
                Forms\Components\Section::make([
                    Forms\Components\TextInput::make('ticket_prefix')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Toggle::make('create_default_statuses')
                        ->label('Use Default Ticket Statuses')
                        ->helperText('Create standard To Do, In Progress, Review, and Done statuses automatically')
                        ->default(true)
                        ->dehydrated(false)
                        ->visible(fn($livewire) => $livewire instanceof Pages\CreateProject)
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ticket_prefix')
                    ->searchable(),
                Tables\Columns\TextColumn::make('members_count')
                    ->counts('members')
                    ->label('Members'),
                Tables\Columns\TextColumn::make('tickets_count')
                    ->counts('tickets')
                    ->label('Tickets'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // No toggle filter here
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TicketStatusesRelationManager::class,
            RelationManagers\MembersRelationManager::class,
            RelationManagers\EpicsRelationManager::class,
            RelationManagers\TicketsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit')
        ];
    }

    // Add this method to show all projects for super_admin, but only member projects for regular users
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Check if the current user has the super_admin role
        // Adjust this condition based on how you check for roles in your application
        $userIsSuperAdmin = auth()->user() && (
            // If using Spatie Permission package
            (method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('super_admin'))
            // Or if using a simple role column
            || (isset(auth()->user()->role) && auth()->user()->role === 'super_admin')
        );

        if (!$userIsSuperAdmin) {
            // If not a super_admin, only show projects where user is a member
            $query->whereHas('members', function (Builder $query) {
                $query->where('user_id', auth()->id());
            });
        }

        return $query;
    }
}
