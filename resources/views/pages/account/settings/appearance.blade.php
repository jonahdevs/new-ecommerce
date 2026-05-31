<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::settings')] #[Title('Appearance — Sheffield')] class extends Component {
    public bool $embedded = false;

    public function mount(bool $embedded = false): void
    {
        $this->embedded = $embedded;
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading', ['embedded' => $embedded])

    <flux:heading class="sr-only">{{ __('Appearance settings') }}</flux:heading>

    <x-pages::account.settings.layout :embedded="$embedded" :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
            <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
        </flux:radio.group>
    </x-pages::account.settings.layout>
</section>
