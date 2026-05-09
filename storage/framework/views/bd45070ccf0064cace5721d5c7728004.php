<?php if (isset($component)) { $__componentOriginal071cba40201c8f65242f69b169ef9aaa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal071cba40201c8f65242f69b169ef9aaa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.form-field','data' => ['label' => 'Password','name' => 'delete_password']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.form-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Password','name' => 'delete_password']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
<input type="password" class="customer-input border-red-300"> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal071cba40201c8f65242f69b169ef9aaa)): ?>
<?php $attributes = $__attributesOriginal071cba40201c8f65242f69b169ef9aaa; ?>
<?php unset($__attributesOriginal071cba40201c8f65242f69b169ef9aaa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal071cba40201c8f65242f69b169ef9aaa)): ?>
<?php $component = $__componentOriginal071cba40201c8f65242f69b169ef9aaa; ?>
<?php unset($__componentOriginal071cba40201c8f65242f69b169ef9aaa); ?>
<?php endif; ?><?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\storage\framework\views/ee2fe5ae07afe4474fe8c51eabf9ff9c.blade.php ENDPATH**/ ?>