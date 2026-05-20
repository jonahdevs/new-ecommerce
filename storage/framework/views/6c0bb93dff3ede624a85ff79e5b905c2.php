<?php
use App\Services\{CompareService, WishlistService, CartService};
use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\QuoteBasketService;
?>

<nav class="w-full bg-cover bg-center bg-no-repeat"
    style="background-image: url('<?php echo e(asset('images/stainless_steel.jpg')); ?>')">
    <section class="container mx-auto px-4 py-3 lg:py-4">
        <div class="flex items-center justify-between gap-3 sm:gap-4">

            
            <a href="<?php echo e(route('home')); ?>" wire:navigate class="flex items-center shrink-0">
                <img src="<?php echo e(asset('logo.png')); ?>" alt="<?php echo e(config('site.site.name')); ?> Logo"
                    class="h-8 sm:h-10 lg:h-12 w-auto transition-transform duration-300 hover:scale-105" />
            </a>

            
            <div class="hidden lg:flex flex-1 min-w-0 px-6 xl:px-10">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!($isErrorPage ?? false)): ?>
                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('search-bar', []);

$__keyOuter = $__key ?? null;

$__key = null;
$__componentSlots = [];

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-3444346058-0', $__key);

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
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <div class="flex items-center gap-2 sm:gap-3 lg:gap-5">

                
                <div class="lg:hidden">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!($isErrorPage ?? false)): ?>
                        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('search-bar', []);

$__keyOuter = $__key ?? null;

