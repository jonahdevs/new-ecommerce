<?php
use App\Services\{CompareService, WishlistService, CartService};
use Livewire\Component;
use App\Models\Category;
use Livewire\Attributes\{Computed, On};
use App\Enums\CategorySection;
use App\Services\QuoteBasketService;
?>

<div>
    
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

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-1982648626-0', $__key);

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

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-1982648626-1', $__key);

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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.heart','data' => ['class' => 'size-6 text-zinc-800 group-hover:text-brand-primary transition-colors']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.heart'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-6 text-zinc-800 group-hover:text-brand-primary transition-colors']); ?>
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
                                    class="absolute -top-2 -right-2 bg-brand-primary text-brand-primary-content text-xs rounded-full w-5 h-5 flex items-center justify-center font-medium">
                                    <?php echo e($wishlistCount); ?>

                                </span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <span class="text-sm font-medium text-zinc-800">Wishlist</span>
                    </a>

                    
                    <a href="<?php echo e(route('products.compare')); ?>" wire:navigate
                        class="hidden lg:flex items-center gap-2 group">
                        <div class="relative">
                            <svg class="w-6 h-6 text-zinc-800 group-hover:text-brand-primary transition-colors"
                                viewBox="0 0 24 24" fill="none">
                                <g clip-path="url(#clip0_105_1836)">
                                    <path
                                        d="M13 3.99976H6C4.89543 3.99976 4 4.89519 4 5.99976V17.9998C4 19.1043 4.89543 19.9998 6 19.9998H13M17 3.99976H18C19.1046 3.99976 20 4.89519 20 5.99976V6.99976M20 16.9998V17.9998C20 19.1043 19.1046 19.9998 18 19.9998H17M20 10.9998V12.9998M12 1.99976V21.9998"
                                        stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" />
                                </g>
                                <defs>
                                    <clipPath id="clip0_105_1836">
                                        <rect fill="white" height="24" transform="translate(0 -0.000244141)"
                                            width="24" />
                                    </clipPath>
                                </defs>
                            </svg>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($compareCount > 0): ?>
                                <span
                                    class="absolute -top-2 -right-2 bg-brand-primary text-brand-primary-content text-xs font-medium rounded-full h-5 w-5 flex items-center justify-center">
                                    <?php echo e($compareCount); ?>

                                </span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <span class="text-sm font-medium text-zinc-800">Compare</span>
                    </a>

                    
                    <a href="<?php echo e(route('cart')); ?>" wire:navigate class="flex items-center gap-2 group">
                        <div class="relative">
                            <svg class="w-6 h-6 text-zinc-800 group-hover:text-brand-primary transition-colors"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cartCount > 0): ?>
                                <span
                                    class="absolute -top-2 -right-2 bg-brand-primary text-brand-primary-content text-xs font-medium rounded-full w-5 h-5 flex items-center justify-center">
                                    <?php echo e($cartCount); ?>

                                </span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <span class="hidden lg:inline text-sm font-medium text-zinc-800">Cart</span>
                    </a>

                    
                    <?php if (isset($component)) { $__componentOriginal2b4bb2cd4b8f1a3c08bae49ea918b888 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2b4bb2cd4b8f1a3c08bae49ea918b888 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::dropdown','data' => ['position' => 'bottom','align' => 'end','hover' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['position' => 'bottom','align' => 'end','hover' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->avatar): ?>
                                <?php if (isset($component)) { $__componentOriginal2e5cdd03843a4c4d68fb9a6d7bd7e994 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2e5cdd03843a4c4d68fb9a6d7bd7e994 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::profile','data' => ['circle' => true,'avatar' => ''.e(auth()->user()->avatar).'','name' => ''.e(auth()->user()->name).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::profile'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['circle' => true,'avatar' => ''.e(auth()->user()->avatar).'','name' => ''.e(auth()->user()->name).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2e5cdd03843a4c4d68fb9a6d7bd7e994)): ?>
<?php $attributes = $__attributesOriginal2e5cdd03843a4c4d68fb9a6d7bd7e994; ?>
<?php unset($__attributesOriginal2e5cdd03843a4c4d68fb9a6d7bd7e994); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2e5cdd03843a4c4d68fb9a6d7bd7e994)): ?>
<?php $component = $__componentOriginal2e5cdd03843a4c4d68fb9a6d7bd7e994; ?>
<?php unset($__componentOriginal2e5cdd03843a4c4d68fb9a6d7bd7e994); ?>
<?php endif; ?>
                            <?php else: ?>
                                <?php if (isset($component)) { $__componentOriginal2e5cdd03843a4c4d68fb9a6d7bd7e994 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2e5cdd03843a4c4d68fb9a6d7bd7e994 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::profile','data' => ['circle' => true,'name' => ''.e(auth()->user()->name).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::profile'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['circle' => true,'name' => ''.e(auth()->user()->name).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2e5cdd03843a4c4d68fb9a6d7bd7e994)): ?>
