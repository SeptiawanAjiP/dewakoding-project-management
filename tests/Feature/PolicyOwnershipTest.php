<?php

use App\Models\Notification;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function createPolicyTestProject(string $name): Project
{
    return Project::create([
        'name' => $name,
        'ticket_prefix' => strtoupper(substr($name, 0, 3)),
    ]);
}

function createPolicyTestTicket(Project $project, string $name): Ticket
{
    $status = TicketStatus::create([
        'project_id' => $project->id,
        'name' => 'To Do',
        'color' => '#000000',
        'sort_order' => 0,
    ]);

    return Ticket::create([
        'project_id' => $project->id,
        'ticket_status_id' => $status->id,
        'name' => $name,
    ]);
}

function givePermission(User $user, string $permission): void
{
    Permission::findOrCreate($permission, 'web');
    $user->givePermissionTo($permission);
}

it('does not let a user update a ticket outside their projects even with the update_ticket permission', function () {
    $outsider = User::factory()->create();
    givePermission($outsider, 'update_ticket');

    $otherProject = createPolicyTestProject('Other');
    $ticket = createPolicyTestTicket($otherProject, 'Secret ticket');

    expect($outsider->can('update', $ticket))->toBeFalse();
});

it('lets a project member update a ticket in their own project', function () {
    $member = User::factory()->create();
    givePermission($member, 'update_ticket');

    $project = createPolicyTestProject('Mine');
    $project->members()->attach($member->id);

    $ticket = createPolicyTestTicket($project, 'My ticket');

    expect($member->can('update', $ticket))->toBeTrue();
});

it('does not let a user view a ticket comment outside their projects', function () {
    $outsider = User::factory()->create();
    givePermission($outsider, 'view_ticket::comment');

    $author = User::factory()->create();
    $otherProject = createPolicyTestProject('Other');
    $ticket = createPolicyTestTicket($otherProject, 'Secret ticket');

    $commentId = DB::table('ticket_comments')->insertGetId([
        'ticket_id' => $ticket->id,
        'user_id' => $author->id,
        'comment' => 'secret comment',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $comment = TicketComment::find($commentId);

    expect($outsider->can('view', $comment))->toBeFalse();
});

it('does not let a user view a project they are not a member of', function () {
    $outsider = User::factory()->create();
    givePermission($outsider, 'view_project');

    $otherProject = createPolicyTestProject('Other');

    expect($outsider->can('view', $otherProject))->toBeFalse();
});

it('does not let a user view another user\'s notification', function () {
    $viewer = User::factory()->create();
    givePermission($viewer, 'view_notification');

    $owner = User::factory()->create();
    givePermission($owner, 'view_notification');

    $notification = Notification::create([
        'user_id' => $owner->id,
        'type' => 'comment_added',
        'title' => 'Test',
        'message' => 'Test message',
    ]);

    expect($viewer->can('view', $notification))->toBeFalse();
    expect($owner->can('view', $notification))->toBeTrue();
});