$__key = null;
$__componentSlots = [];

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-3444346058-1', $__key);

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
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                <a href="<?php echo e(route('wishlist')); ?>" wire:navigate class="hidden lg:flex items-center gap-2 group">
                    <div class="relative">
                        <?php if (isset($component)) { $__componentOriginalfcc604edd6e541ab058ff166c8353443 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfcc604edd6e541ab058ff166c8353443 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.heart','data' => ['class' => 'size-5 lg:size-6 text-zinc-800 group-hover:text-primary transition-colors']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.heart'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-5 lg:size-6 text-zinc-800 group-hover:text-primary transition-colors']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfcc604edd6e541ab058ff166c8353443)): ?>
<?php $attributes = $__attributesOriginalfcc604edd6e541ab058ff166c8353443; ?>
<?php unset($__attributesOriginalfcc604edd6e541ab058ff166c8353443); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfcc604edd6e541ab058ff166c8353443)): ?>
<?php $component = $__componentOriginalfcc604edd6e541ab058ff166c8353443; ?>
<?php unset($__componentOriginalfcc604edd6e541ab058ff166c8353443); ?>
<?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($wishlistCount > 0): ?>
                            <span
                                class="absolute -top-2 -right-2 bg-primary text-on-primary text-[10px] sm:text-xs rounded-full w-4 h-4 sm:w-5 sm:h-5 flex items-center justify-center font-medium">
                                <?php echo e($wishlistCount); ?>

                            </span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <span class="text-xs lg:text-sm font-medium text-zinc-800">Wishlist</span>
                </a>

                
                <a href="<?php echo e(route('products.compare')); ?>" wire:navigate class="hidden lg:flex items-center gap-2 group">
                    <div class="relative">
                        <?php if (isset($component)) { $__componentOriginald5a897b0ee22b6aaa0496659ac878337 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald5a897b0ee22b6aaa0496659ac878337 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon.compare','data' => ['class' => 'size-5 lg:size-6 text-zinc-800 group-hover:text-primary transition-colors']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon.compare'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-5 lg:size-6 text-zinc-800 group-hover:text-primary transition-colors']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald5a897b0ee22b6aaa0496659ac878337)): ?>
<?php $attributes = $__attributesOriginald5a897b0ee22b6aaa0496659ac878337; ?>
<?php unset($__attributesOriginald5a897b0ee22b6aaa0496659ac878337); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald5a897b0ee22b6aaa0496659ac878337)): ?>
<?php $component = $__componentOriginald5a897b0ee22b6aaa0496659ac878337; ?>
<?php unset($__componentOriginald5a897b0ee22b6aaa0496659ac878337); ?>
<?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($compareCount > 0): ?>
                            <span
                                class="absolute -top-2 -right-2 bg-primary text-on-primary text-[10px] sm:text-xs font-medium rounded-full h-4 w-4 sm:h-5 sm:w-5 flex items-center justify-center">
                                <?php echo e($compareCount); ?>

                            </span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <span class="text-xs lg:text-sm font-medium text-zinc-800">Compare</span>
                </a>

                
                <a href="<?php echo e(route('cart')); ?>" wire:navigate class="flex items-center gap-2 group">
                    <div class="relative">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-zinc-800 group-hover:text-primary transition-colors"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cartCount > 0): ?>
                            <span
                                class="absolute -top-2 -right-2 bg-primary text-on-primary text-[10px] sm:text-xs font-medium rounded-full w-4 h-4 sm:w-5 sm:h-5 flex items-center justify-center">
                                <?php echo e($cartCount); ?>

                            </span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <span class="hidden lg:inline text-xs lg:text-sm font-medium text-zinc-800">Cart</span>
                </a>

                
                <?php if (isset($component)) { $__componentOriginal2b4bb2cd4b8f1a3c08bae49ea918b888 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2b4bb2cd4b8f1a3c08bae49ea918b888 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::dropdown','data' => ['position' => 'bottom','align' => 'end','hover' => true,'class' => 'ms-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['position' => 'bottom','align' => 'end','hover' => true,'class' => 'ms-2']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                        <button type="button" class="flex items-center gap-2 cursor-pointer">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->avatar): ?>
                                <?php if (isset($component)) { $__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::avatar.index','data' => ['circle' => true,'size' => 'sm','src' => ''.e(auth()->user()->avatar).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['circle' => true,'size' => 'sm','src' => ''.e(auth()->user()->avatar).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690)): ?>
<?php $attributes = $__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690; ?>
<?php unset($__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690)): ?>
<?php $component = $__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690; ?>
<?php unset($__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690); ?>
<?php endif; ?>
                            <?php else: ?>
                                <?php if (isset($component)) { $__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::avatar.index','data' => ['circle' => true,'size' => 'sm','name' => ''.e(auth()->user()->name).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['circle' => true,'size' => 'sm','name' => ''.e(auth()->user()->name).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690)): ?>
<?php $attributes = $__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690; ?>
<?php unset($__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690)): ?>
<?php $component = $__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690; ?>
<?php unset($__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690); ?>
<?php endif; ?>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <span class="hidden md:block text-xs lg:text-sm font-medium text-zinc-800">
                                <?php echo e(auth()->user()->name); ?>

                            </span>
                            <svg class="hidden md:block w-4 h-4 text-zinc-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    <?php else: ?>
                        <button type="button" class="flex items-center gap-2 hover:text-secondary transition-colors">
                            <?php if (isset($component)) { $__componentOriginalcbe89caa4ae8c58f7efd0ed6343c35ff = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcbe89caa4ae8c58f7efd0ed6343c35ff = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.user','data' => ['class' => 'w-5 h-5 sm:w-6 sm:h-6 text-zinc-800','variant' => 'outline']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.user'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5 sm:w-6 sm:h-6 text-zinc-800','variant' => 'outline']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcbe89caa4ae8c58f7efd0ed6343c35ff)): ?>
<?php $attributes = $__attributesOriginalcbe89caa4ae8c58f7efd0ed6343c35ff; ?>
<?php unset($__attributesOriginalcbe89caa4ae8c58f7efd0ed6343c35ff); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcbe89caa4ae8c58f7efd0ed6343c35ff)): ?>
<?php $component = $__componentOriginalcbe89caa4ae8c58f7efd0ed6343c35ff; ?>
<?php unset($__componentOriginalcbe89caa4ae8c58f7efd0ed6343c35ff); ?>
<?php endif; ?>
                            <div class="hidden lg:block">
                                <div class="text-xs lg:text-sm font-medium text-zinc-800">Account</div>
                            </div>
                            <svg class="w-3.5 h-3.5 lg:w-4 lg:h-4 hidden lg:block text-zinc-600" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if (isset($component)) { $__componentOriginal0acbef9d8e9b45c80d953734a49636ad = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0acbef9d8e9b45c80d953734a49636ad = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::navmenu.index','data' => ['class' => 'rounded-sm! shadow-2xl! min-w-56!']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::navmenu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'rounded-sm! shadow-2xl! min-w-56!']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>


                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                            <div
                                class="px-3 py-3 border-b border-zinc-100 dark:border-zinc-700 flex items-center gap-3">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->avatar): ?>
                                    <?php if (isset($component)) { $__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::avatar.index','data' => ['circle' => true,'size' => 'sm','src' => ''.e(auth()->user()->avatar).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['circle' => true,'size' => 'sm','src' => ''.e(auth()->user()->avatar).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690)): ?>
<?php $attributes = $__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690; ?>
<?php unset($__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690)): ?>
<?php $component = $__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690; ?>
<?php unset($__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690); ?>
<?php endif; ?>
                                <?php else: ?>
                                    <?php if (isset($component)) { $__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::avatar.index','data' => ['circle' => true,'size' => 'sm','name' => ''.e(auth()->user()->name).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['circle' => true,'size' => 'sm','name' => ''.e(auth()->user()->name).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690)): ?>
<?php $attributes = $__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690; ?>
<?php unset($__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690)): ?>
<?php $component = $__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690; ?>
<?php unset($__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690); ?>
<?php endif; ?>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-on-surface truncate">
                                        <?php echo e(auth()->user()->name); ?></p>
                                    <p class="text-xs text-on-surface-variant truncate"><?php echo e(auth()->user()->email); ?></p>
                                </div>
                            </div>

                            
                            <?php if (isset($component)) { $__componentOriginal6498d2c45a9cd193b85bf4c51011baaf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::navmenu.item','data' => ['href' => route('customer.account'),'wire:navigate' => true,'icon' => 'user','iconVariant' => 'outline']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::navmenu.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('customer.account')),'wire:navigate' => true,'icon' => 'user','icon-variant' => 'outline']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                Account
                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf)): ?>
