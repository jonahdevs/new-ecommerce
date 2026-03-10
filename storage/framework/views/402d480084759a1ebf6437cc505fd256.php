
    <div class="pt-10">
        <?php if (isset($component)) { $__componentOriginale27b9f538b18752a4e62486fb1a784aa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale27b9f538b18752a4e62486fb1a784aa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::skeleton.index','data' => ['animate' => 'shimmer','class' => 'w-44 h-5 mb-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::skeleton'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['animate' => 'shimmer','class' => 'w-44 h-5 mb-4']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale27b9f538b18752a4e62486fb1a784aa)): ?>
<?php $attributes = $__attributesOriginale27b9f538b18752a4e62486fb1a784aa; ?>
<?php unset($__attributesOriginale27b9f538b18752a4e62486fb1a784aa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale27b9f538b18752a4e62486fb1a784aa)): ?>
<?php $component = $__componentOriginale27b9f538b18752a4e62486fb1a784aa; ?>
<?php unset($__componentOriginale27b9f538b18752a4e62486fb1a784aa); ?>
<?php endif; ?>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($i = 1; $i <= 6; $i++): ?>
                <?php if (isset($component)) { $__componentOriginal617f61e5bfd7bca40eb484f2cd6e3a3a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal617f61e5bfd7bca40eb484f2cd6e3a3a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.product-card-placeholder','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('product-card-placeholder'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal617f61e5bfd7bca40eb484f2cd6e3a3a)): ?>
<?php $attributes = $__attributesOriginal617f61e5bfd7bca40eb484f2cd6e3a3a; ?>
<?php unset($__attributesOriginal617f61e5bfd7bca40eb484f2cd6e3a3a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal617f61e5bfd7bca40eb484f2cd6e3a3a)): ?>
<?php $component = $__componentOriginal617f61e5bfd7bca40eb484f2cd6e3a3a; ?>
<?php unset($__componentOriginal617f61e5bfd7bca40eb484f2cd6e3a3a); ?>
<?php endif; ?>
            <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
<?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\storage\framework/views/livewire/placeholders/aa46c081.blade.php ENDPATH**/ ?>