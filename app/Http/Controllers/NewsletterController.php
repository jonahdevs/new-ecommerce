<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function confirm(string $token): View|RedirectResponse
    {
        $subscriber = Subscriber::where('token', $token)
            ->whereNull('subscribed_at')
            ->whereNull('unsubscribed_at')
            ->first();

        if (! $subscriber) {
            return redirect()->route('home');
        }

        $subscriber->update(['subscribed_at' => now()]);

        return view('pages.newsletter.confirmed');
    }

    public function unsubscribe(string $token): View
    {
        $subscriber = Subscriber::where('token', $token)
            ->whereNull('unsubscribed_at')
            ->first();

        if ($subscriber) {
            $subscriber->update(['unsubscribed_at' => now()]);
        }

        return view('pages.newsletter.unsubscribed');
    }
}
