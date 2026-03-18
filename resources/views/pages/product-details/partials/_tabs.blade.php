<flux:card class="pb-6 relative pt-10 px-6 mt-10">

    {{-- Tab Buttons --}}
    <div class="flex items-center gap-2 absolute top-0 left-0 -translate-y-1/2 rounded-b-sm rounded-tr-sm">

        {{-- Description --}}
        <flux:button x-show="$wire.selectedTab == 'description'" @click="$wire.selectedTab = 'description'"
            variant="primary" class="rounded-none cursor-pointer">
            Description
        </flux:button>
        <flux:button x-cloak x-show="$wire.selectedTab !== 'description'" @click="$wire.selectedTab = 'description'"
            class="rounded-none cursor-pointer">
            Description
        </flux:button>

        {{-- Specification --}}
        <flux:button x-cloak x-show="$wire.selectedTab == 'specification'" @click="$wire.selectedTab = 'specification'"
            variant="primary" class="rounded-none cursor-pointer">
            Specification
        </flux:button>
        <flux:button x-show="$wire.selectedTab !== 'specification'" @click="$wire.selectedTab = 'specification'"
            class="rounded-none cursor-pointer">
            Specification
        </flux:button>

        @if ($this->product->reviews_enabled)
            {{-- Reviews --}}
            <flux:button x-cloak x-show="$wire.selectedTab == 'reviews'" @click="$wire.selectedTab = 'reviews'"
                variant="primary" class="rounded-none cursor-pointer">
                Reviews
            </flux:button>
            <flux:button x-show="$wire.selectedTab !== 'reviews'" @click="$wire.selectedTab = 'reviews'"
                class="rounded-none cursor-pointer">
                Reviews
            </flux:button>
        @endif

    </div>

    {{-- Tab Content --}}
    @include('pages.product-details.partials._description')
    @include('pages.product-details.partials._specification')
    @include('pages.product-details.partials._reviews')

</flux:card>
