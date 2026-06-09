<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::settings')] #[Title('Privacy & Data')] class extends Component
{
    use PasswordValidationRules;

    public bool $embedded = false;

    public string $delete_password = '';

    public function mount(bool $embedded = false): void
    {
        $this->embedded = $embedded;
        \Artesaos\SEOTools\Facades\SEOMeta::setRobots('noindex,follow');
    }

    public function deleteUser(Logout $logout): void
    {
        $this->validate(['delete_password' => $this->currentPasswordRules()]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
@if (!$embedded)
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Settings</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Privacy &amp; Data</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush
@endif

<section class="w-full">
    @include('partials.settings-heading', ['embedded' => $embedded])

    <x-pages::account.settings.layout :embedded="$embedded">

        <div class="space-y-4">

            {{-- Data Management --}}
            <flux:card class="overflow-hidden p-0">
                <div class="flex items-center gap-3 border-b border-zinc-200 px-5 py-3">
                    <flux:icon.arrow-down-tray variant="outline" class="size-4 text-zinc-600" />
                    <flux:heading size="sm" class="uppercase tracking-wide">Data Management</flux:heading>
                </div>

                <div class="flex items-center justify-between gap-4 border-b border-zinc-100 p-4">
                    <div>
                        <p class="text-sm font-medium text-ink">Download Your Data</p>
                        <p class="mt-0.5 text-xs text-ink-3">Download a JSON export of your personal data, orders, addresses, reviews and preferences.</p>
                    </div>
                    <flux:button size="sm" icon="arrow-down-tray" :href="route('account.data.export')" wire:navigate.except class="shrink-0">
                        Download
                    </flux:button>
                </div>

                <div class="flex items-center justify-between gap-4 p-4">
                    <div>
                        <p class="text-sm font-medium text-ink">Data Retention</p>
                        <p class="mt-0.5 text-xs text-ink-3">Your data is retained for 7 years after account closure as required by KRA regulations.</p>
                    </div>
                    <span class="shrink-0 rounded border border-zinc-200 bg-zinc-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-zinc-600">7 Years</span>
                </div>
            </flux:card>

            {{-- Delete Account --}}
            <flux:card class="overflow-hidden p-0 border-red-200">
                <div class="flex items-center gap-3 border-b border-red-100 bg-red-50 px-5 py-3">
                    <flux:icon.exclamation-triangle variant="outline" class="size-4 text-red-500" />
                    <flux:heading size="sm" class="uppercase tracking-wide text-red-700!">Delete Account</flux:heading>
                </div>
                <div class="p-5">
                    <p class="mb-4 text-[13px] text-ink-3">
                        {{ __('Deleting your account signs you out and removes your access. Orders and reviews are retained in line with the data-retention policy above for legal and accounting purposes, then anonymised.') }}
                    </p>
                    <flux:modal.trigger name="confirm-user-deletion">
                        <flux:button variant="danger" data-test="delete-user-button">Delete My Account</flux:button>
                    </flux:modal.trigger>
                </div>
            </flux:card>

        </div>

    </x-pages::account.settings.layout>
</section>

<flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
    <form wire:submit="deleteUser" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Are you sure?') }}</flux:heading>
            <flux:subheading>
                {{ __('Once deleted, all your data will be permanently removed. Enter your password to confirm.') }}
            </flux:subheading>
        </div>
        <flux:input wire:model="delete_password" :label="__('Password')" type="password" viewable />
        <flux:error name="delete_password" />
        <div class="flex justify-end gap-3">
            <flux:modal.close>
                <flux:button variant="outline">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="danger" type="submit" data-test="confirm-delete-user-button">
                Delete account
            </flux:button>
        </div>
    </form>
</flux:modal>
</div>
