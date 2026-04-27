


<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($suggestions['categories'])): ?>
    <div class="px-4 pt-3 pb-2 flex flex-wrap gap-2">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $suggestions['categories']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <a href="<?php echo e(route('shop.category', ['category' => $category['slug']])); ?>" wire:navigate
                <?php if($mobileOpen): ?> wire:click="closeMobile" <?php endif; ?>
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium
                    bg-zinc-100 text-zinc-600 hover:bg-brand-secondary hover:text-white
                    border border-zinc-200 hover:border-brand-secondary transition-colors duration-150">
                <?php if (isset($component)) { $__componentOriginal9d0b0e731f78867d90e05462d3b99106 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9d0b0e731f78867d90e05462d3b99106 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.squares-2x2','data' => ['class' => 'w-3 h-3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.squares-2x2'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-3 h-3']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9d0b0e731f78867d90e05462d3b99106)): ?>
<?php $attributes = $__attributesOriginal9d0b0e731f78867d90e05462d3b99106; ?>
<?php unset($__attributesOriginal9d0b0e731f78867d90e05462d3b99106); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9d0b0e731f78867d90e05462d3b99106)): ?>
<?php $component = $__componentOriginal9d0b0e731f78867d90e05462d3b99106; ?>
<?php unset($__componentOriginal9d0b0e731f78867d90e05462d3b99106); ?>
<?php endif; ?>
                <?php echo e($category['name']); ?>

                <span class="opacity-60">(<?php echo e($category['products_count']); ?>)</span>
            </a>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    </div>
    <div class="mx-4 border-t border-zinc-100"></div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($suggestions['products'])): ?>
    <div class="py-1">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $suggestions['products']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <a href="<?php echo e(route('products.show', $product['slug'])); ?>" wire:navigate
                <?php if($mobileOpen): ?> wire:click="closeMobile" <?php endif; ?>
                class="flex items-center gap-3 px-4 py-2.5 hover:bg-zinc-50 transition-colors group">

                
                <div class="shrink-0 w-10 h-10 rounded-md border border-zinc-100 bg-zinc-50 overflow-hidden">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product['image']): ?>
                        <img src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>"
                            class="w-full h-full object-contain" loading="lazy">
                    <?php else: ?>
                        <?php if (isset($component)) { $__componentOriginal2d7605e1adbee8a1737ebec29a91da61 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2d7605e1adbee8a1737ebec29a91da61 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.photo','data' => ['class' => 'w-full h-full p-2 text-zinc-300 stroke-1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.photo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-full h-full p-2 text-zinc-300 stroke-1']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2d7605e1adbee8a1737ebec29a91da61)): ?>
<?php $attributes = $__attributesOriginal2d7605e1adbee8a1737ebec29a91da61; ?>
<?php unset($__attributesOriginal2d7605e1adbee8a1737ebec29a91da61); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2d7605e1adbee8a1737ebec29a91da61)): ?>
<?php $component = $__componentOriginal2d7605e1adbee8a1737ebec29a91da61; ?>
<?php unset($__componentOriginal2d7605e1adbee8a1737ebec29a91da61); ?>
<?php endif; ?>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                <div class="flex-1 min-w-0">
                    <p
                        class="text-sm font-medium text-zinc-800 truncate group-hover:text-brand-secondary transition-colors">
                        <?php echo e($product['name']); ?>

                    </p>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product['category']): ?>
                        <p class="text-xs text-zinc-400 mt-0.5 truncate">
                            <?php echo e($product['category']); ?>

                        </p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <?php if (isset($component)) { $__componentOriginal31cb76c8d087d4f00797aeea7232b4c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal31cb76c8d087d4f00797aeea7232b4c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.chevron-right','data' => ['class' => 'size-4 text-zinc-300 group-hover:text-brand-secondary group-hover:translate-x-0.5 transition-all duration-150 shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.chevron-right'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4 text-zinc-300 group-hover:text-brand-secondary group-hover:translate-x-0.5 transition-all duration-150 shrink-0']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal31cb76c8d087d4f00797aeea7232b4c3)): ?>
