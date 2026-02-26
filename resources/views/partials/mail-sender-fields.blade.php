<div class="space-y-4">
    <flux:subheading class="font-medium">Sender Details</flux:subheading>

    <flux:field>
        <flux:label>From Address</flux:label>
        <flux:input wire:model="from_address" type="email" placeholder="hello@sheffield.com" />
        <flux:description>The email address your customers will see emails from.</flux:description>
        <flux:error name="from_address" />
    </flux:field>

    <flux:field>
        <flux:label>From Name</flux:label>
        <flux:input wire:model="from_name" placeholder="Sheffield Africa" />
        <flux:description>The name your customers will see emails from.</flux:description>
        <flux:error name="from_name" />
    </flux:field>
</div>
