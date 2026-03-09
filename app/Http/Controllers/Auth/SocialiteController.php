<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirect(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['social' => 'Authentication failed. Please try again.']);
        }

        // Find or create the user
        $user = User::updateOrCreate(
            [
                'email'          => $socialUser->getEmail(),
            ],
            [
                'provider'    => $provider,
                'provider_id' => $socialUser->getId(),
                'name'           => $socialUser->getName(),
                'provider_token' => $socialUser->token,
                'email_verified_at' => now(), // trust OAuth provider's email
            ]
        );

        Auth::login($user, remember: true);

        if ($user->is_staff) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('home'));
    }
}
