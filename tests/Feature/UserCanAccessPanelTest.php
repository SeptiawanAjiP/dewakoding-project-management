<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('denies panel access to a user with no role even if email is verified', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    expect($user->canAccessPanel(new \Filament\Panel))->toBeFalse();
});

it('denies panel access to a user with a role but an unverified email', function () {
    $user = User::factory()->unverified()->create();
    Role::create(['name' => 'member', 'guard_name' => 'web']);
    $user->assignRole('member');

    expect($user->canAccessPanel(new \Filament\Panel))->toBeFalse();
});

it('allows panel access to a user with a verified email and a role', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    Role::create(['name' => 'member', 'guard_name' => 'web']);
    $user->assignRole('member');

    expect($user->canAccessPanel(new \Filament\Panel))->toBeTrue();
});
