<?php
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\{Layout, Title};
use Livewire\Component;
use Artesaos\SEOTools\Facades\SEOMeta;
?>

<div>
    <?php if (isset($component)) { $__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.settings-card','data' => ['title' => 'Notification','titleEm' => 'Preferences']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.settings-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Notification','titleEm' => 'Preferences']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('icon', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginal2357204bbfb73ef228c684f3b7e8f9fa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2357204bbfb73ef228c684f3b7e8f9fa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.bell','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.bell'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2357204bbfb73ef228c684f3b7e8f9fa)): ?>
<?php $attributes = $__attributesOriginal2357204bbfb73ef228c684f3b7e8f9fa; ?>
<?php unset($__attributesOriginal2357204bbfb73ef228c684f3b7e8f9fa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2357204bbfb73ef228c684f3b7e8f9fa)): ?>
<?php $component = $__componentOriginal2357204bbfb73ef228c684f3b7e8f9fa; ?>
<?php unset($__componentOriginal2357204bbfb73ef228c684f3b7e8f9fa); ?>
<?php endif; ?>
         <?php $__env->endSlot(); ?>

        
        <div class="flex items-center justify-end px-5 py-2.5 border-b border-zinc-200 gap-5">
            <span class="text-[9px] font-extrabold tracking-widest uppercase text-zinc-500 w-9 text-center">Email</span>
            <span class="text-[9px] font-extrabold tracking-widest uppercase text-zinc-500 w-9 text-center">SMS</span>
            <span class="text-[9px] font-extrabold tracking-widest uppercase text-zinc-500 w-9 text-center">Push</span>
        </div>

        
        <div class="flex items-center gap-2 px-5 py-3.5 border-b border-zinc-200 bg-zinc-50/40">
            <?php if (isset($component)) { $__componentOriginalc43f195f1b3468dd3dad13eb2914c8e1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc43f195f1b3468dd3dad13eb2914c8e1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.shopping-bag','data' => ['class' => 'w-3.5 h-3.5 text-primary shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.shopping-bag'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-3.5 h-3.5 text-primary shrink-0']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc43f195f1b3468dd3dad13eb2914c8e1)): ?>
<?php $attributes = $__attributesOriginalc43f195f1b3468dd3dad13eb2914c8e1; ?>
<?php unset($__attributesOriginalc43f195f1b3468dd3dad13eb2914c8e1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc43f195f1b3468dd3dad13eb2914c8e1)): ?>
<?php $component = $__componentOriginalc43f195f1b3468dd3dad13eb2914c8e1; ?>
<?php unset($__componentOriginalc43f195f1b3468dd3dad13eb2914c8e1); ?>
<?php endif; ?>
            <span class="text-[11px] font-bold tracking-widest uppercase text-zinc-500">Orders & Shipping</span>
        </div>
        <?php if (isset($component)) { $__componentOriginalee132a16b1d41f96d94c66f138a9563d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee132a16b1d41f96d94c66f138a9563d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.notification-row','data' => ['topic' => 'order_confirmations','title' => 'Order Confirmations','description' => 'Receive confirmation when your order is placed']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.notification-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['topic' => 'order_confirmations','title' => 'Order Confirmations','description' => 'Receive confirmation when your order is placed']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee132a16b1d41f96d94c66f138a9563d)): ?>
<?php $attributes = $__attributesOriginalee132a16b1d41f96d94c66f138a9563d; ?>
<?php unset($__attributesOriginalee132a16b1d41f96d94c66f138a9563d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee132a16b1d41f96d94c66f138a9563d)): ?>
<?php $component = $__componentOriginalee132a16b1d41f96d94c66f138a9563d; ?>
<?php unset($__componentOriginalee132a16b1d41f96d94c66f138a9563d); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginalee132a16b1d41f96d94c66f138a9563d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee132a16b1d41f96d94c66f138a9563d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.notification-row','data' => ['topic' => 'shipping_updates','title' => 'Shipping Updates','description' => 'Get notified when your order ships and is delivered']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.notification-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['topic' => 'shipping_updates','title' => 'Shipping Updates','description' => 'Get notified when your order ships and is delivered']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee132a16b1d41f96d94c66f138a9563d)): ?>
<?php $attributes = $__attributesOriginalee132a16b1d41f96d94c66f138a9563d; ?>
<?php unset($__attributesOriginalee132a16b1d41f96d94c66f138a9563d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee132a16b1d41f96d94c66f138a9563d)): ?>
<?php $component = $__componentOriginalee132a16b1d41f96d94c66f138a9563d; ?>
<?php unset($__componentOriginalee132a16b1d41f96d94c66f138a9563d); ?>
<?php endif; ?>

        
        <div class="flex items-center gap-2 px-5 py-3.5 border-b border-zinc-200 bg-zinc-50/40">
            <?php if (isset($component)) { $__componentOriginal372652fcc747cd9bb1f591829ed1255a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal372652fcc747cd9bb1f591829ed1255a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.tag','data' => ['class' => 'w-3.5 h-3.5 text-primary shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.tag'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-3.5 h-3.5 text-primary shrink-0']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal372652fcc747cd9bb1f591829ed1255a)): ?>
