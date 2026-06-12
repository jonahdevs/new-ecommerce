{{-- TODO: wire to a setting (spatie/laravel-settings) so admins can edit/hide --}}
<div class="bg-brand-blue-500 text-[#f2ead9]">
    <div class="shell flex h-9 items-center justify-between gap-4 text-[12.5px]">
        <div class="flex items-center gap-7 overflow-hidden">
            <span class="flex items-center gap-1.5">
                <flux:icon.truck variant="micro" class="size-3.5" />
                Free delivery within Nairobi
            </span>
            <span class="hidden opacity-60 md:inline">·</span>
            <span class="hidden items-center gap-1.5 md:flex">
                <flux:icon.shield-check variant="micro" class="size-3.5" />
                Local parts &amp; service across East Africa
            </span>
            <span class="hidden opacity-60 lg:inline">·</span>
            <span class="hidden items-center gap-1.5 lg:flex">
                <flux:icon.phone variant="micro" class="size-3.5" />
                +254&nbsp;713&nbsp;777&nbsp;111
            </span>
        </div>
        <div class="flex items-center gap-3 text-[#d8c79d]">
            <a href="{{ route('login') }}" class="hidden hover:text-white sm:inline" wire:navigate>Sign in</a>
            <span class="hidden opacity-50 sm:inline">·</span>
            <a href="#" class="hover:text-white">KES</a>
        </div>
    </div>
</div>
