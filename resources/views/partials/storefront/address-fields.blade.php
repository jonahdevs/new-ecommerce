{{-- Address details form. Binds to: label, first_name, last_name, phone, alternative_phone, line1, delivery_instructions, is_default. --}}
<flux:field>
    <flux:label>Full name <span class="ms-0.5 text-red-500">*</span></flux:label>
    <flux:input wire:model="name" placeholder="Anita Wanjiru" />
    <flux:error name="name" />
</flux:field>

<div class="grid grid-cols-2 gap-4">
    <flux:field>
        <flux:label>Phone</flux:label>
        <flux:input wire:model="phone" type="tel" placeholder="+254 712 345 678" />
        <flux:error name="phone" />
    </flux:field>
    <flux:field>
        <flux:label>Alternative phone</flux:label>
        <flux:input wire:model="alternative_phone" type="tel" placeholder="+254 722 345 678" />
        <flux:error name="alternative_phone" />
    </flux:field>
</div>

<flux:field>
    <flux:label>Street / Apartment / Office <span class="ms-0.5 text-red-500">*</span></flux:label>
    <flux:input wire:model="line1" placeholder="e.g. Westlands, ABC Place, 3rd floor" />
    <flux:error name="line1" />
</flux:field>

<flux:field>
    <flux:label>Delivery instructions</flux:label>
    <flux:textarea wire:model="delivery_instructions" placeholder="e.g. Call on arrival, gate code is 1234…" rows="2" />
    <flux:error name="delivery_instructions" />
</flux:field>

<flux:field>
    <flux:label>Label <span class="ms-0.5 text-red-500">*</span></flux:label>
    <flux:radio.group wire:model="label" class="flex flex-wrap gap-x-4 gap-y-2">
        @foreach (['Home', 'Work', 'Other'] as $opt)
            <flux:radio value="{{ $opt }}" label="{{ $opt }}" />
        @endforeach
    </flux:radio.group>
    <flux:error name="label" />
</flux:field>

<flux:checkbox wire:model="is_default" label="Set as default address" />