<?php $attributes = $__attributesOriginal31cb76c8d087d4f00797aeea7232b4c3; ?>
<?php unset($__attributesOriginal31cb76c8d087d4f00797aeea7232b4c3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal31cb76c8d087d4f00797aeea7232b4c3)): ?>
<?php $component = $__componentOriginal31cb76c8d087d4f00797aeea7232b4c3; ?>
<?php unset($__componentOriginal31cb76c8d087d4f00797aeea7232b4c3); ?>
<?php endif; ?>
            </a>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($suggestions['products'])): ?>
    <div class="border-t border-zinc-100 px-4 py-2.5">
        <a href="<?php echo e(route('shop.index')); ?>?search=<?php echo e(urlencode($search)); ?>" wire:navigate
            <?php if($mobileOpen): ?> wire:click="closeMobile" <?php endif; ?>
            class="flex items-center justify-between text-xs font-medium text-brand-secondary hover:underline">
            <span>See all results for "<?php echo e($search); ?>"</span>
            <?php if (isset($component)) { $__componentOriginal5c84e1af936cb00c34687173a7f14ca8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c84e1af936cb00c34687173a7f14ca8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.arrow-right','data' => ['class' => 'w-3.5 h-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.arrow-right'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-3.5 h-3.5']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5c84e1af936cb00c34687173a7f14ca8)): ?>
<?php $attributes = $__attributesOriginal5c84e1af936cb00c34687173a7f14ca8; ?>
<?php unset($__attributesOriginal5c84e1af936cb00c34687173a7f14ca8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5c84e1af936cb00c34687173a7f14ca8)): ?>
<?php $component = $__componentOriginal5c84e1af936cb00c34687173a7f14ca8; ?>
<?php unset($__componentOriginal5c84e1af936cb00c34687173a7f14ca8); ?>
<?php endif; ?>
        </a>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showSuggestions && empty($suggestions['products']) && empty($suggestions['categories'])): ?>
    <div class="px-4 py-10 text-center">
        <?php if (isset($component)) { $__componentOriginalc3d062a579167d374258253d48d4177f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc3d062a579167d374258253d48d4177f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.magnifying-glass','data' => ['class' => 'w-8 h-8 text-zinc-300 mx-auto mb-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.magnifying-glass'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-8 h-8 text-zinc-300 mx-auto mb-2']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc3d062a579167d374258253d48d4177f)): ?>
<?php $attributes = $__attributesOriginalc3d062a579167d374258253d48d4177f; ?>
<?php unset($__attributesOriginalc3d062a579167d374258253d48d4177f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc3d062a579167d374258253d48d4177f)): ?>
<?php $component = $__componentOriginalc3d062a579167d374258253d48d4177f; ?>
<?php unset($__componentOriginalc3d062a579167d374258253d48d4177f); ?>
<?php endif; ?>
        <p class="text-sm font-medium text-zinc-500">No results for "<?php echo e($search); ?>"</p>
        <p class="text-xs text-zinc-400 mt-1">Try a different keyword or browse all products</p>
        <a href="<?php echo e(route('shop.index')); ?>" wire:navigate
            class="inline-flex items-center gap-1.5 mt-4 text-xs font-medium text-brand-secondary hover:underline">
            Browse all products
            <?php if (isset($component)) { $__componentOriginal5c84e1af936cb00c34687173a7f14ca8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c84e1af936cb00c34687173a7f14ca8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.arrow-right','data' => ['class' => 'w-3.5 h-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.arrow-right'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-3.5 h-3.5']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5c84e1af936cb00c34687173a7f14ca8)): ?>
<?php $attributes = $__attributesOriginal5c84e1af936cb00c34687173a7f14ca8; ?>
<?php unset($__attributesOriginal5c84e1af936cb00c34687173a7f14ca8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5c84e1af936cb00c34687173a7f14ca8)): ?>
<?php $component = $__componentOriginal5c84e1af936cb00c34687173a7f14ca8; ?>
<?php unset($__componentOriginal5c84e1af936cb00c34687173a7f14ca8); ?>
<?php endif; ?>
        </a>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\resources\views/partials/search-suggestions.blade.php ENDPATH**/ ?>