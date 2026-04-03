<?php

namespace App\Livewire\Forms\Admin;

use App\Enums\LogisticsProviderStatus;
use App\Models\LogisticsProvider;
use Livewire\Form;

class LogisticsProviderForm extends Form
{
    public ?LogisticsProvider $provider = null;

    public string $name = '';

    public string $code = '';

    public string $type = 'internal';

    public string $description = '';

    public string $status = 'active';

    public function rules(): array
    {
        $uniqueCode = 'required|string|max:50|alpha_dash|unique:logistics_providers,code';

        if ($this->provider) {
            $uniqueCode .= ",{$this->provider->id}";
        }

        return [
            'name' => 'required|string|max:100',
            'code' => $uniqueCode,
            'type' => 'required|in:internal,external',
            'description' => 'nullable|string|max:500',
            'status' => 'required|string|in:'.implode(',', array_column(LogisticsProviderStatus::cases(), 'value')),
        ];
    }

    public function messages(): array
    {
        return [
            'code.alpha_dash' => 'Code may only contain letters, numbers, dashes and underscores.',
            'code.unique' => 'This code is already in use by another provider.',
        ];
    }

    public function setProvider(LogisticsProvider $provider): void
    {
        $this->provider = $provider;
        $this->name = $provider->name;
        $this->code = $provider->code;
        $this->type = $provider->type;
        $this->description = $provider->description ?? '';
        $this->status = $provider->status instanceof LogisticsProviderStatus
            ? $provider->status->value
            : $provider->status;
    }

    public function store(): void
    {
        $this->validate();

        LogisticsProvider::create([
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'description' => $this->description ?: null,
            'status' => $this->status,
        ]);
    }

    public function update(): void
    {
        $this->validate();

        $this->provider->update([
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'description' => $this->description ?: null,
            'status' => $this->status,
        ]);
    }
}
