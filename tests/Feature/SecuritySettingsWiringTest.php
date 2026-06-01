<?php

use App\Models\User;
use App\Settings\SecuritySettings;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

it('enforces the configured minimum password length', function () {
    app(SecuritySettings::class)->fill(['min_password_length' => 12])->save();

    expect(Validator::make(['password' => 'short123'], ['password' => Password::default()])->fails())->toBeTrue()
        ->and(Validator::make(['password' => 'longenoughpassword'], ['password' => Password::default()])->fails())->toBeFalse();
});

it('redirects staff without 2FA to security settings when two-factor is required', function () {
    app(SecuritySettings::class)->fill(['require_two_factor' => true])->save();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.products.index'))
        ->assertRedirect(route('security.edit'));
});

it('allows admin access when two-factor is not required', function () {
    // require_two_factor defaults to false.
    $this->actingAs(User::factory()->create())
        ->get(route('admin.products.index'))
        ->assertOk();
});
