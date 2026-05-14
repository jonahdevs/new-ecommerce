
    <?php if (isset($component)) { $__componentOriginal06cb810e41dccc8469f1dcd1d389b3ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal06cb810e41dccc8469f1dcd1d389b3ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.review-item-placeholder','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('review-item-placeholder'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal06cb810e41dccc8469f1dcd1d389b3ee)): ?>
<?php $attributes = $__attributesOriginal06cb810e41dccc8469f1dcd1d389b3ee; ?>
<?php unset($__attributesOriginal06cb810e41dccc8469f1dcd1d389b3ee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal06cb810e41dccc8469f1dcd1d389b3ee)): ?>
<?php $component = $__componentOriginal06cb810e41dccc8469f1dcd1d389b3ee; ?>
<?php unset($__componentOriginal06cb810e41dccc8469f1dcd1d389b3ee); ?>
<?php endif; ?>
<?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\storage\framework/views/livewire/placeholders/2379c022.blade.php ENDPATH**/ ?>