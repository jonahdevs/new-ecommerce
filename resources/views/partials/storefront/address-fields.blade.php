{{-- Step 2 body — address details. Binds to label/first_name/last_name/phone/line1/line2/city/postal_code/country/is_default. --}}
<flux:field>
    <flux:label>Label <span class="ms-0.5 text-red-500">*</span></flux:label>
    <flux:select wire:model="label">
        @foreach (['Home', 'Office', 'Warehouse', 'Site', 'Other'] as $opt)
            <flux:select.option value="{{ $opt }}">{{ $opt }}</flux:select.option>
        @endforeach
    </flux:select>
    <flux:error name="label" />
</flux:field>

<div class="grid grid-cols-2 gap-4">
    <flux:field>
        <flux:label>First name <span class="ms-0.5 text-red-500">*</span></flux:label>
        <flux:input wire:model="first_name" placeholder="Anita" />
        <flux:error name="first_name" />
    </flux:field>
    <flux:field>
        <flux:label>Last name <span class="ms-0.5 text-red-500">*</span></flux:label>
        <flux:input wire:model="last_name" placeholder="Wanjiru" />
        <flux:error name="last_name" />
    </flux:field>
</div>

<flux:field>
    <flux:label>Phone</flux:label>
    <flux:input wire:model="phone" type="tel" placeholder="+254 712 345 678" />
    <flux:error name="phone" />
</flux:field>

<flux:field>
    <flux:label>Address line 1 <span class="ms-0.5 text-red-500">*</span></flux:label>
    <flux:input wire:model="line1" placeholder="Street, building, floor" />
    <flux:error name="line1" />
</flux:field>
<flux:field>
    <flux:label>Address line 2</flux:label>
    <flux:input wire:model="line2" placeholder="Suite / Unit" />
    <flux:error name="line2" />
</flux:field>

<div class="grid grid-cols-3 gap-4">
    <flux:field class="col-span-2">
        <flux:label>City <span class="ms-0.5 text-red-500">*</span></flux:label>
        <flux:input wire:model="city" placeholder="Nairobi" />
        <flux:error name="city" />
    </flux:field>
    <flux:field>
        <flux:label>Postal code</flux:label>
        <flux:input wire:model="postal_code" placeholder="00100" />
        <flux:error name="postal_code" />
    </flux:field>
</div>

<flux:field>
    <flux:label>Country <span class="ms-0.5 text-red-500">*</span></flux:label>
    <flux:select wire:model="country">
        <flux:select.option value="KE">Kenya</flux:select.option>
        <flux:select.option value="UG">Uganda</flux:select.option>
        <flux:select.option value="TZ">Tanzania</flux:select.option>
        <flux:select.option value="RW">Rwanda</flux:select.option>
    </flux:select>
    <flux:error name="country" />
</flux:field>

<flux:checkbox wire:model="is_default" label="Set as default address" />