<?php $attributes = $__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf; ?>
<?php unset($__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6498d2c45a9cd193b85bf4c51011baaf)): ?>
<?php $component = $__componentOriginal6498d2c45a9cd193b85bf4c51011baaf; ?>
<?php unset($__componentOriginal6498d2c45a9cd193b85bf4c51011baaf); ?>
<?php endif; ?>
                            <?php if (isset($component)) { $__componentOriginal6498d2c45a9cd193b85bf4c51011baaf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::navmenu.item','data' => ['href' => route('customer.orders.index'),'wire:navigate' => true,'icon' => 'package','iconVariant' => 'outline']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::navmenu.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('customer.orders.index')),'wire:navigate' => true,'icon' => 'package','icon-variant' => 'outline']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                Orders
                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf)): ?>
<?php $attributes = $__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf; ?>
<?php unset($__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6498d2c45a9cd193b85bf4c51011baaf)): ?>
<?php $component = $__componentOriginal6498d2c45a9cd193b85bf4c51011baaf; ?>
<?php unset($__componentOriginal6498d2c45a9cd193b85bf4c51011baaf); ?>
<?php endif; ?>

                            <?php if (isset($component)) { $__componentOriginald5e1eb3ae521062f8474178ba08933ca = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald5e1eb3ae521062f8474178ba08933ca = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::menu.separator','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::menu.separator'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald5e1eb3ae521062f8474178ba08933ca)): ?>
<?php $attributes = $__attributesOriginald5e1eb3ae521062f8474178ba08933ca; ?>
<?php unset($__attributesOriginald5e1eb3ae521062f8474178ba08933ca); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald5e1eb3ae521062f8474178ba08933ca)): ?>
<?php $component = $__componentOriginald5e1eb3ae521062f8474178ba08933ca; ?>
<?php unset($__componentOriginald5e1eb3ae521062f8474178ba08933ca); ?>
<?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        
                        <?php if (isset($component)) { $__componentOriginal6498d2c45a9cd193b85bf4c51011baaf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::navmenu.item','data' => ['href' => route('quote'),'wire:navigate' => true,'icon' => 'document-text','iconVariant' => 'outline']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::navmenu.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('quote')),'wire:navigate' => true,'icon' => 'document-text','icon-variant' => 'outline']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            <span class="flex items-center gap-2 w-full">
                                Quote Basket
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quoteCount > 0): ?>
                                    <span
                                        class="ms-auto bg-amber-500 text-white text-xs font-medium rounded-full h-5 w-5 flex items-center justify-center">
                                        <?php echo e($quoteCount); ?>

                                    </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </span>
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf)): ?>
<?php $attributes = $__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf; ?>
<?php unset($__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6498d2c45a9cd193b85bf4c51011baaf)): ?>
<?php $component = $__componentOriginal6498d2c45a9cd193b85bf4c51011baaf; ?>
<?php unset($__componentOriginal6498d2c45a9cd193b85bf4c51011baaf); ?>
<?php endif; ?>

                        
                        <div class="lg:hidden">
                            <?php if (isset($component)) { $__componentOriginal6498d2c45a9cd193b85bf4c51011baaf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::navmenu.item','data' => ['href' => route('wishlist'),'wire:navigate' => true,'icon' => 'heart','iconVariant' => 'outline']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::navmenu.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('wishlist')),'wire:navigate' => true,'icon' => 'heart','icon-variant' => 'outline']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                <span class="flex items-center gap-2 w-full">
                                    Wishlist
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($wishlistCount > 0): ?>
                                        <span
                                            class="ms-auto bg-primary text-on-primary text-xs font-medium rounded-full h-5 w-5 flex items-center justify-center">
                                            <?php echo e($wishlistCount); ?>

                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </span>
                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf)): ?>
