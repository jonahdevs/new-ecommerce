{{-- Price filter — dual-thumb range slider + min/max number inputs.
     Expects the host Livewire component to expose int $priceMin and $priceMax
     (whole KES units). Local Alpine state drives the UI and only commits to
     Livewire on release/change to avoid a request per drag tick. --}}
@php
    $absMin = 0;
    $absMax = 6000000;
    $step = 50000;
    $symbol = app(\App\Settings\CurrencySettings::class)->symbol;
@endphp
<div class="px-5 py-4" wire:ignore x-data="{
    absMin: {{ $absMin }},
    absMax: {{ $absMax }},
    step: {{ $step }},
    symbol: @js($symbol),
    min: {{ $priceMin }},
    max: {{ $priceMax }},
    get dirty() { return this.min > this.absMin || this.max < this.absMax; },
    pct(v) { return ((v - this.absMin) / (this.absMax - this.absMin)) * 100; },
    fmt(v) { return this.symbol + ' ' + Number(v).toLocaleString(); },
    clamp() {
        this.min = Math.min(Math.max(this.min || this.absMin, this.absMin), this.absMax);
        this.max = Math.min(Math.max(this.max || this.absMax, this.absMin), this.absMax);
        if (this.min > this.max) { [this.min, this.max] = [this.max, this.min]; }
    },
    commit() {
        this.clamp();
        $wire.set('priceMin', this.min, false);
        $wire.set('priceMax', this.max);
    },
    reset() {
        this.min = this.absMin;
        this.max = this.absMax;
        this.commit();
    },
}" x-init="
    {{-- wire:ignore isolates this Alpine widget from Livewire DOM morphs (so the
         active-range fill stops sticking on stale values after a commit); these
         watchers keep it in sync when filters are reset from outside the slider. --}}
    $wire.$watch('priceMin', value => { min = Number(value); });
    $wire.$watch('priceMax', value => { max = Number(value); });
">
    <div class="mb-3 flex items-center justify-between">
        <div class="text-[12px] font-bold uppercase tracking-[0.08em] text-ink-2">Price</div>
        <button type="button" x-show="dirty" x-cloak @click="reset()"
            class="cursor-pointer text-[11.5px] font-medium text-brand-500 hover:underline">Reset</button>
    </div>

    {{-- Readout --}}
    <div class="mb-3 flex justify-between text-[12.5px] text-ink-3">
        <span x-text="fmt(min)"></span>
        <span class="font-semibold text-ink" x-text="fmt(max)"></span>
    </div>

    {{-- Dual-thumb slider --}}
    <div class="relative h-5">
        <div class="pointer-events-none absolute top-1/2 right-0 left-0 h-1 -translate-y-1/2 rounded-full bg-zinc-200">
        </div>
        <div class="pointer-events-none absolute top-1/2 h-1 -translate-y-1/2 rounded-full bg-brand-500"
            :style="`left: ${pct(min)}%; right: ${100 - pct(max)}%`"></div>

        <input type="range" :min="absMin" :max="absMax" :step="step" x-model.number="min"
            @input="if (min > max) min = max" @change="commit()" aria-label="Minimum price"
            class="price-thumb absolute inset-0 h-5 w-full appearance-none bg-transparent" />
        <input type="range" :min="absMin" :max="absMax" :step="step" x-model.number="max"
            @input="if (max < min) max = min" @change="commit()" aria-label="Maximum price"
            class="price-thumb absolute inset-0 h-5 w-full appearance-none bg-transparent" />
    </div>

    {{-- Number inputs --}}
    <div class="mt-4 flex items-end gap-2">
        <div class="flex-1">
            <label class="mb-1 block text-[11px] text-ink-4">Min</label>
            <input type="number" inputmode="numeric" :min="absMin" :max="absMax" :step="step"
                x-model.number="min" @change="commit()"
                class="w-full rounded border border-zinc-300 px-2 py-1.5 text-[13px] tabular-nums focus:border-brand-500 focus:ring-0 focus:outline-none" />
        </div>
        <span class="pb-2 text-ink-4">—</span>
        <div class="flex-1">
            <label class="mb-1 block text-[11px] text-ink-4">Max</label>
            <input type="number" inputmode="numeric" :min="absMin" :max="absMax" :step="step"
                x-model.number="max" @change="commit()"
                class="w-full rounded border border-zinc-300 px-2 py-1.5 text-[13px] tabular-nums focus:border-brand-500 focus:ring-0 focus:outline-none" />
        </div>
    </div>

    {{-- Dual-range needs the input element click-through, with only the thumbs interactive,
         so both thumbs stay independently draggable. Inline so it works without a rebuild. --}}
    <style>
        .price-thumb {
            pointer-events: none;
        }

        .price-thumb::-webkit-slider-thumb {
            pointer-events: auto;
            -webkit-appearance: none;
            appearance: none;
            height: 1rem;
            width: 1rem;
            border-radius: 9999px;
            background: #fff;
            border: 2px solid var(--color-brand-500);
            box-shadow: 0 1px 2px rgb(0 0 0 / 0.15);
            cursor: pointer;
        }

        .price-thumb::-moz-range-thumb {
            pointer-events: auto;
            height: 1rem;
            width: 1rem;
            border-radius: 9999px;
            background: #fff;
            border: 2px solid var(--color-brand-500);
            box-shadow: 0 1px 2px rgb(0 0 0 / 0.15);
            cursor: pointer;
        }

        .price-thumb::-moz-range-track {
            background: transparent;
        }
    </style>
</div>
