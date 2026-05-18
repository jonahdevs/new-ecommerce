<?php
// Extract directive's "with" parameter (overrides component properties)
$__islandScope = (function($name = null, $token = null, $lazy = false, $defer = false, $always = false, $skip = false, $with = []) {
    return $with;
})('top-categories');
if (!empty($__islandScope)) {
    extract($__islandScope, EXTR_OVERWRITE);
}

// Extract runtime "with" parameter if provided (overrides everything)
if (isset($__runtimeWith) && is_array($__runtimeWith) && !empty($__runtimeWith)) {
    extract($__runtimeWith, EXTR_OVERWRITE);
}
?>

            <?php if (isset($__placeholder)) { ob_start(); } if (isset($__placeholder)): ?>
                <div
                    class="py-3 pb-5 grid grid-cols-2 @xs/categories:grid-cols-3 @md/categories:grid-cols-4 @2xl/categories:grid-cols-5 @4xl/categories:grid-cols-6 @6xl/categories:grid-cols-7 gap-3">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php for($i = 0; $i < 14; $i++): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div class="animate-pulse">
                            <div class="w-full aspect-4/3 bg-zinc-200 rounded-md"></div>
                            <div class="w-3/4 h-3 sm:h-4 mt-2 bg-zinc-200 mx-auto rounded"></div>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            <?php endif; if (isset($__placeholder)) { echo ob_get_clean(); return; } ?>

            <div
                class="grid grid-cols-2 @xs/categories:grid-cols-3 @md/categories:grid-cols-4 @2xl/categories:grid-cols-5 @4xl/categories:grid-cols-6 @6xl/categories:grid-cols-7 gap-x-3 gap-y-6 @md/categories:gap-x-5 @md/categories:gap-y-10">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->topCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <div class="group relative" :key="'category-' . $category->id">
                        <a href="<?php echo e(route('shop.category', ['category' => $category->slug])); ?>" wire:navigate
                            class="block">
                            <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                'relative aspect-4/3 overflow-hidden rounded-md bg-zinc-50',
                                'border border-zinc-200' => !$category->image_url,
                            ]); ?>">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($category->image_url): ?>
                                    <img src="<?php echo e($category->image_url); ?>" alt="<?php echo e($category->name); ?>" loading="lazy"
                                        class="object-cover w-full h-full">
                                <?php else: ?>
                                    <div class="flex items-center justify-center h-full">
                                        <?php if (isset($component)) { $__componentOriginal2d7605e1adbee8a1737ebec29a91da61 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2d7605e1adbee8a1737ebec29a91da61 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.photo','data' => ['class' => 'text-zinc-300 h-10 w-10 stroke-1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.photo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-zinc-300 h-10 w-10 stroke-1']); ?>
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
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            <p class="mt-5 text-center text-sm font-semibold">
                                <?php echo e($category->name); ?>

                            </p>
                        </a>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        <?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\storage\framework/views/livewire/islands/d9ca7788-1.blade.php ENDPATH**/ ?>