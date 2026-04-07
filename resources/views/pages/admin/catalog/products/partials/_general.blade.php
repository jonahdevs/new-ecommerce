{{-- General --}}
<div wire:cloak wire:show="activeTab == 'general' && form.type !== 'grouped'" class="space-y-5 p-5">

    <div class="space-y-5">

        <div class="grid grid-cols-2 gap-5">
            {{-- Regular Price --}}
            <flux:input label="Regular Price ({{ get_currency_symbol() }})" type="number" step="0.01" min="0" wire:model="form.price"
                placeholder="0.00" />

            {{-- Sale Price --}}
            <flux:input label="Sale Price ({{ get_currency_symbol() }})" type="number" step="0.01" min="0"
                wire:model="form.sale_price" placeholder="0.00" />
        </div>

        {{-- Cost Price --}}
        <flux:input label="Cost Price ({{ get_currency_symbol() }})" type="number" step="0.01" min="0" wire:model="form.cost_price"
            placeholder="0.00" />

        {{-- Tax Class --}}
        <flux:select wire:model="form.tax_class_id" label="Tax Class" placeholder="Use default tax class from settings" clearable
            description="Override the default tax class for this product. Leave blank to inherit the global default.">
            @foreach ($this->taxClasses as $taxClass)
                <flux:select.option value="{{ $taxClass->id }}">
                    {{ $taxClass->name }} — {{ $taxClass->rateLabel() }}
                </flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:separator wire:cloak wire:show="form.is_downloadable" />

    <div wire:show="form.is_downloadable" wire:cloak class="space-y-5">
        {{-- Downloadable Files --}}
        <div class="space-y-3">
            <div>
                <flux:heading>Downloadable Files</flux:heading>
                <flux:subheading>Files the customer receives after purchase</flux:subheading>
            </div>

            <div class="rounded-md border dark:border-zinc-700 overflow-hidden">

                {{-- Table Header --}}
                <div
                    class="grid grid-cols-12 gap-3 px-4 py-2 bg-zinc-50 dark:bg-zinc-800 text-xs font-medium text-zinc-500 uppercase tracking-wide border-b dark:border-zinc-700">
                    <div class="col-span-4">Name</div>
                    <div class="col-span-5">File URL</div>
                    <div class="col-span-3 text-center">Actions</div>
                </div>

                {{-- Rows --}}
                <div class="divide-y dark:divide-zinc-700">

                    @foreach ($form->downloads as $index => $download)
                        <div class="grid grid-cols-12 gap-3 px-4 py-3 items-center"
                            wire:key="download-{{ $index }}">

                            {{-- Name --}}
                            <div class="col-span-4">
                                <flux:input wire:model="form.downloads.{{ $index }}.name"
                                    placeholder="e.g. User Manual" />
                                <flux:error name="form.downloads.{{ $index }}.name" />
                            </div>

                            {{-- File URL --}}
                            <div class="col-span-5">
                                <flux:icon.loading wire:loading wire:target="form.downloads.{{ $index }}.file"
                                    class="size-4" />

                                <div wire:loading.remove wire:target="form.downloads.{{ $index }}.file">
                                    @if (!empty($download['file']))
                                        <div class="flex items-center gap-1.5">
                                            <flux:icon.check-circle class="size-4 text-green-500 shrink-0" />
                                            <span class="text-sm text-zinc-600 dark:text-zinc-300 truncate">
                                                {{ is_object($download['file'])
                                                    ? $download['file']->getClientOriginalName()
                                                    : $download['file_name'] ?? 'Uploaded file' }}
                                            </span>
                                        </div>
                                    @elseif (!empty($download['file_path']))
                                        <div class="flex items-center gap-1.5">
                                            <flux:icon.paper-clip class="size-4 text-zinc-400 shrink-0" />
                                            <span class="text-sm text-zinc-600 dark:text-zinc-300 truncate">
                                                {{ $download['file_name'] ?? 'Existing file' }}
                                            </span>
                                            @if (!empty($download['formatted_file_size']))
                                                <span class="text-xs text-zinc-400 shrink-0">
                                                    {{ $download['formatted_file_size'] }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-sm text-zinc-400 italic">No file chosen</span>
                                    @endif
                                </div>
                                <flux:error name="form.downloads.{{ $index }}.file" />
                            </div>

                            {{-- Actions --}}
                            <div class="col-span-3 flex items-center justify-center gap-2">
                                <label
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs border border-zinc-300 dark:border-zinc-600 rounded-md text-zinc-600 dark:text-zinc-300 hover:border-brand-secondary hover:text-brand-secondary cursor-pointer transition-colors">
                                    <flux:icon.arrow-up-tray class="size-3.5" />
                                    {{ !empty($download['file']) || !empty($download['file_path']) ? 'Replace' : 'Choose File' }}
                                    <input type="file" class="hidden"
                                        wire:model="form.downloads.{{ $index }}.file" />
                                </label>

                                <button type="button" wire:click="removeDownloadFile({{ $index }})"
                                    wire:confirm="Remove this download file?"
                                    class="text-zinc-400 hover:text-red-500 transition-colors">
                                    <flux:icon.x-mark class="size-4" />
                                </button>
                            </div>
                        </div>
                    @endforeach

                    {{-- Add File Row --}}
                    <div class="px-4 py-3">
                        <flux:button type="button" size="sm" wire:click="addDownloadFile"
                            class="cursor-pointer font-normal!">
                            Add File
                        </flux:button>
                    </div>
                </div>
            </div>

            <flux:error name="form.downloads" />
        </div>

        <div class="grid grid-cols-2 gap-5">
            <flux:field>
                <flux:input type="number" wire:model="form.download_limit" label="Download Limit" placeholder="0"
                    description:trailing="Leave blank for unlimited re-downloads" />
                <flux:error name="form.download_limit" />
            </flux:field>

            <flux:field>
                <flux:input type="number" wire:model="form.download_expiry" label="Download Expiry (days)"
                    placeholder="0"
                    description:trailing="Number of days before download link expires, or 0 for never" />
                <flux:error name="form.download_expiry" />
            </flux:field>
        </div>
    </div>
</div>