<?php $attributes = $__attributesOriginal2e5cdd03843a4c4d68fb9a6d7bd7e994; ?>
<?php unset($__attributesOriginal2e5cdd03843a4c4d68fb9a6d7bd7e994); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2e5cdd03843a4c4d68fb9a6d7bd7e994)): ?>
<?php $component = $__componentOriginal2e5cdd03843a4c4d68fb9a6d7bd7e994; ?>
<?php unset($__componentOriginal2e5cdd03843a4c4d68fb9a6d7bd7e994); ?>
<?php endif; ?>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php else: ?>
                            <button type="button"
                                class="flex items-center gap-2 hover:text-brand-secondary transition-colors">
                                <svg class="w-6 h-6 text-zinc-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <div class="hidden lg:block">
                                    <div class="text-sm font-medium text-zinc-800">Account</div>
                                </div>
                                <svg class="w-4 h-4 hidden lg:block text-zinc-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <?php if (isset($component)) { $__componentOriginal0acbef9d8e9b45c80d953734a49636ad = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0acbef9d8e9b45c80d953734a49636ad = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::navmenu.index','data' => ['class' => \Illuminate\Support\Arr::toCssClasses([
                            'rounded-sm!',
                            'mt-4!' => auth()->check(),
                            'mt-5.5!' => !auth()->check(),
                        ])]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::navmenu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\Illuminate\Support\Arr::toCssClasses([
                            'rounded-sm!',
                            'mt-4!' => auth()->check(),
                            'mt-5.5!' => !auth()->check(),
                        ]))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

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

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                                <?php if (isset($component)) { $__componentOriginal6498d2c45a9cd193b85bf4c51011baaf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::navmenu.item','data' => ['href' => route('customer.quotations.index'),'wire:navigate' => true,'icon' => 'clipboard-document-list','iconVariant' => 'outline']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::navmenu.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('customer.quotations.index')),'wire:navigate' => true,'icon' => 'clipboard-document-list','icon-variant' => 'outline']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                    My Quotations
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

                                <span class="flex items-center gap-2">
                                    Quote Basket
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quoteCount > 0): ?>
                                        <span
                                            class="bg-amber-500 text-white text-xs font-medium rounded-full h-5 w-5 flex items-center justify-center">
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

                                Wishlist
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::navmenu.item','data' => ['href' => route('products.compare'),'wire:navigate' => true,'icon' => 'arrows-right-left','iconVariant' => 'outline']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::navmenu.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('products.compare')),'wire:navigate' => true,'icon' => 'arrows-right-left','icon-variant' => 'outline']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                <span class="flex items-center gap-2">
                                    Compare
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($compareCount > 0): ?>
                                        <span
                                            class="bg-brand-primary text-brand-primary-content text-xs font-medium rounded-full h-5 w-5 flex items-center justify-center">
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
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                                <?php if (isset($component)) { $__componentOriginal6498d2c45a9cd193b85bf4c51011baaf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6498d2c45a9cd193b85bf4c51011baaf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::navmenu.item','data' => ['href' => '#','wire:navigate' => true,'icon' => 'envelope','iconVariant' => 'outline']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::navmenu.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => '#','wire:navigate' => true,'icon' => 'envelope','icon-variant' => 'outline']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                    Messages
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
    </nav>

    
    <nav class="bg-brand-primary text-white">

        
        <section class="container mx-auto px-4 hidden lg:block">
            <ul class="m-0 flex flex-wrap border-r border-white/20 p-0" data-language="en" role="menubar"
                aria-label="Main navigation menu">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->categories->take(12); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                    <li class="w-[16.66666666666667%] cursor-pointer hover:bg-brand-primary-dark" tabindex="0"
                        role="menuitem" aria-expanded="false">
                        <div class="relative h-9.25">
                            <a href="<?php echo e(route('shop.category', ['category' => $category->slug])); ?>" wire:navigate
                                class="flex min-h-full items-center overflow-hidden text-ellipsis whitespace-nowrap border-l border-white/20 px-1.25 xl:px-2.5 border-b">
                                <img alt="" loading="eager" width="26" height="26" decoding="async"
                                    class="duration-300 max-h-6.5 max-w-6.5 max-md:hidden invert"
                                    style="color:transparent" src="<?php echo e($category->icon_url); ?>">
                                <span class="ml-2 truncate text-sm text-zinc-50"><?php echo e($category->name); ?></span>
                            </a>
                        </div>
                    </li>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </ul>
        </section>

        
        <section x-data="{
            showLeft: false,
            showRight: true,
            updateArrows() {
                const el = this.$refs.scroller;
                this.showLeft = el.scrollLeft > 10;
                this.showRight = el.scrollLeft + el.clientWidth < el.scrollWidth - 10;
            },
            scrollLeft() { this.$refs.scroller.scrollBy({ left: -160, behavior: 'smooth' }); },
            scrollRight() { this.$refs.scroller.scrollBy({ left: 160, behavior: 'smooth' }); }
        }" x-init="updateArrows()" @mouseover="$el.classList.add('hovered')"
            @mouseleave="$el.classList.remove('hovered')"
            class="group relative container mx-auto px-4 lg:hidden flex items-center">

            
            <button x-show="showLeft" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" @click="scrollLeft(); setTimeout(() => updateArrows(), 300)"
                class="invisible group-hover:visible absolute left-0 z-10 flex items-center justify-center w-8 h-full bg-linear-to-r from-brand-primary via-brand-primary/90 to-transparent text-white shrink-0 cursor-pointer"
                aria-label="Scroll left">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            
            <div x-ref="scroller" @scroll="updateArrows()"
                class="flex overflow-x-auto [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none] w-full">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                    <a href="<?php echo e(route('shop.category', ['category' => $category->slug])); ?>" wire:navigate
                        class="shrink-0 px-4 py-3 text-sm hover:opacity-80 transition-opacity duration-500 whitespace-nowrap">
                        <?php echo e($category->name); ?>

                    </a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>

            
            <button x-show="showRight" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" @click="scrollRight(); setTimeout(() => updateArrows(), 300)"
                class="invisible group-hover:visible absolute right-0 z-10 flex items-center justify-center w-8 h-full bg-linear-to-l from-brand-primary via-brand-primary/90 to-transparent text-white shrink-0 cursor-pointer"
                aria-label="Scroll right">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </section>
    </nav>
</div><?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\storage\framework/views/livewire/views/1bdc5011.blade.php ENDPATH**/ ?>