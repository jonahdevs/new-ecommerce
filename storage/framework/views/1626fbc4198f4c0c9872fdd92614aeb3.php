<?php
// Extract directive's "with" parameter (overrides component properties)
$__islandScope = (function($name = null, $token = null, $lazy = false, $defer = false, $always = false, $skip = false, $with = []) {
    return $with;
})('reviews');
if (!empty($__islandScope)) {
    extract($__islandScope, EXTR_OVERWRITE);
}

// Extract runtime "with" parameter if provided (overrides everything)
if (isset($__runtimeWith) && is_array($__runtimeWith) && !empty($__runtimeWith)) {
    extract($__runtimeWith, EXTR_OVERWRITE);
}
?>
<?php if (isset($__placeholder)) { echo $__placeholder; return; } ?>


                <div class="flex items-center gap-2">
                    <?php $avgRating = $product->reviews_avg_rating ?? 0; ?>
                    <div class="flex items-center gap-0.5">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php for($i = 0; $i < 5; $i++): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($avgRating >= $i + 1): ?>
                                <?php if (isset($component)) { $__componentOriginal0bc6ca59f258b8d2577c76df279598af = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0bc6ca59f258b8d2577c76df279598af = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.star','data' => ['variant' => 'solid','class' => 'w-4 h-4 text-yellow-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.star'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'solid','class' => 'w-4 h-4 text-yellow-400']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0bc6ca59f258b8d2577c76df279598af)): ?>
<?php $attributes = $__attributesOriginal0bc6ca59f258b8d2577c76df279598af; ?>
<?php unset($__attributesOriginal0bc6ca59f258b8d2577c76df279598af); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0bc6ca59f258b8d2577c76df279598af)): ?>
<?php $component = $__componentOriginal0bc6ca59f258b8d2577c76df279598af; ?>
<?php unset($__componentOriginal0bc6ca59f258b8d2577c76df279598af); ?>
<?php endif; ?>
                            <?php elseif($avgRating > $i): ?>
                                <div class="relative w-4 h-4">
                                    <?php if (isset($component)) { $__componentOriginal0bc6ca59f258b8d2577c76df279598af = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0bc6ca59f258b8d2577c76df279598af = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.star','data' => ['variant' => 'solid','class' => 'w-4 h-4 text-zinc-300']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.star'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'solid','class' => 'w-4 h-4 text-zinc-300']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0bc6ca59f258b8d2577c76df279598af)): ?>
<?php $attributes = $__attributesOriginal0bc6ca59f258b8d2577c76df279598af; ?>
<?php unset($__attributesOriginal0bc6ca59f258b8d2577c76df279598af); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0bc6ca59f258b8d2577c76df279598af)): ?>
<?php $component = $__componentOriginal0bc6ca59f258b8d2577c76df279598af; ?>
<?php unset($__componentOriginal0bc6ca59f258b8d2577c76df279598af); ?>
<?php endif; ?>
                                    <div class="absolute inset-0 overflow-hidden w-1/2">
                                        <?php if (isset($component)) { $__componentOriginal0bc6ca59f258b8d2577c76df279598af = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0bc6ca59f258b8d2577c76df279598af = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.star','data' => ['variant' => 'solid','class' => 'w-4 h-4 text-yellow-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.star'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'solid','class' => 'w-4 h-4 text-yellow-400']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0bc6ca59f258b8d2577c76df279598af)): ?>
<?php $attributes = $__attributesOriginal0bc6ca59f258b8d2577c76df279598af; ?>
<?php unset($__attributesOriginal0bc6ca59f258b8d2577c76df279598af); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0bc6ca59f258b8d2577c76df279598af)): ?>
<?php $component = $__componentOriginal0bc6ca59f258b8d2577c76df279598af; ?>
<?php unset($__componentOriginal0bc6ca59f258b8d2577c76df279598af); ?>
<?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php if (isset($component)) { $__componentOriginal0bc6ca59f258b8d2577c76df279598af = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0bc6ca59f258b8d2577c76df279598af = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.star','data' => ['variant' => 'solid','class' => 'w-4 h-4 text-zinc-300']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.star'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'solid','class' => 'w-4 h-4 text-zinc-300']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0bc6ca59f258b8d2577c76df279598af)): ?>
<?php $attributes = $__attributesOriginal0bc6ca59f258b8d2577c76df279598af; ?>
<?php unset($__attributesOriginal0bc6ca59f258b8d2577c76df279598af); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0bc6ca59f258b8d2577c76df279598af)): ?>
<?php $component = $__componentOriginal0bc6ca59f258b8d2577c76df279598af; ?>
<?php unset($__componentOriginal0bc6ca59f258b8d2577c76df279598af); ?>
<?php endif; ?>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                    <span class="text-xs sm:text-sm text-zinc-500">(<?php echo e(number_format($avgRating, 1)); ?>)</span>
                    <a href="<?php echo e(route('products.reviews', $product)); ?>" wire:navigate
                        class="text-xs sm:text-sm text-secondary hover:underline">
                        <?php echo e($this->reviewStats['total']); ?> reviews
                    </a>
                </div>
            <?php /**PATH C:\Users\jonah\Herd\sheffield_ecommerce\storage\framework/views/livewire/islands/20797e43-1.blade.php ENDPATH**/ ?>