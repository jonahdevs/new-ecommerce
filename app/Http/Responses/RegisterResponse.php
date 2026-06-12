<?php

namespace App\Http\Responses;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Notifications\Quotes\QuoteDecisionReceived;
use App\Services\QuoteConversionService;
use App\Support\StaffRecipients;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): mixed
    {
        $user = auth()->user();

        // Link any guest quotes that share this email to the new account.
        Quote::where('contact_email', $user->email)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id]);

        if ($pendingId = session()->pull('quote_approval_pending')) {
            $quote = Quote::find($pendingId);

            if ($quote && $quote->user_id === $user->id && $quote->isApprovable()) {
                $quote->update(['status' => QuoteStatus::APPROVED]);
                $quote->recordStatusChange(QuoteStatus::AWAITING_APPROVAL, QuoteStatus::APPROVED, 'Approved by customer.', $user->id);
                $quote->refresh();

                $order = app(QuoteConversionService::class)->convert($quote);

                Notification::send(StaffRecipients::for('quotes.manage'), new QuoteDecisionReceived($quote));

                return redirect()->route('payment.page', $order);
            }
        }

        return redirect()->intended(config('fortify.home'));
    }
}
