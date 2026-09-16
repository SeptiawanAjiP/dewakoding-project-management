<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Ticket;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TicketPolicy
{
    use HandlesAuthorization;

    /**
     * Whether the user is allowed to see this specific ticket at all,
     * regardless of which permission the action itself requires.
     *
     * Mirrors TicketResource::getEloquentQuery() so a permission alone
     * can never grant access to a ticket outside the user's projects.
     */
    private function hasAccessToTicket(AuthUser $authUser, Ticket $ticket): bool
    {
        if (method_exists($authUser, 'hasRole') && $authUser->hasRole(['super_admin'])) {
            return true;
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
        return $authUser->can('view_any_ticket');
    }

    public function view(AuthUser $authUser, Ticket $ticket): bool
    {
        return $authUser->can('view_ticket') && $this->hasAccessToTicket($authUser, $ticket);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_ticket');
    }

    public function update(AuthUser $authUser, Ticket $ticket): bool
    {
        return $authUser->can('update_ticket') && $this->hasAccessToTicket($authUser, $ticket);
    }

    public function delete(AuthUser $authUser, Ticket $ticket): bool
    {
        return $authUser->can('delete_ticket') && $this->hasAccessToTicket($authUser, $ticket);
    }

    public function restore(AuthUser $authUser, Ticket $ticket): bool
    {
        return $authUser->can('restore_ticket') && $this->hasAccessToTicket($authUser, $ticket);
    }

    public function forceDelete(AuthUser $authUser, Ticket $ticket): bool
    {
        return $authUser->can('force_delete_ticket') && $this->hasAccessToTicket($authUser, $ticket);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_ticket');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_ticket');
    }

    public function replicate(AuthUser $authUser, Ticket $ticket): bool
    {
        return $authUser->can('replicate_ticket') && $this->hasAccessToTicket($authUser, $ticket);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_ticket');
    }
}
