<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->actingAs(User::factory()->create());
});

it('loads the staff admin index', function () {
    $this->get(route('admin.staff.index'))->assertOk();
});

it('invites a staff member and assigns a role', function () {
    Livewire::test('pages::admin.staff.index')
        ->call('openCreate')
        ->set('name', 'Grace Mwangi')
        ->set('email', 'grace@sheffield.test')
        ->set('password', 'secret-password')
        ->set('role', 'staff')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $user = User::firstWhere('email', 'grace@sheffield.test');

    expect($user)->not->toBeNull()
        ->and($user->hasRole('staff'))->toBeTrue();
});

it('only lists users that have a role', function () {
    $customer = User::factory()->create(['name' => 'Plain Customer']);

    $member = User::factory()->create(['name' => 'Admin Member']);
    $member->assignRole('admin');

    Livewire::test('pages::admin.staff.index')
        ->assertSee('Admin Member')
        ->assertDontSee('Plain Customer');
});

it('rejects an unknown role', function () {
    Livewire::test('pages::admin.staff.index')
        ->call('openCreate')
        ->set('name', 'Bad Role')
        ->set('email', 'bad@sheffield.test')
        ->set('password', 'secret-password')
        ->set('role', 'nonexistent')
        ->call('save')
        ->assertHasErrors('role');
});
