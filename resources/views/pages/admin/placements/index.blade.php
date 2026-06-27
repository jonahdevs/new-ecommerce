<?php

use App\Enums\CategorySection;
use App\Enums\CategoryStatus;
use App\Models\CategoryPlacement;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Placements | Admin')] class extends Component
{
    /**
     * For each CategorySection case: total count + first 5 active category thumbnails.
     *
     * @return Collection<int, array{section: CategorySection, total: int, previews: Collection}>
     */
    #[Computed]
    public function sections(): Collection
    {
        $totals = CategoryPlacement::selectRaw('location, count(*) as total')
            ->groupBy('location')
            ->pluck('total', 'location');

        $previews = CategoryPlacement::with('category.media')
            ->where('status', CategoryStatus::ACTIVE)
            ->orderBy('sort_order')
            ->get()
            ->groupBy(fn (CategoryPlacement $p) => $p->location->value);

        return collect(CategorySection::cases())->map(fn (CategorySection $section) => [
            'section' => $section,
            'total' => (int) ($totals[$section->value] ?? 0),
            'previews' => ($previews[$section->value] ?? collect())->take(4),
        ]);
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.categories.index')" wire:navigate>Categories</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Placements</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div>
        <flux:heading size="xl">Placements</flux:heading>
        <flux:subheading>Manage which categories appear in each storefront section.</flux:subheading>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($this->sections as $item)
            @php /** @var App\Enums\CategorySection $section */ $section = $item['section']; @endphp
            <flux:card class="flex flex-col justify-between">
                <div>
                    {{-- Card header: icon + label left, stacked thumbnails right --}}
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                <flux:icon :name="$section->icon()" variant="outline" class="size-5 text-zinc-500 dark:text-zinc-400" />
                            </div>
                            <flux:heading size="lg">{{ $section->label() }}</flux:heading>
                        </div>

                        @if ($item['previews']->isNotEmpty())
                            <div class="flex shrink-0 -space-x-2">
                                @foreach ($item['previews'] as $placement)
                                    @if ($placement->category?->image_thumb_url)
                                        <img src="{{ $placement->category->image_thumb_url }}"
                                            alt="{{ $placement->category->name }}"
                                            title="{{ $placement->category->name }}"
                                            class="size-8 rounded-full border-2 border-white object-cover dark:border-zinc-900" />
                                    @else
                                        <div class="flex size-8 items-center justify-center rounded-full border-2 border-white bg-zinc-100 dark:border-zinc-900 dark:bg-zinc-800">
                                            <flux:icon.photo variant="micro" class="size-3.5 text-zinc-400" />
                                        </div>
                                    @endif
                                @endforeach

                                @if ($item['total'] > 4)
                                    <div class="flex size-8 items-center justify-center rounded-full border-2 border-white bg-zinc-200 text-[11px] font-semibold text-zinc-600 dark:border-zinc-900 dark:bg-zinc-700 dark:text-zinc-300">
                                        +{{ $item['total'] - 4 }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Stats --}}
                    <div class="mt-4 space-y-1.5 text-sm text-zinc-500">
                        <div class="flex items-center gap-2">
                            <flux:icon.squares-2x2 variant="micro" class="size-4 shrink-0" />
                            {{ $item['total'] }} {{ str('category')->plural($item['total']) }}
                        </div>
                        <div class="flex items-start gap-2">
                            <flux:icon.information-circle variant="micro" class="mt-px size-4 shrink-0" />
                            {{ $section->description() }}
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="mt-5 flex items-center justify-between border-t border-zinc-200 pt-3 dark:border-zinc-700">
                    <a href="{{ route('admin.placements.edit', $section->value) }}" wire:navigate
                        class="text-sm font-medium text-brand-500 underline-offset-4 hover:underline">
                        Manage
                    </a>
                    <flux:badge size="sm" :color="$item['total'] > 0 ? 'green' : 'zinc'">
                        {{ $item['total'] > 0 ? 'Active' : 'Empty' }}
                    </flux:badge>
                </div>
            </flux:card>
        @endforeach
    </div>
</div>
