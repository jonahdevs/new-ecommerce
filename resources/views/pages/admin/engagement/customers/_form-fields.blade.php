{{-- Left Panel --}}
<div class="col-span-1">
    <flux:card class="px-10 pb-10 pt-5 space-y-5">

        {{-- Status Badge (edit only) --}}
        @isset($customer)
            <div class="flex justify-end">
                @php
                    $statusColor = match ($customer->status) {
                        'active' => 'green',
                        'banned' => 'red',
                        default => 'yellow',
                    };
                @endphp
                <flux:badge size="sm" :color="$statusColor" variant="soft" class="capitalize">
                    {{ $customer->status }}
                </flux:badge>
            </div>
        @endisset

        {{-- Avatar Upload --}}
        <div class="flex flex-col items-center justify-center">
            <div class="p-3 rounded-full border border-dashed w-fit">
                <label for="avatar" class="cursor-pointer group">
                    <div
                        class="size-32 rounded-full overflow-hidden relative bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">

                        @if ($form->avatar instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                            <img src="{{ $form->avatar->temporaryUrl() }}" class="w-full h-full object-cover"
                                alt="avatar preview" />
                        @elseif (isset($customer) && $customer->avatar)
                            <img src="{{ asset('storage/' . $customer->avatar) }}" class="w-full h-full object-cover"
                                alt="{{ $customer->name }}" />
                        @else
                            <div class="flex flex-col items-center text-zinc-400">
                                <flux:icon name="camera" class="size-6" />
                                <span class="text-xs mt-1">Upload avatar</span>
                            </div>
                        @endif

                        {{-- Hover overlay when image exists --}}
                        @if (
                            $form->avatar instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile ||
                                (isset($customer) && $customer->avatar))
                            <div
                                class="absolute inset-0 bg-black/30 rounded-full flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                <flux:icon name="camera" class="size-6 text-white" />
                                <span class="text-xs mt-1 text-white">Update</span>
                            </div>
                        @endif
                    </div>
                </label>
                <input type="file" id="avatar" wire:model="form.avatar" class="sr-only" accept="image/*" />
            </div>

            <flux:error name="form.avatar" class="mt-2 text-center" />
            <p class="text-xs text-zinc-400 text-center mt-3 max-w-40">
                Allowed *.jpeg, *.jpg, *.png, *.gif — max 3MB
            </p>
        </div>

        {{-- Email Verified --}}
        <div class="space-y-1">
            <flux:text class="text-sm font-medium">Email Verified</flux:text>
            <div class="flex items-start justify-between gap-3">
                <flux:text class="text-xs text-zinc-400">
                    Disabling this will automatically send the user a verification email
                </flux:text>
                <flux:switch wire:model="form.verify_email" />
            </div>
        </div>

        {{-- Banned (edit only) --}}
        @isset($customer)
            <div class="space-y-1">
                <flux:text class="text-sm font-medium">Banned</flux:text>
                <div class="flex items-center justify-between gap-3">
                    <flux:text class="text-xs text-zinc-400">Disable this account</flux:text>
                    <flux:switch wire:model="form.banned" />
                </div>
            </div>

            {{-- Delete --}}
            <div class="flex justify-center pt-2">
                <flux:button variant="danger" size="sm"
                    wire:confirm="Are you sure? This will permanently delete {{ $customer->email }} and all related data."
                    wire:click="delete" class="w-full">
                    Delete Customer
                </flux:button>
            </div>
        @endisset

    </flux:card>
</div>

{{-- Right Panel --}}
<div class="col-span-3">
    <flux:card class="p-10 space-y-6">

        {{-- Personal Information --}}
        <div>
            <flux:subheading class="mb-4 font-medium">Personal Information</flux:subheading>
            <div class="grid grid-cols-2 gap-x-5 gap-y-4">
                <flux:input label="Full name" wire:model="form.name" placeholder="e.g. John Doe" />

                <flux:input label="Email address" type="email" wire:model="form.email"
                    placeholder="e.g. johndoe@example.com" />

                <flux:input label="Phone number" wire:model="form.phone_number" placeholder="e.g. 0700 000 000" />
            </div>
        </div>

        {{-- Address --}}
        <div>
            <flux:subheading class="mb-4 font-medium">Address</flux:subheading>
            <div class="grid grid-cols-2 gap-x-5 gap-y-4">
                <flux:input label="Country" wire:model="form.country" placeholder="e.g. Kenya" />

                <flux:input label="State / Region" wire:model="form.state" placeholder="e.g. Kiambu" />

                <flux:input label="City" wire:model="form.city" placeholder="e.g. Thika" />

                <flux:input label="Address" wire:model="form.address" placeholder="e.g. Fourth floor, TRG Plaza" />

                <flux:input label="Zip / Code" wire:model="form.zip_code" />
            </div>
        </div>

    </flux:card>
</div>
