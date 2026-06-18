<?php

use App\Models\Address;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::app')] #[Title('Edit Customer — Admin')] class extends Component
{
    use WithFileUploads;

    #[Locked]
    public User $customer;

    // Profile
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public $pendingAvatar = null;

    // Email verified
    public bool $emailVerified = false;

    // Ban
    public bool $showBanModal = false;

    public string $banComment = '';

    // Default address
    public ?int $defaultAddressId = null;

    public string $addressName = '';

    public string $addressPhone = '';

    public string $addressAlternativePhone = '';

    public string $addressLine1 = '';

    public string $addressDeliveryInstructions = '';

    public string $addressLabel = '';

    public function mount(User $customer): void
    {
        $this->customer = $customer->load('addresses');
        $this->name = $customer->name;
        $this->email = $customer->email;
        $this->phone = (string) $customer->phone;
        $this->emailVerified = (bool) $customer->email_verified_at;

        $default = $customer->addresses->firstWhere('is_default', true)
            ?? $customer->addresses->first();

        if ($default) {
            $this->defaultAddressId = $default->id;
            $this->addressName = (string) $default->name;
            $this->addressPhone = (string) $default->phone;
            $this->addressAlternativePhone = (string) $default->alternative_phone;
            $this->addressLine1 = (string) $default->line1;
            $this->addressDeliveryInstructions = (string) $default->delivery_instructions;
            $this->addressLabel = (string) $default->label;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->customer->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'pendingAvatar' => ['nullable', 'image', 'max:3072'],
            'addressName' => ['nullable', 'string', 'max:255'],
            'addressPhone' => ['nullable', 'string', 'max:50'],
            'addressAlternativePhone' => ['nullable', 'string', 'max:50'],
            'addressLine1' => ['nullable', 'string', 'max:500'],
            'addressDeliveryInstructions' => ['nullable', 'string', 'max:500'],
            'addressLabel' => ['nullable', 'string', 'max:100'],
        ]);

        if ($this->pendingAvatar) {
            if ($this->customer->avatar) {
                Storage::disk('public')->delete($this->customer->avatar);
            }
            $avatarPath = $this->pendingAvatar->store('avatars', 'public');
            $this->pendingAvatar = null;
        }

        $this->customer->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'avatar' => isset($avatarPath) ? $avatarPath : $this->customer->avatar,
            'email_verified_at' => $this->emailVerified
                ? ($this->customer->email_verified_at ?? now())
                : null,
        ]);

        $this->customer->refresh();

        if ($this->addressLine1) {
            $addressData = [
                'name' => $this->addressName ?: $this->name,
                'phone' => $this->addressPhone ?: null,
                'alternative_phone' => $this->addressAlternativePhone ?: null,
                'line1' => $this->addressLine1,
                'delivery_instructions' => $this->addressDeliveryInstructions ?: null,
                'label' => $this->addressLabel ?: null,
                'is_default' => true,
            ];

            if ($this->defaultAddressId) {
                Address::findOrFail($this->defaultAddressId)->update($addressData);
            } else {
                $address = $this->customer->addresses()->create($addressData);
                $this->defaultAddressId = $address->id;
            }
        }

        Flux::toast(heading: 'Customer updated', text: $this->customer->name.' has been saved.', variant: 'success');
    }

    public function removeAvatar(): void
    {
        if ($this->customer->avatar) {
            Storage::disk('public')->delete($this->customer->avatar);
            $this->customer->update(['avatar' => null]);
        }
        $this->pendingAvatar = null;
    }

    public function sendResetLink(): void
    {
        $status = Password::sendResetLink(['email' => $this->customer->email]);

        if ($status === Password::RESET_LINK_SENT) {
            Flux::toast(heading: 'Reset link sent', text: 'A password reset link has been sent to '.$this->customer->email.'.', variant: 'success');
        } else {
            Flux::toast(heading: 'Could not send', text: __($status), variant: 'danger');
        }
    }

    public function ban(): void
    {
        $this->validate(['banComment' => ['nullable', 'string', 'max:500']]);

        $this->customer->ban(['comment' => $this->banComment ?: null]);
        $this->customer->refresh();
        $this->banComment = '';
        $this->showBanModal = false;

        Flux::toast(heading: 'Customer banned', text: $this->customer->name.' has been banned.', variant: 'warning');
    }

    public function unban(): void
    {
        $this->customer->unban();
        $this->customer->refresh();

        Flux::toast(heading: 'Ban lifted', text: $this->customer->name.' can now access the store.', variant: 'success');
    }

    public function delete(): void
    {
        $name = $this->customer->name;
        $this->customer->delete();
        Flux::toast(heading: 'Customer deleted', text: $name.' has been removed.', variant: 'success');
        $this->redirectRoute('admin.customers.index', navigate: true);
    }

    public function getAvatarPreview(): ?string
    {
        if ($this->pendingAvatar) {
            return $this->pendingAvatar->temporaryUrl();
        }

        return $this->customer->avatar
            ? Storage::disk('public')->url($this->customer->avatar)
            : null;
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.customers.index')" wire:navigate>Customers</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.customers.show', $customer)" wire:navigate>{{ $customer->name }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Edit</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <flux:heading size="xl">Edit Customer</flux:heading>
    <flux:subheading>{{ $customer->name }} &mdash; {{ $customer->email }}</flux:subheading>

    <form wire:submit="save" class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-4 lg:items-start">

        {{-- Left panel --}}
        <div class="lg:col-span-1">
            <flux:card class="overflow-hidden p-0">

                {{-- Body --}}
                <div class="space-y-5 px-8 pb-6 pt-5">

                    {{-- Status badge --}}
                    <div class="flex justify-end">
                        @if ($customer->isBanned())
                            <flux:badge size="sm" color="red" variant="soft">Banned</flux:badge>
                        @else
                            <flux:badge size="sm" color="green" variant="soft">Active</flux:badge>
                        @endif
                    </div>

                    {{-- Avatar upload --}}
                    <div class="flex flex-col items-center justify-center">
                        <div class="w-fit rounded-full border border-dashed p-3 dark:border-zinc-600">
                            <label for="avatar-upload" class="group cursor-pointer">
                                <div
                                    class="relative flex size-32 items-center justify-center overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                    @if ($this->getAvatarPreview())
                                        <img src="{{ $this->getAvatarPreview() }}" class="h-full w-full rounded-full object-cover"
                                            alt="{{ $customer->name }}" />
                                        <div
                                            class="absolute inset-0 flex flex-col items-center justify-center rounded-full bg-black/30 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                                            <flux:icon.camera class="size-6 text-white" />
                                            <span class="mt-1 text-xs text-white">Update</span>
                                        </div>
                                    @else
                                        <div class="flex flex-col items-center text-zinc-400">
                                            <flux:icon.camera class="size-6" />
                                            <span class="mt-1 text-xs">Upload photo</span>
                                        </div>
                                    @endif
                                </div>
                            </label>
                            <input type="file" id="avatar-upload" wire:model="pendingAvatar" accept="image/*"
                                class="sr-only" />
                        </div>
                        @if ($this->getAvatarPreview())
                            <button type="button"
                                wire:click="{{ $pendingAvatar ? '$set(\'pendingAvatar\', null)' : 'removeAvatar' }}"
                                class="mt-2 text-xs text-zinc-400 hover:text-red-500">
                                Remove photo
                            </button>
                        @endif
                        @error('pendingAvatar')
                            <p class="mt-2 text-center text-xs text-red-500">{{ $message }}</p>
                        @enderror
                        <p class="mt-3 max-w-40 text-center text-xs text-zinc-400">
                            Allowed *.jpeg, *.jpg, *.png, *.gif — max 3 MB
                        </p>
                    </div>

                    {{-- Email verified --}}
                    <div class="space-y-1">
                        <flux:text class="text-sm font-medium">Email verified</flux:text>
                        <div class="flex items-start justify-between gap-3">
                            <flux:text class="text-xs text-zinc-400">
                                Disabling this will prompt the customer to verify their email on next login.
                            </flux:text>
                            <flux:switch wire:model="emailVerified" />
                        </div>
                    </div>

                    {{-- Ban info (when banned) --}}
                    @if ($customer->isBanned())
                        @if ($activeBan = $customer->bans()->latest()->first())
                            <div class="space-y-1 border-t border-zinc-100 pt-4 dark:border-zinc-700">
                                <flux:text class="text-sm font-medium">Ban reason</flux:text>
                                <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2.5 text-xs dark:border-red-800 dark:bg-red-950/30">
                                    @if ($activeBan->comment)
                                        <p class="font-medium text-red-700 dark:text-red-400">{{ $activeBan->comment }}</p>
                                    @endif
                                    <p class="mt-0.5 text-red-500">Since {{ $customer->banned_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endif
                    @endif

                    {{-- Password reset --}}
                    <div class="space-y-1 border-t border-zinc-100 pt-4 dark:border-zinc-700">
                        <flux:text class="text-sm font-medium">Password reset</flux:text>
                        <flux:text class="text-xs text-zinc-400">
                            Send the customer a link to reset their password.
                        </flux:text>
                        <flux:button variant="ghost" size="sm" icon="envelope" class="mt-2 w-full"
                            wire:click="sendResetLink"
                            wire:confirm="Send password reset link to {{ $customer->email }}?">
                            Send reset link
                        </flux:button>
                    </div>

                </div>

                {{-- Footer actions --}}
                <div class="flex gap-2 border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    @if ($customer->isBanned())
                        <flux:button size="sm" variant="ghost" icon="lock-open" class="flex-1"
                            wire:click="unban"
                            wire:confirm="Lift the ban for '{{ addslashes($customer->name) }}'?">
                            Lift ban
                        </flux:button>
                    @else
                        <flux:button size="sm" icon="no-symbol" class="flex-1"
                            wire:click="$set('showBanModal', true)">
                            Ban customer
                        </flux:button>
                    @endif
                    <flux:button size="sm" variant="danger" icon="trash-2" class="flex-1"
                        wire:click="delete"
                        wire:confirm="Permanently delete {{ $customer->name }}? This cannot be undone.">
                        Delete
                    </flux:button>
                </div>

            </flux:card>
        </div>

        {{-- Right panel --}}
        <div class="space-y-5 lg:col-span-3">

            {{-- Personal information --}}
            <flux:card class="overflow-hidden p-0">
                <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    <flux:heading size="sm" class="uppercase tracking-wide">Personal information</flux:heading>
                </div>
                <div class="grid grid-cols-1 gap-x-5 gap-y-4 p-6 sm:grid-cols-2">
                    <flux:input wire:model="name" label="Full name" placeholder="e.g. Jane Doe" required />
                    <flux:input wire:model="email" type="email" label="Email address"
                        placeholder="e.g. jane@example.com" required />
                    <flux:field>
                        <flux:label>Phone number</flux:label>
                        <x-phone-input wire:model="phone" placeholder="700 000 000" />
                        <flux:error name="phone" />
                    </flux:field>
                </div>
            </flux:card>

            {{-- Default address --}}
            <flux:card class="overflow-hidden p-0">
                <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    <flux:heading size="sm" class="uppercase tracking-wide">Default address</flux:heading>
                </div>
                <div class="grid grid-cols-1 gap-x-5 gap-y-4 p-6 sm:grid-cols-2">
                    <flux:input wire:model="addressName" label="Recipient name" placeholder="e.g. Jane Doe" />
                    <flux:input wire:model="addressLabel" label="Label" placeholder="e.g. Home, Office" />
                    <flux:field>
                        <flux:label>Phone</flux:label>
                        <x-phone-input wire:model="addressPhone" placeholder="700 000 000" />
                        <flux:error name="addressPhone" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Alternative phone</flux:label>
                        <x-phone-input wire:model="addressAlternativePhone" placeholder="711 000 000" />
                        <flux:error name="addressAlternativePhone" />
                    </flux:field>
                    <div class="sm:col-span-2">
                        <flux:input wire:model="addressLine1" label="Address"
                            placeholder="e.g. 4th Floor, TRG Plaza, Westlands" />
                    </div>
                    <div class="sm:col-span-2">
                        <flux:textarea wire:model="addressDeliveryInstructions"
                            label="Delivery instructions (optional)"
                            placeholder="Landmark, gate code, preferred drop-off point…" rows="2" />
                    </div>
                </div>
            </flux:card>

        </div>

        {{-- Bottom action bar --}}
        <div class="lg:col-span-4">
            <flux:card class="flex justify-end gap-3 bg-zinc-50 dark:bg-zinc-900">
                <flux:button variant="ghost" :href="route('admin.customers.show', $customer)"
                    wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save changes</flux:button>
            </flux:card>
        </div>

    </form>

    {{-- Ban modal --}}
    <flux:modal wire:model.self="showBanModal" class="md:w-[420px]" :dismissible="false">
        <flux:heading>Ban {{ $customer->name }}</flux:heading>
        <flux:subheading class="mt-1">They will lose access to the store immediately.</flux:subheading>

        <form wire:submit="ban" class="mt-5 space-y-4">
            <flux:textarea wire:model="banComment" label="Reason (optional)"
                placeholder="e.g. Fraudulent activity, repeated chargebacks…" rows="3" autofocus />

            <div class="flex justify-end gap-3 pt-1">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger" icon="no-symbol">Ban customer</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
