{{-- Basic Info --}}
<flux:card class="p-0">
    <div class="px-3 py-2 border-b">
        <flux:heading>Basic Information</flux:heading>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 p-5">
        <flux:input label="Full Name" wire:model="form.name" placeholder="John Doe" />

        <flux:input label="Email Address" wire:model="form.email" type="email" placeholder="john@example.com" />

        <flux:input label="Phone Number" wire:model="form.phone_number" placeholder="+254 700 000 000" />

        <flux:select label="Role" wire:model="form.role" placeholder="Select role...">
            @foreach ($this->roles as $role)
                <flux:select.option :value="$role->name">
                    {{ str($role->name)->replace('_', ' ')->title() }}
                </flux:select.option>
            @endforeach
        </flux:select>
    </div>
</flux:card>

{{-- Password --}}
<flux:card class="p-0">
    <div class="px-3 py-2 border-b">
        <flux:heading>Password</flux:heading>
        @isset($user)
            <flux:subheading>Leave blank to keep current password</flux:subheading>
        @endisset
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 p-5">
        <flux:input :label="isset($user) ? 'New Password' : 'Password'" wire:model="form.password" type="password"
            placeholder="••••••••" />

        <flux:input label="Confirm Password" wire:model="form.password_confirmation" type="password"
            placeholder="••••••••" />
    </div>
</flux:card>

{{-- Account Status --}}
<flux:card class="p-0">
    <div class="px-3 py-2 border-b">
        <flux:heading>Account Status</flux:heading>
    </div>

    <div class="p-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <flux:select label="Status" wire:model.live="form.status">
                @foreach ($this->userStatus as $status)
                    <flux:select.option :value="$status->value">
                        {{ $status->label() }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            {{-- Suspended Until — only show when status is suspended --}}
            @if ($form->status === 'suspended')
                <flux:input label="Suspended Unitl" wire:model="form.suspended_until" type="date" />
            @endif
        </div>

        {{-- Status Reason — show for banned/suspended --}}
        @if (in_array($form->status, ['banned', 'suspended']))
            <flux:textarea label="Reason" wire:model="form.status_reason" placeholder="Provide a reason..."
                rows="3" />
        @endif
    </div>
</flux:card>
