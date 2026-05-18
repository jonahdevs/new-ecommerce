
<div x-data="{
    notification: null,
    visible: false,
    timeout: null,

    show({ variant = 'info', message = null, action = null }) {
        if (this.timeout) clearTimeout(this.timeout);
        this.notification = { variant, message, action };
        this.visible = true;

        // danger and action prompts require manual close
        const noAutoClose = variant === 'danger' || action !== null;
        if (!noAutoClose) {
            const duration = variant === 'warning' ? 8000 : 5000;
            this.timeout = setTimeout(() => this.dismiss(), duration);
        }
    },

    dismiss() {
        if (this.timeout) clearTimeout(this.timeout);
        this.visible = false;
    },

    runAction() {
        if (this.notification?.action?.js) {
            eval(this.notification.action.js);
        }
        this.dismiss();
    },
}" x-on:notify.window="show({ variant: $event.detail.variant, message: $event.detail.message })"
    x-on:notify-action.window="show({
        variant: $event.detail.variant,
        message: $event.detail.message,
        action: $event.detail.action,
    })"
    class="fixed top-0 inset-x-0 z-[200] pointer-events-none">

    <div x-show="visible" x-cloak class="pointer-events-auto w-full shadow-md"
        :class="{
            'bg-green-600': notification?.variant === 'success',
            'bg-red-600': notification?.variant === 'danger',
            'bg-amber-500': notification?.variant === 'warning',
            'bg-sky-600': notification?.variant === 'info' || !notification?.variant,
        }"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-y-full"
        x-transition:enter-end="translate-y-0" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0" x-transition:leave-end="-translate-y-full" role="alert">
        <div class="container mx-auto px-4 py-2.5 flex items-center gap-3">

            
            <div class="w-6 shrink-0"></div>

            
            <div class="flex-1 flex items-center justify-center gap-3 flex-wrap">
                <p x-text="notification?.message" class="text-sm font-medium text-white text-center"></p>

                
                <template x-if="notification?.action">
                    <button type="button" @click="runAction()"
                        class="shrink-0 text-xs font-semibold text-white underline underline-offset-2 hover:no-underline cursor-pointer transition-all whitespace-nowrap"
                        x-text="notification?.action?.label">
                    </button>
                </template>
            </div>

            
            <button type="button" @click="dismiss()" class="w-6 shrink-0 flex items-center justify-end cursor-pointer"
                aria-label="Close">
                <span
                    class="flex items-center justify-center w-5 h-5 rounded-full bg-white hover:opacity-80 transition-opacity"
                    :class="{
                        'text-green-600': notification?.variant === 'success',
                        'text-red-600': notification?.variant === 'danger',
                        'text-amber-500': notification?.variant === 'warning',
                        'text-sky-600': notification?.variant === 'info' || !notification?.variant,
                    }">
                    <?php if (isset($component)) { $__componentOriginal155e76c41fe51242bc25d269fabf82f5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal155e76c41fe51242bc25d269fabf82f5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.x-mark','data' => ['class' => 'size-3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.x-mark'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-3']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal155e76c41fe51242bc25d269fabf82f5)): ?>
<?php $attributes = $__attributesOriginal155e76c41fe51242bc25d269fabf82f5; ?>
<?php unset($__attributesOriginal155e76c41fe51242bc25d269fabf82f5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal155e76c41fe51242bc25d269fabf82f5)): ?>
<?php $component = $__componentOriginal155e76c41fe51242bc25d269fabf82f5; ?>
<?php unset($__componentOriginal155e76c41fe51242bc25d269fabf82f5); ?>
<?php endif; ?>
                </span>
            </button>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\resources\views/components/customer-notification.blade.php ENDPATH**/ ?>