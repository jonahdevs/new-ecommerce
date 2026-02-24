{{-- Basic Info --}}
<flux:card class="space-y-5">
    <flux:heading>Basic Information</flux:heading>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <flux:field>
            <flux:label>Full Name</flux:label>
            <flux:input wire:model="form.name" placeholder="John Doe" />
            <flux:error name="form.name" />
        </flux:field>

        <flux:field>
            <flux:label>Email Address</flux:label>
            <flux:input wire:model="form.email" type="email" placeholder="john@example.com" />
            <flux:error name="form.email" />
        </flux:field>

        <flux:field>
            <flux:label>Phone Number</flux:label>
            <flux:input wire:model="form.phone_number" placeholder="+254 700 000 000" />
            <flux:error name="form.phone_number" />
        </flux:field>

        <flux:field>
            <flux:label>Role</flux:label>
            <flux:select wire:model="form.role" placeholder="Select role...">
                @foreach ($this->roles as $role)
                    <flux:select.option :value="$role->name">
                        {{ str($role->name)->replace('_', ' ')->title() }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="form.role" />
        </flux:field>
    </div>
</flux:card>

{{-- Password --}}
<flux:card class="space-y-5">
    <div>
        <flux:heading>Password</flux:heading>
        @isset($user)
            <flux:subheading>Leave blank to keep current password</flux:subheading>
        @endisset
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <flux:field>
            <flux:label>{{ isset($user) ? 'New Password' : 'Password' }}</flux:label>
            <flux:input wire:model="form.password" type="password" placeholder="••••••••" />
            <flux:error name="form.password" />
        </flux:field>

        <flux:field>
            <flux:label>Confirm Password</flux:label>
            <flux:input wire:model="form.password_confirmation" type="password" placeholder="••••••••" />
            <flux:error name="form.password_confirmation" />
        </flux:field>
    </div>
</flux:card>

{{-- Account Status --}}
<flux:card class="space-y-5">
    <flux:heading>Account Status</flux:heading>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <flux:field>
            <flux:label>Status</flux:label>
            <flux:select wire:model.live="form.status">
                @foreach ($this->userStatus as $status)
                    <flux:select.option :value="$status->value">
                        {{ $status->label() }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="form.status" />
        </flux:field>

        {{-- Suspended Until — only show when status is suspended --}}
        @if ($form->status === 'suspended')
            <flux:field>
                <flux:label>Suspended Until</flux:label>
                <flux:input wire:model="form.suspended_until" type="date" />
                <flux:error name="form.suspended_until" />
            </flux:field>
        @endif
    </div>

    {{-- Status Reason — show for banned/suspended --}}
    @if (in_array($form->status, ['banned', 'suspended']))
        <flux:field>
            <flux:label>Reason</flux:label>
            <flux:textarea wire:model="form.status_reason" placeholder="Provide a reason..." rows="3" />
            <flux:error name="form.status_reason" />
        </flux:field>
    @endif
</flux:card>
