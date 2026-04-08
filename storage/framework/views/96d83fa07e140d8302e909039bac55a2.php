<!DOCTYPE html>
<html lang="en">

<head>
    <?php echo $__env->make('partials.head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

</head>

<body class="bg-zinc-50 text-zinc-700 font-sans min-h-screen">


    
    <div class="bg-brand-primary text-brand-primary-content">
        <section class="container mx-auto px-4">
            <div class="flex items-center justify-between py-2 text-sm gap-4">

                
                <div class="hidden md:flex items-center gap-3 lg:gap-4">
                    <div class="flex items-center gap-2">
                        <?php if (isset($component)) { $__componentOriginal3b273e6b331c9518de08da49e1886441 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b273e6b331c9518de08da49e1886441 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.phone','data' => ['class' => 'w-4.5 h-4.5 shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.phone'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-4.5 h-4.5 shrink-0']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b273e6b331c9518de08da49e1886441)): ?>
<?php $attributes = $__attributesOriginal3b273e6b331c9518de08da49e1886441; ?>
<?php unset($__attributesOriginal3b273e6b331c9518de08da49e1886441); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b273e6b331c9518de08da49e1886441)): ?>
<?php $component = $__componentOriginal3b273e6b331c9518de08da49e1886441; ?>
<?php unset($__componentOriginal3b273e6b331c9518de08da49e1886441); ?>
<?php endif; ?>
                        <span class="text-xs lg:text-sm">(254) 713 777 111</span>
                    </div>
                </div>

                
                <div class="flex-1 md:flex-none md:max-w-md lg:max-w-lg mx-auto overflow-hidden h-6"
                    x-data="{
                        swiper: null,
                        init() {
                            this.$nextTick(() => {
                                this.initializeSwiper();
                            });
                        },
                        initializeSwiper() {
                            this.swiper = new Swiper('.promoSwiper', {
                                direction: 'vertical',
                                loop: true,
                                speed: 800,
                                autoplay: {
                                    delay: 3000,
                                    disableOnInteraction: false,
                                },
                            });
                        },
                        destroy() {
                            if (this.swiper) {
                                this.swiper.destroy(true, true);
                            }
                        }
                    }">
                    <div class="swiper promoSwiper h-full">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide flex items-center justify-center">
                                <a href="#"
                                    class="text-center text-xs sm:text-sm hover:opacity-90 transition-opacity">
                                    Get 50% off on Member Exclusive Month <span class="underline font-medium">Shop
                                        Now</span>
                                </a>
                            </div>
                            <div class="swiper-slide flex items-center justify-center">
                                <a href="#"
                                    class="text-center text-xs sm:text-sm hover:opacity-90 transition-opacity">
                                    Free Shipping on Orders Over <?php echo e(get_currency_symbol()); ?> 10,000 <span
                                        class="underline font-medium">Learn
                                        More</span>
                                </a>
                            </div>
                            <div class="swiper-slide flex items-center justify-center">
                                <a href="#"
                                    class="text-center text-xs sm:text-sm hover:opacity-90 transition-opacity">
                                    New Arrivals: Latest Kitchen Equipment <span
                                        class="underline font-medium">Explore</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="hidden md:flex items-center gap-4">
                    <a href="" class="flex items-center gap-2 group hover:opacity-90 transition-opacity">
                        <?php if (isset($component)) { $__componentOriginal7ff90a4ec719b449b03bf1ad0e63e8a9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7ff90a4ec719b449b03bf1ad0e63e8a9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.question-mark-circle','data' => ['class' => 'size-5 shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.question-mark-circle'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-5 shrink-0']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7ff90a4ec719b449b03bf1ad0e63e8a9)): ?>
<?php $attributes = $__attributesOriginal7ff90a4ec719b449b03bf1ad0e63e8a9; ?>
<?php unset($__attributesOriginal7ff90a4ec719b449b03bf1ad0e63e8a9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7ff90a4ec719b449b03bf1ad0e63e8a9)): ?>
<?php $component = $__componentOriginal7ff90a4ec719b449b03bf1ad0e63e8a9; ?>
<?php unset($__componentOriginal7ff90a4ec719b449b03bf1ad0e63e8a9); ?>
<?php endif; ?>
                        <span class="group-hover:underline text-xs lg:text-sm">Support</span>
                    </a>
                </div>

            </div>
        </section>
    </div>

    <div class="sticky top-0 left-0 z-100 w-full">
        
        <?php app("livewire")->forceAssetInjection(); ?><div x-persist="<?php echo e('app-bar'); ?>">
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('app-bar', []);

$__keyOuter = $__key ?? null;

$__key = null;
$__componentSlots = [];

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-3477001781-0', $__key);

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
        </div>

    </div>
    

    <main>
        <?php echo e($slot); ?>

    </main>

    <?php if (isset($component)) { $__componentOriginalf98a32c06d8462f5513d0fb3554f9141 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf98a32c06d8462f5513d0fb3554f9141 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.toast-notification','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('toast-notification'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf98a32c06d8462f5513d0fb3554f9141)): ?>
<?php $attributes = $__attributesOriginalf98a32c06d8462f5513d0fb3554f9141; ?>
<?php unset($__attributesOriginalf98a32c06d8462f5513d0fb3554f9141); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf98a32c06d8462f5513d0fb3554f9141)): ?>
<?php $component = $__componentOriginalf98a32c06d8462f5513d0fb3554f9141; ?>
<?php unset($__componentOriginalf98a32c06d8462f5513d0fb3554f9141); ?>
<?php endif; ?>

    <?php app("livewire")->forceAssetInjection(); ?><div x-persist="<?php echo e('footer'); ?>">
        <?php if (isset($component)) { $__componentOriginal8a8716efb3c62a45938aca52e78e0322 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8a8716efb3c62a45938aca52e78e0322 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.footer','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('footer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8a8716efb3c62a45938aca52e78e0322)): ?>
<?php $attributes = $__attributesOriginal8a8716efb3c62a45938aca52e78e0322; ?>
<?php unset($__attributesOriginal8a8716efb3c62a45938aca52e78e0322); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8a8716efb3c62a45938aca52e78e0322)): ?>
<?php $component = $__componentOriginal8a8716efb3c62a45938aca52e78e0322; ?>
<?php unset($__componentOriginal8a8716efb3c62a45938aca52e78e0322); ?>
<?php endif; ?>
    </div>

    <?php app('livewire')->forceAssetInjection(); ?>
<?php echo app('flux')->scripts(); ?>


    
    <style>
        [x-cloak] { display: none !important; }
        :root { --livewire-progress-bar-color: var(--brand-secondary); }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if(session('success')): ?>
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        variant: 'success',
                        message: <?php echo \Illuminate\Support\Js::from(session('success'))->toHtml() ?>
                    }
                }));
            <?php endif; ?>

            <?php if(session('error')): ?>
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        variant: 'danger',
                        message: <?php echo \Illuminate\Support\Js::from(session('error'))->toHtml() ?>
                    }
                }));
            <?php endif; ?>

            <?php if(session('warning')): ?>
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        variant: 'warning',
                        message: <?php echo \Illuminate\Support\Js::from(session('warning'))->toHtml() ?>
                    }
                }));
            <?php endif; ?>

            <?php if(session('info')): ?>
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        variant: 'info',
                        message: <?php echo \Illuminate\Support\Js::from(session('info'))->toHtml() ?>
                    }
                }));
            <?php endif; ?>
        });
    </script>
</body>

</html>
<?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\resources\views/layouts/guest.blade.php ENDPATH**/ ?>