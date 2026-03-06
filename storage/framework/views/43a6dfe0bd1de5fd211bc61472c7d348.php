<?php

use Livewire\Component;
use App\Models\Order;
use Livewire\Attributes\{Layout, Computed, Title};

new #[Title('Order Details')] #[Layout('layouts.customer')] class extends Component {
    public Order $order;
};
?>

<div>
    <?php if (isset($component)) { $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::card.index','data' => ['class' => 'rounded-md p-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'rounded-md p-0']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <div class="flex items-center gap-3 px-3 py-2 border-b">
            <?php if (isset($component)) { $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button.index','data' => ['size' => 'xs','icon' => 'arrow-long-left','variant' => 'ghost','class' => 'cursor-pointer','href' => route('customer.orders.show', $order),'wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'xs','icon' => 'arrow-long-left','variant' => 'ghost','class' => 'cursor-pointer','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('customer.orders.show', $order)),'wire:navigate' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580)): ?>
<?php $attributes = $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580; ?>
<?php unset($__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc04b147acd0e65cc1a77f86fb0e81580)): ?>
<?php $component = $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580; ?>
<?php unset($__componentOriginalc04b147acd0e65cc1a77f86fb0e81580); ?>
<?php endif; ?>

            <?php if (isset($component)) { $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::heading','data' => ['size' => 'lg']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'lg']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Package History <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $attributes = $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $component = $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
        </div>

        <div class="p-5 px-8">
            <?php if (isset($component)) { $__componentOriginal391884c4ca6d532956b8de0774c8c673 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal391884c4ca6d532956b8de0774c8c673 = $attributes; } ?>
<?php $component = Mary\View\Components\TimelineItem::resolve(['title' => 'Order placed','first' => true,'icon' => 'o-map-pin'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('my-timeline-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Mary\View\Components\TimelineItem::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal391884c4ca6d532956b8de0774c8c673)): ?>
<?php $attributes = $__attributesOriginal391884c4ca6d532956b8de0774c8c673; ?>
<?php unset($__attributesOriginal391884c4ca6d532956b8de0774c8c673); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal391884c4ca6d532956b8de0774c8c673)): ?>
<?php $component = $__componentOriginal391884c4ca6d532956b8de0774c8c673; ?>
<?php unset($__componentOriginal391884c4ca6d532956b8de0774c8c673); ?>
<?php endif; ?>

            <?php if (isset($component)) { $__componentOriginal391884c4ca6d532956b8de0774c8c673 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal391884c4ca6d532956b8de0774c8c673 = $attributes; } ?>
<?php $component = Mary\View\Components\TimelineItem::resolve(['title' => 'Payment confirmed','icon' => 'o-credit-card'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('my-timeline-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Mary\View\Components\TimelineItem::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal391884c4ca6d532956b8de0774c8c673)): ?>
<?php $attributes = $__attributesOriginal391884c4ca6d532956b8de0774c8c673; ?>
<?php unset($__attributesOriginal391884c4ca6d532956b8de0774c8c673); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal391884c4ca6d532956b8de0774c8c673)): ?>
<?php $component = $__componentOriginal391884c4ca6d532956b8de0774c8c673; ?>
<?php unset($__componentOriginal391884c4ca6d532956b8de0774c8c673); ?>
<?php endif; ?>

            <?php if (isset($component)) { $__componentOriginal391884c4ca6d532956b8de0774c8c673 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal391884c4ca6d532956b8de0774c8c673 = $attributes; } ?>
<?php $component = Mary\View\Components\TimelineItem::resolve(['title' => 'Shipped','icon' => 'o-paper-airplane'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('my-timeline-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Mary\View\Components\TimelineItem::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal391884c4ca6d532956b8de0774c8c673)): ?>
<?php $attributes = $__attributesOriginal391884c4ca6d532956b8de0774c8c673; ?>
<?php unset($__attributesOriginal391884c4ca6d532956b8de0774c8c673); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal391884c4ca6d532956b8de0774c8c673)): ?>
<?php $component = $__componentOriginal391884c4ca6d532956b8de0774c8c673; ?>
<?php unset($__componentOriginal391884c4ca6d532956b8de0774c8c673); ?>
<?php endif; ?>

            <?php if (isset($component)) { $__componentOriginal391884c4ca6d532956b8de0774c8c673 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal391884c4ca6d532956b8de0774c8c673 = $attributes; } ?>
<?php $component = Mary\View\Components\TimelineItem::resolve(['title' => 'Delivered','pending' => true,'last' => true,'icon' => 'o-gift'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('my-timeline-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Mary\View\Components\TimelineItem::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal391884c4ca6d532956b8de0774c8c673)): ?>
<?php $attributes = $__attributesOriginal391884c4ca6d532956b8de0774c8c673; ?>
<?php unset($__attributesOriginal391884c4ca6d532956b8de0774c8c673); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal391884c4ca6d532956b8de0774c8c673)): ?>
<?php $component = $__componentOriginal391884c4ca6d532956b8de0774c8c673; ?>
<?php unset($__componentOriginal391884c4ca6d532956b8de0774c8c673); ?>
<?php endif; ?>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $attributes = $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $component = $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>
</div>
<?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\resources\views\pages\customer\orders\tracking.blade.php ENDPATH**/ ?>