<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TicketComment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TicketCommentPolicy
{
    use HandlesAuthorization;

    /**
     * Mirrors TicketCommentResource::getEloquentQuery() so a permission
     * alone can never grant access to a comment outside the user's projects.
     */
    private function hasAccessToTicketComment(AuthUser $authUser, TicketComment $ticketComment): bool
    {
        if (method_exists($authUser, 'hasRole') && $authUser->hasRole(['super_admin'])) {
            return true;
        }

        $ticket = $ticketComment->ticket;

        if (! $ticket) {
            return false;
        }

        if ($ticket->created_by === $authUser->id) {
            return true;
        }

        if ($ticket->assignees()->where('users.id', $authUser->id)->exists()) {
            return true;
        }

        return $ticket->project()->whereHas('members', function ($query) use ($authUser) {
            $query->where('users.id', $authUser->id);
        })->exists();
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_ticket::comment');
    }

    public function view(AuthUser $authUser, TicketComment $ticketComment): bool
    {
        return $authUser->can('view_ticket::comment') && $this->hasAccessToTicketComment($authUser, $ticketComment);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_ticket::comment');
    }

    public function update(AuthUser $authUser, TicketComment $ticketComment): bool
    {
        return $authUser->can('update_ticket::comment') && $this->hasAccessToTicketComment($authUser, $ticketComment);
    }

    public function delete(AuthUser $authUser, TicketComment $ticketComment): bool
    {
        return $authUser->can('delete_ticket::comment') && $this->hasAccessToTicketComment($authUser, $ticketComment);
    }

    public function restore(AuthUser $authUser, TicketComment $ticketComment): bool
    {
        return $authUser->can('restore_ticket::comment') && $this->hasAccessToTicketComment($authUser, $ticketComment);
    }

    public function forceDelete(AuthUser $authUser, TicketComment $ticketComment): bool
    {
        return $authUser->can('force_delete_ticket::comment') && $this->hasAccessToTicketComment($authUser, $ticketComment);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_ticket::comment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_ticket::comment');
    }

    public function replicate(AuthUser $authUser, TicketComment $ticketComment): bool
    {
        return $authUser->can('replicate_ticket::comment') && $this->hasAccessToTicketComment($authUser, $ticketComment);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_ticket::comment');
    }
}
