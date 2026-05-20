<?php

use App\Enums\EmailTemplateType;
use App\Models\EmailTemplate;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Email Templates')] class extends Component {
    #[Computed]
    public function rows(): Collection
    {
        $existing = EmailTemplate::query()->get()->keyBy(fn($t) => $t->type->value);

        return collect(EmailTemplateType::cases())->map(function (EmailTemplateType $type) use ($existing) {
            $template = $existing->get($type->value);

            return [
                'type'         => $type,
                'label'        => $type->label(),
                'description'  => $type->description(),
                'isCustomized' => $template && $template->body_html,
                'isActive'     => $template?->is_active ?? true,
            ];
        });
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('Email Templates')"
        :subheading="__('Customize the transactional emails sent to customers')">

        <div class="space-y-3">
            @foreach ($this->rows as $row)
                <flux:card class="p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <flux:heading size="sm">{{ $row['label'] }}</flux:heading>

                                @if ($row['isCustomized'])
                                    <flux:badge color="lime" size="sm">{{ __('Custom') }}</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">{{ __('Default') }}</flux:badge>
                                @endif
                            </div>
                            <flux:text class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $row['description'] }}
                            </flux:text>
                        </div>

                        <flux:button :href="route('settings.email-templates.edit', $row['type']->value)"
                            wire:navigate size="sm" variant="ghost" icon="pencil-square">
                            {{ __('Edit') }}
                        </flux:button>
                    </div>
                </flux:card>
            @endforeach
        </div>

    </x-pages::admin.settings.layout>
</div>
