{{-- Left Panel --}}
<div class="col-span-1">
    <flux:card class="px-10 pb-10 pt-5 space-y-5">

        {{-- Status Badge (edit only) --}}
        @isset($customer)
            <div class="flex justify-end">
                @php
                    $statusColor = match ($customer->status->value) {
                        'active' => 'green',
                        'banned' => 'red',
                        'suspended' => 'orange',
                        default => 'yellow',
                    };
                @endphp
                <flux:badge size="sm" :color="$statusColor" variant="soft" class="capitalize">
                    {{ $customer->status->label() }}
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

        {{-- Status Management (edit only) --}}
        @isset($customer)
            <div class="space-y-3 pt-1 border-t border-zinc-100 dark:border-zinc-700">
                <flux:text class="text-sm font-medium">Account Status</flux:text>

                <flux:select wire:model.live="form.status">
                    @foreach ($this->userStatus as $status)
                        <flux:select.option :value="$status->value">
                            {{ $status->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="form.status" />

                {{-- Suspended Until --}}
                @if ($form->status === 'suspended')
                    <flux:field>
                        <flux:label>Suspended Until</flux:label>
                        <flux:input wire:model="form.suspended_until" type="date" />
                        <flux:error name="form.suspended_until" />
                    </flux:field>
                @endif

                {{-- Reason --}}
                @if (in_array($form->status, ['banned', 'suspended']))
                    <flux:field>
                        <flux:label>Reason</flux:label>
                        <flux:textarea wire:model="form.status_reason" placeholder="Provide a reason..." rows="3" />
                        <flux:error name="form.status_reason" />
                    </flux:field>
                @endif
            </div>

            {{-- Send Password Reset Link --}}
            <div class="space-y-1 pt-1 border-t border-zinc-100 dark:border-zinc-700">
                <flux:text class="text-sm font-medium">Password Reset</flux:text>
                <flux:text class="text-xs text-zinc-400">
                    Send the customer a link to reset their password
                </flux:text>
                <flux:button variant="ghost" size="sm" icon="envelope" class="w-full mt-2"
                    wire:click="sendPasswordReset" wire:confirm="Send password reset link to {{ $customer->email }}?">
                    Send Reset Link
                </flux:button>
            </div>

            {{-- Delete --}}
            <div class="pt-1 border-t border-zinc-100 dark:border-zinc-700">
                <flux:button variant="danger" size="sm" class="w-full"
                    wire:confirm="Are you sure? This will permanently delete {{ $customer->email }} and all related data."
                    wire:click="delete">
                    Delete Customer
                </flux:button>
            </div>
        @endisset

    </flux:card>
</div>

{{-- Right Panel --}}
<div class="col-span-3 space-y-5">
    {{-- Personal Information --}}
    <flux:card class="p-0">
        <div class="border-b px-3 py-2">
            <flux:subheading class="font-medium">Personal Information</flux:subheading>
        </div>

        <div class="grid grid-cols-2 gap-x-5 gap-y-4 p-5">
            <flux:field>
                <flux:label>Full Name</flux:label>
                <flux:input wire:model="form.name" placeholder="e.g. John Doe" />
                <flux:error name="form.name" />
            </flux:field>

            <flux:field>
                <flux:label>Email Address</flux:label>
                <flux:input wire:model="form.email" type="email" placeholder="e.g. johndoe@example.com" />
                <flux:error name="form.email" />
            </flux:field>

            <flux:field>
                <flux:label>Phone Number</flux:label>
                <flux:input wire:model="form.phone_number" placeholder="e.g. 0700 000 000" />
                <flux:error name="form.phone_number" />
            </flux:field>
        </div>

    </flux:card>

    {{-- Default Address --}}
    <flux:card class="p-0">
        <div class="border-b px-3 py-2">
            <flux:subheading class="font-medium">Default Address</flux:subheading>
            <flux:text class="text-xs text-zinc-400 mt-1">This will be set as the customer's default shipping address
            </flux:text>
        </div>

        <div class="grid grid-cols-2 gap-x-5 gap-y-4 p-5">
            <flux:field>
                <flux:label>First Name</flux:label>
                <flux:input wire:model="form.address_first_name" placeholder="e.g. John" />
                <flux:error name="form.address_first_name" />
            </flux:field>

            <flux:field>
                <flux:label>Last Name</flux:label>
                <flux:input wire:model="form.address_last_name" placeholder="e.g. Doe" />
                <flux:error name="form.address_last_name" />
            </flux:field>

            <flux:field>
                <flux:label>Phone Number</flux:label>
                <flux:input wire:model="form.address_phone" placeholder="e.g. 0700 000 000" />
                <flux:error name="form.address_phone" />
            </flux:field>

            <flux:field>
                <flux:label>County</flux:label>
                <flux:select wire:model.live="form.county_id" placeholder="Select county...">
                    @foreach ($this->counties as $county)
                        <flux:select.option :value="$county->id">{{ $county->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="form.county_id" />
            </flux:field>

            <flux:field>
                <flux:label>Area</flux:label>
                <flux:select wire:model="form.area_id" placeholder="Select area..." :disabled="!$form->county_id">
                    @foreach ($this->areas as $area)
                        <flux:select.option :value="$area->id">{{ $area->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="form.area_id" />
            </flux:field>

            <flux:field class="col-span-2">
                <flux:label>Address</flux:label>
                <flux:input wire:model="form.address_line" placeholder="e.g. Fourth Floor, TRG Plaza" />
                <flux:error name="form.address_line" />
            </flux:field>

            <flux:field class="col-span-2">
                <flux:label>Additional Information <flux:badge size="sm" variant="ghost">Optional</flux:badge>
                </flux:label>
                <flux:textarea wire:model="form.additional_information"
                    placeholder="Landmark, delivery instructions..." rows="2" />
                <flux:error name="form.additional_information" />
            </flux:field>
        </div>
    </flux:card>
</div>
