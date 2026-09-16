<?php

use App\Filament\Pages\ProjectBoard;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createProjectBoardTestProject(string $name): Project
{
    return Project::create([
        'name' => $name,
        'ticket_prefix' => strtoupper(substr($name, 0, 3)),
    ]);
}

it('does not load a project the user is not a member of when mounting via the route parameter', function () {
    $member = User::factory()->create();
    $myProject = createProjectBoardTestProject('Mine');
    $myProject->members()->attach($member->id);

    $otherProject = createProjectBoardTestProject('Other');

    Livewire::actingAs($member)
        ->test(ProjectBoard::class, ['project_id' => $otherProject->id])
        ->assertSet('selectedProject', null);
});

it('does not select a project the user is not a member of via selectProject()', function () {
    $member = User::factory()->create();
    $myProject = createProjectBoardTestProject('Mine');
    $myProject->members()->attach($member->id);

    $otherProject = createProjectBoardTestProject('Other');

    Livewire::actingAs($member)
        ->test(ProjectBoard::class)
        ->call('selectProject', $otherProject->id)
        ->assertSet('selectedProject', null);
});

it('does load a project the user is a member of', function () {
    $member = User::factory()->create();
    $myProject = createProjectBoardTestProject('Mine');
    $myProject->members()->attach($member->id);

    Livewire::actingAs($member)
        ->test(ProjectBoard::class, ['project_id' => $myProject->id])
        ->assertSet('selectedProject.id', $myProject->id);
});
