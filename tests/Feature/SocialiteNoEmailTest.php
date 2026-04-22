<?php

use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

it('throws error if social provider does not return email', function () {
    // Fake Socialite user with no email
    $mockUser = $this->mock(SocialiteUser::class, function ($mock) {
        $mock->shouldReceive('getEmail')->andReturn(null);
        $mock->shouldReceive('getId')->andReturn('12345');
        $mock->shouldReceive('getName')->andReturn('No Email User');
        $mock->token = 'fake-token';
    });

    // Fake Socialite driver
    Socialite::shouldReceive('driver')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($mockUser);

    // Set intent in session
    Session::put('socialite_intent', 'login');

    $response = $this->get(route('socialite.callback', ['provider' => 'google']));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['social' => 'Your social account did not provide an email address. Please use a provider that shares your email or contact support.']);
});
