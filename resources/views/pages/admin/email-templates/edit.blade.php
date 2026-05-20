<?php

use App\Enums\EmailTemplateType;
use App\Models\EmailTemplate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

new #[Title('Edit Email Template')] class extends Component {
    #[Locked]
    public string $typeValue = '';

    public string $bodyHtml = '';

    public string $bodyJson = '';

    public bool $isActive = true;

    public function mount(string $type): void
    {
        $emailType = EmailTemplateType::tryFrom($type);

        if (! $emailType) {
            throw new NotFoundHttpException();
        }

        $this->typeValue = $emailType->value;

        $template = EmailTemplate::query()->byType($emailType)->first();

        if ($template) {
            $this->bodyHtml = $template->body_html ?? '';
            $this->bodyJson = $template->body_json ?? '';
            $this->isActive = $template->is_active;
        }
    }

    #[Computed]
    public function emailType(): EmailTemplateType
    {
        return EmailTemplateType::from($this->typeValue);
    }

    /** @return array<int, array{token: string, description: string}> */
    #[Computed]
    public function variables(): array
    {
        return $this->emailType()->variables();
    }

    public function save(): void
    {
        $this->authorize('manage.settings');

        EmailTemplate::query()->updateOrCreate(
            ['type' => $this->typeValue],
            [
                'name'        => $this->emailType()->label(),
                'subject'     => $this->emailType()->label(),
                'body_html'   => $this->bodyHtml ?: null,
                'body_json'   => $this->bodyJson ?: null,
                'description' => $this->emailType()->description(),
                'variables'   => $this->emailType()->variables(),
                'is_active'   => $this->isActive,
            ],
        );

        $this->dispatch(
            'notify',
            variant: 'success',
            title: __('Template saved'),
            message: __('Email template saved successfully.'),
        );
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('admin.email-templates.index')">{{ __('Email Templates') }}
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $this->emailType()->label() }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div class="px-6 py-6">
        <div class="mb-5">
            <flux:heading size="xl">{{ $this->emailType()->label() }}</flux:heading>
        </div>

        <form wire:submit="save" class="space-y-4">
            <flux:card class="p-0 overflow-hidden">
                <div x-data="emailEditor({ html: @js($bodyHtml), variables: @js($this->variables()) })"
                    x-init="@if ($bodyJson) loadProject(@js($bodyJson)) @endif" wire:ignore>
                    <div x-ref="emailEditor" class="h-[720px]"></div>
                    <input type="hidden" x-ref="htmlInput" wire:model="bodyHtml" />
                    <input type="hidden" x-ref="jsonInput" wire:model="bodyJson" />
                </div>
            </flux:card>

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">
                    {{ __('Save template') }}
                </flux:button>
            </div>
        </form>
    </div>
</div>