<?php $attributes = $__attributesOriginal372652fcc747cd9bb1f591829ed1255a; ?>
<?php unset($__attributesOriginal372652fcc747cd9bb1f591829ed1255a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal372652fcc747cd9bb1f591829ed1255a)): ?>
<?php $component = $__componentOriginal372652fcc747cd9bb1f591829ed1255a; ?>
<?php unset($__componentOriginal372652fcc747cd9bb1f591829ed1255a); ?>
<?php endif; ?>
            <span class="text-[11px] font-bold tracking-widest uppercase text-zinc-500">Promotions & Offers</span>
        </div>
        <?php if (isset($component)) { $__componentOriginalee132a16b1d41f96d94c66f138a9563d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee132a16b1d41f96d94c66f138a9563d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.notification-row','data' => ['topic' => 'special_offers','title' => 'Special Offers','description' => 'Exclusive deals, discounts and promotions']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.notification-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['topic' => 'special_offers','title' => 'Special Offers','description' => 'Exclusive deals, discounts and promotions']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee132a16b1d41f96d94c66f138a9563d)): ?>
<?php $attributes = $__attributesOriginalee132a16b1d41f96d94c66f138a9563d; ?>
<?php unset($__attributesOriginalee132a16b1d41f96d94c66f138a9563d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee132a16b1d41f96d94c66f138a9563d)): ?>
<?php $component = $__componentOriginalee132a16b1d41f96d94c66f138a9563d; ?>
<?php unset($__componentOriginalee132a16b1d41f96d94c66f138a9563d); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginalee132a16b1d41f96d94c66f138a9563d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee132a16b1d41f96d94c66f138a9563d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.notification-row','data' => ['topic' => 'new_arrivals','title' => 'New Arrivals','description' => 'Be the first to know about new products']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.notification-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['topic' => 'new_arrivals','title' => 'New Arrivals','description' => 'Be the first to know about new products']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee132a16b1d41f96d94c66f138a9563d)): ?>
<?php $attributes = $__attributesOriginalee132a16b1d41f96d94c66f138a9563d; ?>
<?php unset($__attributesOriginalee132a16b1d41f96d94c66f138a9563d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee132a16b1d41f96d94c66f138a9563d)): ?>
<?php $component = $__componentOriginalee132a16b1d41f96d94c66f138a9563d; ?>
<?php unset($__componentOriginalee132a16b1d41f96d94c66f138a9563d); ?>
<?php endif; ?>

        
        <div class="flex items-center gap-2 px-5 py-3.5 border-b border-zinc-200 bg-zinc-50/40">
            <?php if (isset($component)) { $__componentOriginal0bc6ca59f258b8d2577c76df279598af = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0bc6ca59f258b8d2577c76df279598af = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.star','data' => ['class' => 'w-3.5 h-3.5 text-primary shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.star'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-3.5 h-3.5 text-primary shrink-0']); ?>
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
            <span class="text-[11px] font-bold tracking-widest uppercase text-zinc-500">Reviews & Feedback</span>
        </div>
        <?php if (isset($component)) { $__componentOriginalee132a16b1d41f96d94c66f138a9563d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee132a16b1d41f96d94c66f138a9563d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.notification-row','data' => ['topic' => 'review_reminders','title' => 'Review Reminders','description' => 'Reminders to review products you\'ve purchased']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.notification-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['topic' => 'review_reminders','title' => 'Review Reminders','description' => 'Reminders to review products you\'ve purchased']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee132a16b1d41f96d94c66f138a9563d)): ?>
<?php $attributes = $__attributesOriginalee132a16b1d41f96d94c66f138a9563d; ?>
<?php unset($__attributesOriginalee132a16b1d41f96d94c66f138a9563d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee132a16b1d41f96d94c66f138a9563d)): ?>
<?php $component = $__componentOriginalee132a16b1d41f96d94c66f138a9563d; ?>
<?php unset($__componentOriginalee132a16b1d41f96d94c66f138a9563d); ?>
<?php endif; ?>

        
        <div class="flex items-center gap-2 px-5 py-3.5 border-b border-zinc-200 bg-zinc-50/40">
            <?php if (isset($component)) { $__componentOriginalf870514c33bb1b53395ba02235f60146 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf870514c33bb1b53395ba02235f60146 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.shield-check','data' => ['class' => 'w-3.5 h-3.5 text-primary shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.shield-check'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-3.5 h-3.5 text-primary shrink-0']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf870514c33bb1b53395ba02235f60146)): ?>
