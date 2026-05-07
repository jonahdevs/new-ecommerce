<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Socialite;

class SocialiteController extends Controller
{
    /**
     * Redirect to the OAuth provider.
     *
     * Stores the intent ('login' or 'register') in the session so the
     * callback knows whether to find-only or find-or-create the user.
     */
    public function redirect(string $provider, Request $request)
    {
        session(['socialite_intent' => $request->query('intent', 'login')]);

        if ($provider === 'facebook') {
            return Socialite::driver($provider)
                ->scopes(['email'])
                ->with([
                    'fields'    => 'name,email,id',
                    'auth_type' => 'rerequest',
                ])
                ->redirect();
        }

        return Socialite::driver($provider)
            ->redirect();
    }

    public function callback(string $provider, Request $request)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['social' => 'Authentication failed. Please try again.']);
        }
        Log::info('social User:' . json_encode($socialUser, JSON_PRETTY_PRINT));

        // Require email from provider
        if (empty($socialUser->getEmail())) {
            return redirect()->route('login')->withErrors([
                'social' => 'Your social account did not provide an email address. Please use a provider that shares your email or contact support.',
            ]);
        }

        $intent = session()->pull('socialite_intent', 'login');


        $user = User::where('email', $socialUser->getEmail())->first();

        if ($intent === 'register') {
            // Registration flow — create account if it doesn't exist yet.
            if (! $user) {
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'provider_token' => $socialUser->token,
                    'email_verified_at' => now(),
                    'terms_accepted_at' => now(),
                    'terms_accepted_ip' => $request->ip(),
                ]);
            }
        } else {
            // Login flow — only allow existing accounts.
            if (! $user) {
                return redirect()->route('login')->withErrors([
                    'social' => 'No account found for ' . $socialUser->getEmail() . '. Please register first.',
                ]);
            }
        }

        // Link / refresh the social provider on the account.
        $user->update([
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'provider_token' => $socialUser->token,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);

        Auth::login($user, remember: true);

        if ($user->is_staff) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('home'));
    }
}