<?php $attributes = $__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf; ?>
<?php unset($__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6498d2c45a9cd193b85bf4c51011baaf)): ?>
<?php $component = $__componentOriginal6498d2c45a9cd193b85bf4c51011baaf; ?>
<?php unset($__componentOriginal6498d2c45a9cd193b85bf4c51011baaf); ?>
<?php endif; ?>
                            <?php if (isset($component)) { $__componentOriginal6498d2c45a9cd193b85bf4c51011baaf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::navmenu.item','data' => ['href' => route('products.compare'),'wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::navmenu.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('products.compare')),'wire:navigate' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                 <?php $__env->slot('icon', null, []); ?> <?php if (isset($component)) { $__componentOriginald5a897b0ee22b6aaa0496659ac878337 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald5a897b0ee22b6aaa0496659ac878337 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon.compare','data' => ['class' => 'size-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon.compare'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald5a897b0ee22b6aaa0496659ac878337)): ?>
<?php $attributes = $__attributesOriginald5a897b0ee22b6aaa0496659ac878337; ?>
<?php unset($__attributesOriginald5a897b0ee22b6aaa0496659ac878337); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald5a897b0ee22b6aaa0496659ac878337)): ?>
<?php $component = $__componentOriginald5a897b0ee22b6aaa0496659ac878337; ?>
<?php unset($__componentOriginald5a897b0ee22b6aaa0496659ac878337); ?>
<?php endif; ?> <?php $__env->endSlot(); ?>
                                <span class="flex items-center gap-2 w-full">
                                    Compare
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($compareCount > 0): ?>
                                        <span
                                            class="ms-auto bg-primary text-on-primary text-xs font-medium rounded-full h-5 w-5 flex items-center justify-center">
                                            <?php echo e($compareCount); ?>

                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </span>
                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf)): ?>
<?php $attributes = $__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf; ?>
<?php unset($__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6498d2c45a9cd193b85bf4c51011baaf)): ?>
<?php $component = $__componentOriginal6498d2c45a9cd193b85bf4c51011baaf; ?>
<?php unset($__componentOriginal6498d2c45a9cd193b85bf4c51011baaf); ?>
<?php endif; ?>
                        </div>

                        <?php if (isset($component)) { $__componentOriginald5e1eb3ae521062f8474178ba08933ca = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald5e1eb3ae521062f8474178ba08933ca = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::menu.separator','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::menu.separator'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald5e1eb3ae521062f8474178ba08933ca)): ?>
<?php $attributes = $__attributesOriginald5e1eb3ae521062f8474178ba08933ca; ?>
<?php unset($__attributesOriginald5e1eb3ae521062f8474178ba08933ca); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald5e1eb3ae521062f8474178ba08933ca)): ?>
<?php $component = $__componentOriginald5e1eb3ae521062f8474178ba08933ca; ?>
<?php unset($__componentOriginald5e1eb3ae521062f8474178ba08933ca); ?>
<?php endif; ?>

                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                            <form action="<?php echo e(route('logout')); ?>" method="post">
                                <?php echo csrf_field(); ?>
                                <?php if (isset($component)) { $__componentOriginal6498d2c45a9cd193b85bf4c51011baaf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::navmenu.item','data' => ['type' => 'submit','icon' => 'arrow-right-start-on-rectangle','variant' => 'danger','class' => 'cursor-pointer']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::navmenu.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','icon' => 'arrow-right-start-on-rectangle','variant' => 'danger','class' => 'cursor-pointer']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                    Logout
                                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf)): ?>
