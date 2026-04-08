<div wire:cloak wire:show="selectedTab == 'specification'">
    @if (!empty($product->technical_specification))
        <div class="text-sm text-zinc-500 tracking-wider leading-6">
            {!! $product->technical_specification !!}
        </div>
    @else
        <p class="text-sm text-zinc-500">No specifications available for this product.</p>
    @endif
</div>
