<?php

use App\Models\User;
use App\Settings\IntegrationSettings;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    app(IntegrationSettings::class)->fill(['google_login_enabled' => true])->save();
});

it('redirects to google when enabled', function () {
    Socialite::fake('google');

    $response = $this->get(route('auth.google.redirect'));

    $response->assertRedirect();
});

it('returns 404 when google login is disabled', function () {
    app(IntegrationSettings::class)->fill(['google_login_enabled' => false])->save();

    $this->get(route('auth.google.redirect'))->assertNotFound();
    $this->get(route('auth.google.callback'))->assertNotFound();
});

it('creates a new user on first google login', function () {
    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-123',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]));

    $this->get(route('auth.google.callback'))->assertRedirect();

    $this->assertDatabaseHas('users', [
        'google_id' => 'google-123',
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
    ]);

    expect(auth()->check())->toBeTrue();
});

it('logs in an existing user by google id', function () {
    $user = User::factory()->create(['google_id' => 'google-456', 'email' => 'existing@example.com']);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-456',
        'name' => $user->name,
        'email' => $user->email,
    ]));

    $this->get(route('auth.google.callback'))->assertRedirect();

    expect(auth()->id())->toBe($user->id);
    expect(User::count())->toBe(1);
});

it('links google id to existing account with matching email', function () {
    $user = User::factory()->create(['email' => 'linked@example.com', 'google_id' => null]);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-789',
        'name' => $user->name,
        'email' => 'linked@example.com',
    ]));

    $this->get(route('auth.google.callback'))->assertRedirect();

    expect($user->fresh()->google_id)->toBe('google-789');
    expect(auth()->id())->toBe($user->id);
    expect(User::count())->toBe(1);
});
