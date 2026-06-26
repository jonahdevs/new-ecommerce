<?php

use App\Mail\NewsletterConfirmation;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|email|max:254')]
    public string $email = '';

    /** @var array<int, string> Kept for admin segmentation/export; no longer chosen on the form. */
    public array $interests = [];

    public bool $submitted = false;

    public function subscribe(): void
    {
        $this->validate();

        $existing = Subscriber::where('email', $this->email)->first();

        if ($existing && $existing->isConfirmed()) {
            // Already active — silently update interests and show success
            $existing->update(['interests' => $this->interests]);
            $this->submitted = true;

            return;
        }

        // New, pending, or re-subscribing after unsubscribe
        $subscriber = Subscriber::updateOrCreate(
            ['email' => $this->email],
            [
                'interests'       => $this->interests,
                'token'           => Str::random(64),
                'source'          => request()->path(),
                'ip_address'      => request()->ip(),
                'subscribed_at'   => null,
                'unsubscribed_at' => null,
            ]
        );

        Mail::to($subscriber->email)->queue(new NewsletterConfirmation($subscriber));

        $this->submitted = true;
    }
}; ?>

<section class="mt-12 pb-2">
    <div class="shell">
        <div class="grid grid-cols-1 gap-6 rounded-md bg-brand-blue-700 px-5 py-7 md:grid-cols-2 md:gap-12 md:px-14 md:py-12">

            {{-- Left: copy --}}
            <div class="flex flex-col justify-center">
                <h2 class="font-serif text-3xl font-normal leading-snug text-[#f6ecd9]">
                    Catalog drops and project stories.
                </h2>
                <ul class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-[12.5px] text-[#c9bea4]">
                    <li class="flex items-center gap-1.5">
                        <flux:icon.check-circle variant="micro" class="size-3.5 text-brand-500" /> No spam
                    </li>
                    <li class="flex items-center gap-1.5">
                        <flux:icon.check-circle variant="micro" class="size-3.5 text-brand-500" /> 1-click unsubscribe
                    </li>
                    <li class="flex items-center gap-1.5">
                        <flux:icon.check-circle variant="micro" class="size-3.5 text-brand-500" /> 4,800+ trade subscribers
                    </li>
                </ul>
            </div>

            {{-- Right: form / success --}}
            <div class="flex flex-col justify-center">
                @if ($submitted)
                    <div class="flex items-start gap-4 rounded-md bg-white/8 p-6">
                        <div class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-500 text-white">
                            <flux:icon.check variant="micro" class="size-5" />
                        </div>
                        <div>
                            <div class="font-serif text-xl text-[#f6ecd9]">You're on the list.</div>
                            <div class="mt-1 text-[13px] text-[#c9bea4]">
                                Confirmation sent to <strong class="text-[#f6ecd9]">{{ $email }}</strong>.
                                Issue 13 lands in early September.
                            </div>
                        </div>
                    </div>
                @else
                    <form wire:submit="subscribe">
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <div class="flex-1">
                                <flux:input wire:model="email" type="email" placeholder="you@kitchen.co.ke" />
                                @error('email')
                                    <p class="mt-1 text-[12px] text-[#d8c79d]">{{ $message }}</p>
                                @enderror
                            </div>
                            <flux:button type="submit" variant="primary"
                                class="w-full shrink-0 self-start sm:w-auto"
                                wire:loading.attr="disabled" wire:target="subscribe">
                                <span wire:loading.remove wire:target="subscribe">Subscribe</span>
                                <span wire:loading wire:target="subscribe">Sending…</span>
                            </flux:button>
                        </div>

                        <p class="mt-4 text-[11px] text-[#9c927c]">
                            By subscribing you agree to our
                            <a href="#" class="text-[#c9bea4] underline-offset-2 hover:text-white hover:underline">Privacy Policy</a>.
                            One-click unsubscribe in every issue.
                        </p>
                    </form>
                @endif
            </div>
        </div>
    </div>
</section>
