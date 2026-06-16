<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

it('lets a staff member delete their own account with the correct password', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $this->actingAs($staff);

    Livewire::test('pages::admin.settings.delete-account')
        ->set('delete_password', 'password')
        ->call('deleteAccount')
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect(User::find($staff->id))->toBeNull();
});

it('rejects deletion when the password is wrong', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $this->actingAs($staff);

    Livewire::test('pages::admin.settings.delete-account')
        ->set('delete_password', 'not-my-password')
        ->call('deleteAccount')
        ->assertHasErrors('delete_password');

    expect(User::find($staff->id))->not->toBeNull();
});

it('blocks the only super-admin from deleting their account', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    $this->actingAs($admin);

    Livewire::test('pages::admin.settings.delete-account')
        ->assertSet('isLastSuperAdmin', true)
        ->set('delete_password', 'password')
        ->call('deleteAccount')
        ->assertHasErrors('delete_password');

    expect(User::find($admin->id))->not->toBeNull();
});

it('lets a super-admin delete their account when another super-admin remains', function () {
    $other = User::factory()->create();
    $other->assignRole('super-admin');

    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    $this->actingAs($admin);

    Livewire::test('pages::admin.settings.delete-account')
        ->assertSet('isLastSuperAdmin', false)
        ->set('delete_password', 'password')
        ->call('deleteAccount')
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect(User::find($admin->id))->toBeNull()
        ->and(User::find($other->id))->not->toBeNull();
});
