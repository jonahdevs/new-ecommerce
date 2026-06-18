@props([
    'placeholder' => '712 345 678',
    'searchPlaceholder' => 'Search country or code',
])

@php
    // Country-code combobox welded to a local-number input, bound to a single
    // E.164 wire:model string (e.g. "+254712345678"). Splits/joins internally,
    // mirroring App\Support\CountryCodes::parse() in JS for the prefix match.
    $countries = \App\Support\CountryCodes::all();
    $dialCodes = \App\Support\CountryCodes::dialCodes();
@endphp

<div
    x-data="{
        open: false,
        search: '',
        active: 0,
        dial: '+254',
        local: '',
        countries: {{ \Illuminate\Support\Js::from($countries) }},
        dialCodes: {{ \Illuminate\Support\Js::from($dialCodes) }},
        value: @entangle($attributes->wire('model')),
        init() {
            [this.dial, this.local] = this.parse(this.value ?? '');
            this.$watch('dial', () => this.sync());
            this.$watch('local', () => this.sync());
        },
        parse(phone) {
            if (! phone) {
                return ['+254', ''];
            }
            if (! phone.startsWith('+')) {
                return ['+254', phone];
            }
            for (const code of this.dialCodes) {
                if (phone.startsWith(code)) {
                    return [code, phone.slice(code.length)];
                }
            }
            return ['+254', phone];
        },
        sync() {
            const local = this.local.replace(/[^0-9]/g, '').replace(/^0+/, '');
            this.value = local ? this.dial + local : '';
        },
        get filtered() {
            const q = this.search.trim().toLowerCase().replace('+', '');

            if (! q) {
                return this.countries;
            }

            return this.countries.filter((c) =>
                c.name.toLowerCase().includes(q) ||
                c.dial.replace('+', '').includes(q) ||
                c.code.toLowerCase().includes(q)
            );
        },
        get current() {
            return this.countries.find((c) => c.dial === this.dial) ?? this.countries[0];
        },
        openPanel() {
            this.open = true;
            this.search = '';
            this.active = Math.max(0, this.filtered.findIndex((c) => c.dial === this.dial));
            this.$nextTick(() => this.$refs.search?.focus());
        },
        choose(country) {
            this.dial = country.dial;
            this.open = false;
        },
        move(direction) {
            this.active = Math.min(this.filtered.length - 1, Math.max(0, this.active + direction));
            this.$nextTick(() => this.$refs.list
                ?.querySelector('[data-active=true]')
                ?.scrollIntoView({ block: 'nearest' }));
        },
    }"
    x-on:keydown.escape.stop="open = false"
    class="w-full"
>
    <flux:input.group>
        {{-- Country-code combobox --}}
        <div @click.outside="open = false" class="relative shrink-0">
            <button
                type="button"
                data-flux-control
                data-flux-group-target
                x-on:click="open ? (open = false) : openPanel()"
                x-bind:aria-expanded="open"
                aria-haspopup="listbox"
                class="flex h-10 w-[7.5rem] items-center gap-1.5 rounded-lg border border-zinc-200 border-b-zinc-300/80 bg-white px-3 text-base shadow-xs sm:text-sm dark:border-white/10 dark:bg-white/10"
            >
                <img
                    x-bind:src="'https://flagcdn.com/' + current.code.toLowerCase() + '.svg'"
                    x-bind:alt="current.name"
                    class="h-3.5 w-5 shrink-0 rounded-[2px] object-cover"
                />
                <span class="font-medium text-zinc-700 dark:text-zinc-300" x-text="current.dial"></span>
                <svg class="ms-auto size-4 shrink-0 text-zinc-400 dark:text-white/60" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                </svg>
            </button>

            <div
                x-show="open"
                x-cloak
                x-transition.origin.top.left
                class="absolute start-0 z-50 mt-1 w-72 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-600 dark:bg-zinc-700"
                role="listbox"
            >
                <div class="relative border-b border-zinc-200 dark:border-zinc-600">
                    <svg class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400 dark:text-white/60" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input
                        x-ref="search"
                        x-model="search"
                        x-on:keydown.down.prevent="move(1)"
                        x-on:keydown.up.prevent="move(-1)"
                        x-on:keydown.enter.prevent="filtered[active] && choose(filtered[active])"
                        type="text"
                        placeholder="{{ $searchPlaceholder }}"
                        class="w-full border-0 bg-transparent py-2 pe-3 ps-9 text-sm text-zinc-700 placeholder-zinc-400 focus:ring-0 dark:text-zinc-200 dark:placeholder-zinc-400"
                    />
                </div>

                <ul x-ref="list" class="max-h-60 overflow-y-auto p-1">
                    <template x-for="(country, index) in filtered" x-bind:key="country.code + country.dial">
                        <li
                            role="option"
                            x-bind:data-active="index === active"
                            x-bind:aria-selected="country.dial === dial"
                            x-on:click="choose(country)"
                            x-on:mousemove="active = index"
                            class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm font-medium text-zinc-800 data-[active=true]:bg-zinc-100 dark:text-white dark:data-[active=true]:bg-zinc-600"
                        >
                            <img
                                x-bind:src="'https://flagcdn.com/' + country.code.toLowerCase() + '.svg'"
                                x-bind:alt="country.name"
                                loading="lazy"
                                class="h-3.5 w-5 shrink-0 rounded-[2px] object-cover"
                            />
                            <span class="flex-1 truncate" x-text="country.name"></span>
                            <span class="text-xs text-zinc-400 dark:text-white/60" x-text="country.dial"></span>
                            <svg x-cloak x-show="country.dial === dial" class="size-4 shrink-0 text-zinc-500 dark:text-zinc-300" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </li>
                    </template>
                    <li x-show="filtered.length === 0" class="px-2 py-3 text-center text-sm text-zinc-400">
                        No matches found
                    </li>
                </ul>
            </div>
        </div>

        {{-- Local number --}}
        <flux:input x-model="local" type="tel" :placeholder="$placeholder" autocomplete="tel" />
    </flux:input.group>
</div>
