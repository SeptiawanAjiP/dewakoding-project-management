<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Notification;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class NotificationPolicy
{
    use HandlesAuthorization;

    /**
     * A notification is only ever meant for the user it was sent to.
     */
    private function ownsNotification(AuthUser $authUser, Notification $notification): bool
    {
        if (method_exists($authUser, 'hasRole') && $authUser->hasRole(['super_admin'])) {
            return true;
        }

        return $notification->user_id === $authUser->id;
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_notification');
    }

    public function view(AuthUser $authUser, Notification $notification): bool
    {
        return $authUser->can('view_notification') && $this->ownsNotification($authUser, $notification);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_notification');
    }

    public function update(AuthUser $authUser, Notification $notification): bool
    {
        return $authUser->can('update_notification') && $this->ownsNotification($authUser, $notification);
    }

    public function delete(AuthUser $authUser, Notification $notification): bool
    {
        return $authUser->can('delete_notification') && $this->ownsNotification($authUser, $notification);
    }

    public function restore(AuthUser $authUser, Notification $notification): bool
    {
        return $authUser->can('restore_notification') && $this->ownsNotification($authUser, $notification);
    }

    public function forceDelete(AuthUser $authUser, Notification $notification): bool
    {
        return $authUser->can('force_delete_notification') && $this->ownsNotification($authUser, $notification);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_notification');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_notification');
    }

    public function replicate(AuthUser $authUser, Notification $notification): bool
    {
        return $authUser->can('replicate_notification') && $this->ownsNotification($authUser, $notification);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_notification');
    }
}
