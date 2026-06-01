{{-- Cookie consent notice — shown until dismissed (remembered in localStorage). --}}
<div x-data="{ show: false }"
    x-init="show = ! localStorage.getItem('cookie-consent')"
    x-show="show"
    x-cloak
    x-transition.opacity
    class="fixed inset-x-0 bottom-0 z-50 border-t border-zinc-200 bg-white/95 backdrop-blur"
    role="dialog"
    aria-label="Cookie notice">
    <div class="shell flex flex-col items-start gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-[13px] leading-relaxed text-ink-2">
            We use cookies to improve your experience and analyse site traffic.
            <a href="{{ route('page.show', 'cookie-policy') }}" class="text-brand-500 underline" wire:navigate>Learn more</a>.
        </p>
        <div class="flex shrink-0 gap-2">
            <flux:button size="sm" variant="primary"
                x-on:click="localStorage.setItem('cookie-consent', 'accepted'); show = false">
                Accept
            </flux:button>
        </div>
    </div>
</div>
