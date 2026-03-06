<?php if (isset($component)) { $__componentOriginalbfb9265e32d6b1aaed2eb5e0fa443a01 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfb9265e32d6b1aaed2eb5e0fa443a01 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'f4ac99e09542ff494432bc959d4fee61::auth.modern','data' => ['title' => $title ?? null]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts::auth.modern'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($title ?? null)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <?php echo e($slot); ?>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbfb9265e32d6b1aaed2eb5e0fa443a01)): ?>
<?php $attributes = $__attributesOriginalbfb9265e32d6b1aaed2eb5e0fa443a01; ?>
<?php unset($__attributesOriginalbfb9265e32d6b1aaed2eb5e0fa443a01); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbfb9265e32d6b1aaed2eb5e0fa443a01)): ?>
<?php $component = $__componentOriginalbfb9265e32d6b1aaed2eb5e0fa443a01; ?>
<?php unset($__componentOriginalbfb9265e32d6b1aaed2eb5e0fa443a01); ?>
<?php endif; ?>
<?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\resources\views\layouts\auth.blade.php ENDPATH**/ ?>