<div wire:cloak wire:show="selectedTab == 'specification'">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($product->technical_specification)): ?>
        <div class="text-xs sm:text-sm text-zinc-500 tracking-wider leading-6">
            <?php echo $product->technical_specification; ?>

        </div>
    <?php else: ?>
        <p class="text-xs sm:text-sm text-zinc-500">No specifications available for this product.</p>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\resources\views/pages/product-details/partials/_specification.blade.php ENDPATH**/ ?>