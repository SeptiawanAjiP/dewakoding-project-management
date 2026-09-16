<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProjectPolicy
{
    use HandlesAuthorization;

    /**
     * Mirrors ProjectResource::getEloquentQuery() so a permission alone
     * can never grant access to a project the user is not a member of.
     */
    private function hasAccessToProject(AuthUser $authUser, Project $project): bool
    {
        if (method_exists($authUser, 'hasRole') && $authUser->hasRole(['super_admin'])) {
            return true;
        }

        return $project->members()->where('users.id', $authUser->id)->exists();
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_project');
    }

    public function view(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('view_project') && $this->hasAccessToProject($authUser, $project);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_project');
    }

    public function update(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('update_project') && $this->hasAccessToProject($authUser, $project);
    }

    public function delete(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('delete_project') && $this->hasAccessToProject($authUser, $project);
    }

    public function restore(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('restore_project') && $this->hasAccessToProject($authUser, $project);
    }

    public function forceDelete(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('force_delete_project') && $this->hasAccessToProject($authUser, $project);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_project');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_project');
    }

    public function replicate(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('replicate_project') && $this->hasAccessToProject($authUser, $project);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_project');
    }
}