<?php $attributes = $__attributesOriginalf870514c33bb1b53395ba02235f60146; ?>
<?php unset($__attributesOriginalf870514c33bb1b53395ba02235f60146); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf870514c33bb1b53395ba02235f60146)): ?>
<?php $component = $__componentOriginalf870514c33bb1b53395ba02235f60146; ?>
<?php unset($__componentOriginalf870514c33bb1b53395ba02235f60146); ?>
<?php endif; ?>
            <span class="text-[11px] font-bold tracking-widest uppercase text-zinc-500">Account & Security</span>
        </div>
        <?php if (isset($component)) { $__componentOriginalee132a16b1d41f96d94c66f138a9563d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee132a16b1d41f96d94c66f138a9563d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.notification-row','data' => ['topic' => 'security_alerts','title' => 'Security Alerts','description' => 'Important updates about your account security']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.notification-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['topic' => 'security_alerts','title' => 'Security Alerts','description' => 'Important updates about your account security']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee132a16b1d41f96d94c66f138a9563d)): ?>
<?php $attributes = $__attributesOriginalee132a16b1d41f96d94c66f138a9563d; ?>
<?php unset($__attributesOriginalee132a16b1d41f96d94c66f138a9563d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee132a16b1d41f96d94c66f138a9563d)): ?>
<?php $component = $__componentOriginalee132a16b1d41f96d94c66f138a9563d; ?>
<?php unset($__componentOriginalee132a16b1d41f96d94c66f138a9563d); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginalee132a16b1d41f96d94c66f138a9563d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee132a16b1d41f96d94c66f138a9563d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.notification-row','data' => ['topic' => 'password_changes','title' => 'Password Changes','description' => 'Notifications when your password is changed']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.notification-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['topic' => 'password_changes','title' => 'Password Changes','description' => 'Notifications when your password is changed']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee132a16b1d41f96d94c66f138a9563d)): ?>
<?php $attributes = $__attributesOriginalee132a16b1d41f96d94c66f138a9563d; ?>
<?php unset($__attributesOriginalee132a16b1d41f96d94c66f138a9563d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee132a16b1d41f96d94c66f138a9563d)): ?>
<?php $component = $__componentOriginalee132a16b1d41f96d94c66f138a9563d; ?>
<?php unset($__componentOriginalee132a16b1d41f96d94c66f138a9563d); ?>
<?php endif; ?>

        
        <div class="flex items-center gap-2 px-5 py-3.5 border-b border-zinc-200 bg-zinc-50/40">
            <?php if (isset($component)) { $__componentOriginalb2620669e6f3f9a8ec8b91c4a73fca6f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb2620669e6f3f9a8ec8b91c4a73fca6f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.envelope','data' => ['class' => 'w-3.5 h-3.5 text-primary shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.envelope'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-3.5 h-3.5 text-primary shrink-0']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb2620669e6f3f9a8ec8b91c4a73fca6f)): ?>
<?php $attributes = $__attributesOriginalb2620669e6f3f9a8ec8b91c4a73fca6f; ?>
<?php unset($__attributesOriginalb2620669e6f3f9a8ec8b91c4a73fca6f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb2620669e6f3f9a8ec8b91c4a73fca6f)): ?>
<?php $component = $__componentOriginalb2620669e6f3f9a8ec8b91c4a73fca6f; ?>
<?php unset($__componentOriginalb2620669e6f3f9a8ec8b91c4a73fca6f); ?>
<?php endif; ?>
            <span class="text-[11px] font-bold tracking-widest uppercase text-zinc-500">Marketing Communications</span>
        </div>
        <div class="flex items-center justify-between px-5 py-3.5">
            <div class="flex-1">
                <div class="text-[13px] font-semibold text-zinc-950 mb-0.5">Newsletter Subscription</div>
                <div class="text-[12px] text-zinc-500">Receive updates about new products, promotions, and special
                    offers</div>
            </div>
            <button type="button" wire:click="$toggle('newsletter_subscribed')" class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                'relative w-9.5 h-5.5 rounded-full shrink-0 transition-colors cursor-pointer ml-4',
                'bg-primary' => $newsletter_subscribed,
                'bg-zinc-200' => !$newsletter_subscribed,
            ]); ?>">
                <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    'absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform',
                    'translate-x-4' => $newsletter_subscribed,
                ]); ?>"></div>
            </button>
        </div>

         <?php $__env->slot('footer', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button.index','data' => ['type' => 'button','wire:click' => 'save','variant' => 'customer-primary','size' => 'customer-lg']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','wire:click' => 'save','variant' => 'customer-primary','size' => 'customer-lg']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <span wire:loading.remove wire:target="save">Save Preferences</span>
                <span wire:loading wire:target="save">Saving...</span>
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
         <?php $__env->endSlot(); ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb)): ?>
<?php $attributes = $__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb; ?>
<?php unset($__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb)): ?>
<?php $component = $__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb; ?>
<?php unset($__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb); ?>
<?php endif; ?>
</div><?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\storage\framework/views/livewire/views/ac383b63.blade.php ENDPATH**/ ?>