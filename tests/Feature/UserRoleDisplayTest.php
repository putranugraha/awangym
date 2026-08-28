<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('users index displays a custom assigned role instead of admin', function () {
    $role = Role::create(['name' => 'front_office', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    Livewire::test('app::users.index')
        ->assertSeeHtml('user-role-front_office">Front Office</span>');
});

test('users index clearly labels users without a role', function () {
    User::factory()->create();

    Livewire::test('app::users.index')
        ->assertSeeHtml('user-role-unassigned">Tanpa Role</span>');
});
