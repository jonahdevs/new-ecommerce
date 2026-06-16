<?php

use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;

new #[Layout('layouts::app')] #[Title('Permissions — Admin')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterGroup = '';

    #[Url]
    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterGroup(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function permissions()
    {
        return Permission::query()
            ->with('roles:id,name')
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->filterGroup, fn ($q) => $q->where('name', 'like', $this->filterGroup.'.%'))
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    /** @return \Illuminate\Support\Collection<int, string> */
    #[Computed]
    public function groups(): \Illuminate\Support\Collection
    {
        return Permission::orderBy('name')->pluck('name')
            ->map(fn ($name) => Str::before($name, '.'))
            ->unique()
            ->values();
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            @push('breadcrumbs')
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>Permissions</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            @endpush
            <flux:heading size="xl">Permissions</flux:heading>
            <flux:subheading>Capabilities defined in code and assigned to roles.</flux:subheading>
        </div>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search permissions…"
                icon="magnifying-glass"
                clearable
                class="max-w-xs" />

            <div class="flex items-center gap-2">
                <flux:select wire:model.live="filterGroup" class="w-44">
                    <flux:select.option value="">All groups</flux:select.option>
                    @foreach ($this->groups as $group)
                        <flux:select.option value="{{ $group }}">{{ Str::headline($group) }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="perPage" class="w-28">
                    <flux:select.option value="10">10 / page</flux:select.option>
                    <flux:select.option value="25">25 / page</flux:select.option>
                    <flux:select.option value="50">50 / page</flux:select.option>
                    <flux:select.option value="100">100 / page</flux:select.option>
                </flux:select>
            </div>
        </div>

        <flux:table
            container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column>Permission</flux:table.column>
                <flux:table.column>Group</flux:table.column>
                <flux:table.column>Assigned to</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->permissions as $permission)
                    <flux:table.row :key="$permission->id">
                        <flux:table.cell variant="strong">
                            {{ Str::headline(Str::after($permission->name, '.')) }}
                            <span class="block font-mono text-xs font-normal text-zinc-400">{{ $permission->name }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" inset="top bottom" color="zinc">
                                {{ Str::headline(Str::before($permission->name, '.')) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @php
                                $shown = $permission->roles->take(3);
                                $remaining = $permission->roles->count() - $shown->count();
                            @endphp
                            @if ($permission->roles->isEmpty())
                                <span class="text-xs text-zinc-400">—</span>
                            @else
                                <div class="flex flex-wrap items-center gap-1">
                                    @foreach ($shown as $role)
                                        <flux:badge size="sm" inset="top bottom" color="zinc">{{ Str::headline($role->name) }}</flux:badge>
                                    @endforeach
                                    @if ($remaining > 0)
                                        <flux:badge size="sm" inset="top bottom" color="zinc">+{{ $remaining }}</flux:badge>
                                    @endif
                                </div>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="3" class="py-12 text-center text-zinc-400">
                            No permissions found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->permissions->hasPages())
            <div class="border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:pagination :paginator="$this->permissions" />
            </div>
        @endif
    </flux:card>
</div>
