<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php echo $__env->make('partials.head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</head>

<body <?php echo e($attributes->merge(['class' => 'bg-white text-on-surface font-sans min-h-screen flex flex-col'])); ?>>

    
    <div class="sticky top-0 left-0 z-20 w-full">
        <?php $general = app('App\Settings\GeneralSettings'); ?>

        
        <div class="bg-primary text-on-primary">
            <section class="container mx-auto px-4">
                <div class="flex items-center justify-between py-2 text-sm gap-4">

                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($general->store_phone): ?>
                        <div class="hidden md:flex items-center gap-3 lg:gap-4">
                            <div class="flex items-center gap-2">
                                <?php if (isset($component)) { $__componentOriginal3b273e6b331c9518de08da49e1886441 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b273e6b331c9518de08da49e1886441 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.phone','data' => ['class' => 'w-3.5 h-3.5 sm:w-4 sm:h-4 lg:w-4.5 lg:h-4.5 shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.phone'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-3.5 h-3.5 sm:w-4 sm:h-4 lg:w-4.5 lg:h-4.5 shrink-0']); ?>
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
                                <span class="text-xs sm:text-sm"><?php echo e($general->store_phone); ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="hidden md:block"></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.question-mark-circle','data' => ['class' => 'size-4 sm:size-5 shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.question-mark-circle'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4 sm:size-5 shrink-0']); ?>
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
                            <span class="group-hover:underline text-xs sm:text-sm">Support</span>
                        </a>
                    </div>

                </div>
            </section>
        </div>

        
        <?php app("livewire")->forceAssetInjection(); ?><div x-persist="<?php echo e('app-bar-header'); ?>">
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('app-bar-header', []);

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
    

    
    <?php app("livewire")->forceAssetInjection(); ?><div x-persist="<?php echo e('app-bar-categories'); ?>">
        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('app-bar-categories', []);

$__keyOuter = $__key ?? null;

$__key = null;
$__componentSlots = [];

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-3477001781-1', $__key);

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

    <main class="flex-1 bg-white">
        <?php echo e($slot); ?>

    </main>

    <?php if (isset($component)) { $__componentOriginalefb8450f00a7938d7dc7cce1f5a06186 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalefb8450f00a7938d7dc7cce1f5a06186 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer-notification','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer-notification'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalefb8450f00a7938d7dc7cce1f5a06186)): ?>
<?php $attributes = $__attributesOriginalefb8450f00a7938d7dc7cce1f5a06186; ?>
<?php unset($__attributesOriginalefb8450f00a7938d7dc7cce1f5a06186); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalefb8450f00a7938d7dc7cce1f5a06186)): ?>
<?php $component = $__componentOriginalefb8450f00a7938d7dc7cce1f5a06186; ?>
<?php unset($__componentOriginalefb8450f00a7938d7dc7cce1f5a06186); ?>
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

    
    <?php $social = app('App\Settings\SocialSettings'); ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($social->whatsapp_number): ?>
        <?php
            $waNumber = preg_replace('/[^0-9]/', '', $social->whatsapp_number);
        ?>
        <a href="https://wa.me/<?php echo e($waNumber); ?>?text=<?php echo e(urlencode('Hello Sheffield, I need assistance in')); ?>"
            target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp"
            class="fixed bottom-6 right-6 z-50 flex items-center justify-center w-14 h-14 rounded-full bg-[#25D366] text-white shadow-lg hover:bg-[#1ebe5d] transition-colors">
            <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                <path
                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
            </svg>
        </a>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php app('livewire')->forceAssetInjection(); ?>
<?php echo app('flux')->scripts(); ?>


    
    <style>
        [x-cloak] {
            display: none !important;
        }

        :root {
            --livewire-progress-bar-color: var(--secondary);
        }
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