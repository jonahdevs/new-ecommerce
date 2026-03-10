{{-- Downloads --}}
<div wire:cloak wire:show="activeTab == 'downloads'" class="space-y-5">

    {{-- Download Settings --}}
    <div class="grid grid-cols-2 gap-4">
        <flux:field>
            <flux:input type="number" min="0" wire:model="form.download_limit" label="Download Limit"
                placeholder="0" />
            <flux:description>
                How many times a customer can download. 0 = unlimited.
            </flux:description>
        </flux:field>

        <flux:field>
            <flux:input type="number" min="0" wire:model="form.download_expiry" label="Download Expiry (days)"
                placeholder="0" />
            <flux:description>
                Days after purchase before link expires. 0 = never expires.
            </flux:description>
        </flux:field>
    </div>

    <flux:separator />

    {{-- Downloadable Files --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading>Downloadable Files</flux:heading>
            <flux:subheading>Files the customer receives after purchase</flux:subheading>
        </div>
        <flux:button type="button" size="sm" icon="plus" wire:click="addDownloadFile">
            Add File
        </flux:button>
    </div>

    @if (!empty($form->downloads))
        <div class="space-y-3">
            @foreach ($form->downloads as $index => $download)
                <div class="grid grid-cols-12 gap-3 items-center p-3 border dark:border-zinc-700 rounded-md"
                    wire:key="download-{{ $index }}">

                    {{-- File Icon --}}
                    <div class="col-span-1 flex justify-center">
                        @if (!empty($download['file']))
                            <flux:icon.document-text class="size-8 text-sheffield-blue" />
                        @else
                            <flux:icon.document class="size-8 text-zinc-400" />
                        @endif
                    </div>

                    {{-- Name --}}
                    <div class="col-span-4">
                        <flux:input wire:model="form.downloads.{{ $index }}.name"
                            placeholder="e.g. User Manual, Software Installer" />
                    </div>

                    {{-- File Upload --}}
                    <div class="col-span-6">
                        {{-- Loading --}}
                        <div wire:loading wire:target="form.downloads.{{ $index }}.file"
                            class="flex items-center gap-2 text-zinc-500 py-2">
                            <flux:icon.arrow-path class="size-4 animate-spin text-sheffield-blue" />
                            <flux:text class="text-sm">Uploading...</flux:text>
                        </div>

                        <div wire:loading.remove wire:target="form.downloads.{{ $index }}.file">
                            @if (!empty($download['file']))
                                {{-- File uploaded preview --}}
                                <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                    <flux:icon.check-circle class="size-4 text-green-500 shrink-0" />
                                    <span class="truncate">
                                        {{ is_object($download['file'])
                                            ? $download['file']->getClientOriginalName()
                                            : $download['file_name'] ?? 'Uploaded file' }}
                                    </span>
                                    <flux:link wire:click="clearDownloadFile({{ $index }})"
                                        class="text-xs text-red-500 cursor-pointer shrink-0">
                                        Remove
                                    </flux:link>
                                </div>
                            @elseif (!empty($download['file_path']))
                                {{-- Existing saved file --}}
                                <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                    <flux:icon.paper-clip class="size-4 text-zinc-400 shrink-0" />
                                    <span class="truncate">{{ $download['file_name'] ?? 'Existing file' }}</span>
                                    <span class="text-xs text-zinc-400 shrink-0">
                                        {{ $download['formatted_file_size'] ?? '' }}
                                    </span>
                                    <label class="text-xs text-sheffield-blue cursor-pointer shrink-0 hover:underline">
                                        Replace
                                        <input type="file" class="hidden"
                                            wire:model="form.downloads.{{ $index }}.file" />
                                    </label>
                                </div>
                            @else
                                {{-- No file yet --}}
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <div
                                        class="flex items-center gap-2 px-3 py-1.5 border border-dashed border-zinc-300 dark:border-zinc-600 rounded-md text-sm text-zinc-500 group-hover:border-sheffield-blue group-hover:text-sheffield-blue transition-colors">
                                        <flux:icon.arrow-up-tray class="size-4" />
                                        Choose file
                                    </div>
                                    <input type="file" class="hidden"
                                        wire:model="form.downloads.{{ $index }}.file" />
                                </label>
                            @endif
                        </div>
                    </div>

                    {{-- Remove --}}
                    <div class="col-span-1 flex justify-end">
                        <flux:button type="button" size="xs" variant="ghost" icon="trash" icon-variant="outline"
                            class="text-red-500!" wire:click="removeDownloadFile({{ $index }})"
                            wire:confirm="Remove this download file?" />
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- Empty State --}}
        <div
            class="text-center py-10 border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-md text-zinc-400">
            <flux:icon.arrow-down-tray class="size-10 mx-auto mb-2 opacity-40" />
            <p class="text-sm font-medium">No downloadable files yet</p>
            <p class="text-xs mt-1">Click "Add File" to attach downloadable content</p>
        </div>
    @endif
</div>
