<?php if (isset($component)) { $__componentOriginalf805d1519e62e51560a2790be1c43181 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf805d1519e62e51560a2790be1c43181 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'f4ac99e09542ff494432bc959d4fee61::guest','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts::guest'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <div class="bg-white border-b border-zinc-200 py-3">
        <?php echo e($breadcrumbs ?? ''); ?>

    </div>

    <div class="mx-auto container px-4 py-4 min-h-[80svh]">
        <?php if (isset($component)) { $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::heading','data' => ['level' => '1','class' => 'text-2xl! font-bold! font-serif']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['level' => '1','class' => 'text-2xl! font-bold! font-serif']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <?php echo e($heading ?? 'Checkout'); ?>

         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $attributes = $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $component = $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>

        <div class="mt-4 flex flex-col lg:flex-row lg:items-start lg:gap-6">

            
            <div class="flex-1 min-w-0">
                <?php echo e($slot); ?>

            </div>

            
            <div class="w-full lg:w-96 shrink-0 mt-4 lg:mt-0 lg:sticky lg:top-28">
                <?php if (isset($__component)) { $__componentOriginal = $__component; } ?>
<?php if (isset($__key)) { $__keyOriginal = $__key; } ?>
<?php if (isset($__attributes)) { $__attributesOriginal = $__attributes; } ?>
<?php if (isset($__slots)) { $__slotsOriginal = $__slots; } ?>
<?php $__component = 'order-summary'; ?>
<?php $__key = null; ?>
<?php $__attributes = []; ?>
<?php $__slots = []; ?>
<?php ob_start(); ?>

                    <?php echo e($orderSummaryCta ?? ''); ?>

                <?php $__slots['default'] = ob_get_clean(); ?>
<?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split($__component, $__attributes);

$__keyOuter = $__key ?? null;

$__key = $__key;
$__componentSlots = $__slots ?? [];

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-3996986761-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key, $__componentSlots);

echo $__html;

unset($__html);
unset($__key);
$__key = $__keyOuter;
unset($__keyOuter);
unset($__name);
unset($__params);
unset($__componentSlots);
unset($__split);
?>
<?php if (isset($__componentOriginal)) { $__component = $__componentOriginal; unset($__componentOriginal); } ?>
<?php if (isset($__keyOriginal)) { $__key = $__keyOriginal; unset($__keyOriginal); } ?>
<?php if (isset($__attributesOriginal)) { $__attributes = $__attributesOriginal; unset($__attributesOriginal); } ?>
<?php if (isset($__slotsOriginal)) { $__slots = $__slotsOriginal; unset($__slotsOriginal); } ?>

            </div>

        </div>
    </div>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf805d1519e62e51560a2790be1c43181)): ?>
<?php $attributes = $__attributesOriginalf805d1519e62e51560a2790be1c43181; ?>
<?php unset($__attributesOriginalf805d1519e62e51560a2790be1c43181); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf805d1519e62e51560a2790be1c43181)): ?>
<?php $component = $__componentOriginalf805d1519e62e51560a2790be1c43181; ?>
<?php unset($__componentOriginalf805d1519e62e51560a2790be1c43181); ?>
<?php endif; ?>
<?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\resources\views/layouts/checkout.blade.php ENDPATH**/ ?>