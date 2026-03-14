    <div class="grid grid-cols-2 gap-5">
        {{-- First Name --}}
        <flux:input wire:model="form.first_name" :label="__('First Name')" placeholder="John" />

        {{-- Last Name --}}
        <flux:input wire:model="form.last_name" :label="__('Last Name')" placeholder="Doe" />

        {{-- Phone Number --}}
        <flux:field>
            <flux:label>{{ __('Phone Number') }}</flux:label>
            <flux:input.group>
                <flux:input.group.prefix>+254</flux:input.group.prefix>
                <flux:input wire:model="form.phone_number" placeholder="Enter Your Phone Number" mask="999 999 999" />
            </flux:input.group>
            <flux:error name="form.phone_number" />
        </flux:field>

        {{-- Alternative Phone Number --}}
        <flux:field>
            <flux:label>{{ __('Alternative Phone Number') }}</flux:label>
            <flux:input.group>
                <flux:input.group.prefix>+254</flux:input.group.prefix>
                <flux:input wire:model="form.alternative_phone_number" placeholder="Enter Your Alternative Phone Number"
                    mask="999 999 999" />
            </flux:input.group>
            <flux:error name="form.alternative_phone_number" />
        </flux:field>

        {{-- County --}}
        <flux:select wire:model.live="form.county_id" placeholder="Select County..." :label="__('Region / County')">
            {{-- Explicit null placeholder option --}}
            <flux:select.option value="" selected hidden>
                Select County...
            </flux:select.option>
            @foreach ($this->counties as $zoneName => $zoneCounties)
                <flux:select.option disabled value="">
                    -- {{ $zoneName }} --
                </flux:select.option>

                @foreach ($zoneCounties as $county)
                    <flux:select.option :value="$county->id">
                        {{ $county->name }}
                    </flux:select.option>
                @endforeach
            @endforeach

        </flux:select>

        {{-- Area --}}
        <flux:select wire:model="form.area_id" :label="__('City/Area')">
            <flux:select.option value="" selected hidden>
                {{ $form->county_id ? 'Select Area' : 'Select a county first' }}
            </flux:select.option>
            @foreach ($this->areas as $area)
                <flux:select.option :value="$area->id">
                    {{ $area->name }}
                </flux:select.option>
            @endforeach
        </flux:select>
    </div>

    {{-- Address --}}
    <flux:input wire:model="form.address_text" :label="__('Address')" placeholder="Enter your Address" />

    {{-- Additional Info --}}
    <flux:textarea wire:model="form.additional_information" :label="__('Additional Information')"
        placeholder="Enter Additional Information" />

    @if ($this->hasDefaultAddress)
        <flux:field variant="inline">
            <flux:checkbox wire:model="form.is_default" />
            <flux:label>Set as default Address</flux:label>
        </flux:field>
    @endif
