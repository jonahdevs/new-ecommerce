<?php

use App\Enums\EmailTemplateType;
use App\Models\EmailTemplate;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

new #[Title('Email Templates')] class extends Component {
    #[Computed]
    public function rows(): Collection
    {
        $existing = EmailTemplate::query()->get()->keyBy(fn ($t) => $t->type->value);

        return collect(EmailTemplateType::cases())->map(function (EmailTemplateType $type) use ($existing) {
            $template = $existing->get($type->value);

            return [
                'type'         => $type,
                'label'        => $type->label(),
                'description'  => $type->description(),
                'bodyHtml'     => $template?->body_html,
                'isCustomized' => $template && $template->body_html,
                'isActive'     => $template?->is_active ?? true,
                'updatedAt'    => $template?->updated_at,
            ];
        });
    }

    public function toggleActive(string $type): void
    {
        $this->authorize('manage.settings');

        $emailType = EmailTemplateType::tryFrom($type);

        if (! $emailType) {
            throw new NotFoundHttpException();
        }

        $template = EmailTemplate::query()->byType($emailType)->first();

        if (! $template) {
            return;
        }

        $template->update(['is_active' => ! $template->is_active]);

        unset($this->rows);

        $this->dispatch(
            'notify',
            variant: 'success',
            title: $template->is_active ? __('Template activated') : __('Template deactivated'),
            message: $template->is_active
                ? __(':label is now active.', ['label' => $emailType->label()])
                : __(':label is now using the default Blade view.', ['label' => $emailType->label()]),
        );
    }

    public function resetToDefault(string $type): void
    {
        $this->authorize('manage.settings');

        $emailType = EmailTemplateType::tryFrom($type);

        if (! $emailType) {
            throw new NotFoundHttpException();
        }

        EmailTemplate::query()->byType($emailType)->delete();

        unset($this->rows);

        $this->dispatch(
            'notify',
            variant: 'success',
            title: __('Reset to default'),
            message: __(':label will now use the default Blade view.', ['label' => $emailType->label()]),
        );
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item>{{ __('Email Templates') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div class="px-6 py-6">
        <div class="mb-6">
            <flux:heading size="xl" class="mb-1">{{ __('Email Templates') }}</flux:heading>
            <flux:subheading>{{ __('Customize the transactional emails sent to customers.') }}</flux:subheading>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @foreach ($this->rows as $row)
                @php($typeValue = $row['type']->value)

                <div class="group" wire:key="card-{{ $typeValue }}">

                    {{-- Preview thumbnail --}}
                    <div
                        class="relative aspect-[4/5] bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:border-primary transition-colors">

                        <a href="{{ route('admin.email-templates.edit', $typeValue) }}" wire:navigate
                            class="absolute inset-0 block">

                            @if ($row['bodyHtml'])
                                <iframe srcdoc="{{ $row['bodyHtml'] }}" sandbox loading="lazy"
                                    class="pointer-events-none absolute top-0 left-0 origin-top-left"
                                    style="width: 250%; height: 250%; transform: scale(0.4);"
                                    title="{{ $row['label'] }} preview"></iframe>
                            @else
                                <div class="absolute inset-0 flex flex-col items-center justify-center gap-2">
                                    <flux:icon.envelope class="size-12 text-zinc-300 dark:text-zinc-700" />
                                    <flux:text class="text-xs text-zinc-400">{{ __('No custom design yet') }}</flux:text>
                                </div>
                            @endif

                            {{-- Hover overlay --}}
                            <div
                                class="absolute inset-0 bg-zinc-900/0 group-hover:bg-zinc-900/50 transition-colors flex items-center justify-center">
                                <span
                                    class="opacity-0 group-hover:opacity-100 transition-opacity inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium bg-primary text-on-primary rounded">
                                    <flux:icon.pencil-square class="size-4" />
                                    {{ $row['isCustomized'] ? __('Edit Email') : __('Create Email') }}
                                </span>
                            </div>
                        </a>

                        {{-- Inactive overlay (top-left) --}}
                        @if ($row['isCustomized'] && ! $row['isActive'])
                            <div class="absolute top-2 left-2 z-10 pointer-events-none">
                                <flux:badge color="amber" size="sm">{{ __('Inactive') }}</flux:badge>
                            </div>
                        @endif

                        {{-- Actions dropdown (top-right) --}}
                        <div class="absolute top-2 right-2 z-10">
                            <flux:dropdown align="end">
                                <flux:button variant="filled" size="xs" icon="ellipsis-vertical"
                                    aria-label="{{ __('Actions') }}" />

                                <flux:menu>
                                    <flux:menu.item icon="pencil-square"
                                        :href="route('admin.email-templates.edit', $typeValue)" wire:navigate>
                                        {{ $row['isCustomized'] ? __('Edit') : __('Create') }}
                                    </flux:menu.item>

                                    @if ($row['isCustomized'])
                                        <flux:menu.separator />

                                        @if ($row['isActive'])
                                            <flux:menu.item icon="pause-circle"
                                                wire:click="toggleActive('{{ $typeValue }}')">
                                                {{ __('Deactivate') }}
                                            </flux:menu.item>
                                        @else
                                            <flux:menu.item icon="play-circle"
                                                wire:click="toggleActive('{{ $typeValue }}')">
                                                {{ __('Activate') }}
                                            </flux:menu.item>
                                        @endif

                                        <flux:menu.separator />

                                        <flux:menu.item icon="arrow-uturn-left" variant="danger"
                                            wire:click="resetToDefault('{{ $typeValue }}')"
                                            wire:confirm="{{ __('Reset :label to the default template? Your customizations will be lost.', ['label' => $row['label']]) }}">
                                            {{ __('Reset to default') }}
                                        </flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>

                    {{-- Card footer --}}
                    <div class="mt-3">
                        <div class="flex items-center gap-2">
                            <flux:text class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">
                                {{ $row['label'] }}
                            </flux:text>
                            @if ($row['isCustomized'])
                                <flux:badge color="lime" size="sm">{{ __('Custom') }}</flux:badge>
                            @endif
                        </div>

                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                            @if ($row['updatedAt'])
                                {{ __('Updated') }} {{ $row['updatedAt']->diffForHumans() }}
                            @else
                                {{ __('Default template') }}
                            @endif
                        </flux:text>
                    </div>

                </div>
            @endforeach
        </div>
    </div>
</div>