<?php $attributes = $__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf; ?>
<?php unset($__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6498d2c45a9cd193b85bf4c51011baaf)): ?>
<?php $component = $__componentOriginal6498d2c45a9cd193b85bf4c51011baaf; ?>
<?php unset($__componentOriginal6498d2c45a9cd193b85bf4c51011baaf); ?>
<?php endif; ?>
                            </form>
                        <?php else: ?>
                            <?php if (isset($component)) { $__componentOriginal6498d2c45a9cd193b85bf4c51011baaf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::navmenu.item','data' => ['href' => ''.e(route('login')).'','wire:navigate' => true,'icon' => 'arrow-left-start-on-rectangle','class' => 'cursor-pointer']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::navmenu.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('login')).'','wire:navigate' => true,'icon' => 'arrow-left-start-on-rectangle','class' => 'cursor-pointer']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                Log in
                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf)): ?>
<?php $attributes = $__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf; ?>
<?php unset($__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6498d2c45a9cd193b85bf4c51011baaf)): ?>
<?php $component = $__componentOriginal6498d2c45a9cd193b85bf4c51011baaf; ?>
<?php unset($__componentOriginal6498d2c45a9cd193b85bf4c51011baaf); ?>
<?php endif; ?>
                            <?php if (isset($component)) { $__componentOriginal6498d2c45a9cd193b85bf4c51011baaf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::navmenu.item','data' => ['href' => ''.e(route('register')).'','wire:navigate' => true,'icon' => 'user-plus','iconVariant' => 'outline','class' => 'cursor-pointer']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::navmenu.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('register')).'','wire:navigate' => true,'icon' => 'user-plus','icon-variant' => 'outline','class' => 'cursor-pointer']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                Create Account
                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf)): ?>
<?php $attributes = $__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf; ?>
<?php unset($__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6498d2c45a9cd193b85bf4c51011baaf)): ?>
<?php $component = $__componentOriginal6498d2c45a9cd193b85bf4c51011baaf; ?>
<?php unset($__componentOriginal6498d2c45a9cd193b85bf4c51011baaf); ?>
<?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0acbef9d8e9b45c80d953734a49636ad)): ?>
<?php $attributes = $__attributesOriginal0acbef9d8e9b45c80d953734a49636ad; ?>
<?php unset($__attributesOriginal0acbef9d8e9b45c80d953734a49636ad); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0acbef9d8e9b45c80d953734a49636ad)): ?>
<?php $component = $__componentOriginal0acbef9d8e9b45c80d953734a49636ad; ?>
<?php unset($__componentOriginal0acbef9d8e9b45c80d953734a49636ad); ?>
<?php endif; ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2b4bb2cd4b8f1a3c08bae49ea918b888)): ?>
<?php $attributes = $__attributesOriginal2b4bb2cd4b8f1a3c08bae49ea918b888; ?>
<?php unset($__attributesOriginal2b4bb2cd4b8f1a3c08bae49ea918b888); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2b4bb2cd4b8f1a3c08bae49ea918b888)): ?>
<?php $component = $__componentOriginal2b4bb2cd4b8f1a3c08bae49ea918b888; ?>
<?php unset($__componentOriginal2b4bb2cd4b8f1a3c08bae49ea918b888); ?>
<?php endif; ?>

            </div>
        </div>
    </section>
</nav><?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\storage\framework/views/livewire/views/8912a490.blade.php ENDPATH**/ ?>