<?php

use App\Services\CartService;
use App\Services\WishlistService;
use Livewire\Component;
use App\Models\Product;

new class extends Component {
    public Product $product;
    public int $recommendedQuantity = 1;

    public bool $wishlisted = false;
    public int $cartQuantity = 1;

    public function mount(WishlistService $wishlist): void
    {
        $this->cartQuantity = $this->recommendedQuantity;
        $this->wishlisted = $wishlist->has($this->product->id);
    }

    public function increaseQuantity(): void
    {
        $this->cartQuantity++;
    }

    public function decreaseQuantity(): void
    {
        if ($this->cartQuantity > 1) {
            $this->cartQuantity--;
        }
    }

    public function addToCart(CartService $cartService): void
    {
        try {
            $cartService->addItem($this->product->id, $this->cartQuantity);
            $this->dispatch('cart-updated');
            $this->dispatch('notify', variant: 'success', message: 'Added to cart successfully');
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to add to cart');
        }
    }

    public function toggleWishlist(WishlistService $wishlistService): void
    {
        try {
            $added = $wishlistService->toggle($this->product->id);
            $this->wishlisted = $added;
            $this->dispatch('wishlist-updated');
            $this->dispatch('notify', variant: 'success', message: $added ? 'Added to wishlist' : 'Removed from wishlist');
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to update wishlist');
        }
    }
};
?>

<?php if (isset($component)) { $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::card.index','data' => ['class' => 'p-0 flex flex-col overflow-hidden']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'p-0 flex flex-col overflow-hidden']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>


    
    <div class="grid grid-cols-[100px_1fr] flex-1">

        
        <a wire:navigate href="<?php echo e(route('products.show', $product)); ?>"
            class="bg-zinc-50 flex items-center justify-center overflow-hidden">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->image_url): ?>
                <img src="<?php echo e($product->image_url); ?>" alt="<?php echo e($product->name); ?>"
                    class="w-full h-full object-cover hover:scale-105 transition-transform duration-300" loading="lazy" />
            <?php else: ?>
                <?php if (isset($component)) { $__componentOriginal2d7605e1adbee8a1737ebec29a91da61 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2d7605e1adbee8a1737ebec29a91da61 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.photo','data' => ['class' => 'w-10 h-10 text-zinc-300 stroke-1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.photo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-10 h-10 text-zinc-300 stroke-1']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2d7605e1adbee8a1737ebec29a91da61)): ?>
<?php $attributes = $__attributesOriginal2d7605e1adbee8a1737ebec29a91da61; ?>
<?php unset($__attributesOriginal2d7605e1adbee8a1737ebec29a91da61); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2d7605e1adbee8a1737ebec29a91da61)): ?>
<?php $component = $__componentOriginal2d7605e1adbee8a1737ebec29a91da61; ?>
<?php unset($__componentOriginal2d7605e1adbee8a1737ebec29a91da61); ?>
<?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </a>

        
        <div class="p-3 flex flex-col min-w-0">

            
            <a wire:navigate href="<?php echo e(route('products.show', $product)); ?>"
                class="text-sm font-medium text-zinc-800 leading-snug line-clamp-2 hover:text-secondary transition-colors mn-1.5">
                <?php echo e($product->name); ?>

            </a>

            
            <div class="flex items-baseline gap-2 mb-1.5">
                <span class="text-base font-semibold text-secondary">
                    <?php echo e($product->formatted_final_price); ?>

                </span>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->hasDiscount()): ?>
                    <span class="text-xs text-zinc-400 line-through">
                        <?php echo e($product->formatted_price); ?>

                    </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <?php
                $inStock = $product->manage_stock
                    ? $product->stock_quantity > 0
                    : $product->stock_status === 'in_stock';

                $isBackorder =
                    $product->stock_status === 'backorder' ||
                    (!$product->manage_stock && $product->stock_status === 'backorder');
            ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($inStock): ?>
                <span class="inline-flex items-center gap-1 text-[11px] font-medium text-green-700 w-fit">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>
                    In stock
                </span>
            <?php elseif($isBackorder): ?>
                <span class="inline-flex items-center gap-1 text-[11px] font-medium text-amber-700 w-fit">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 inline-block"></span>
                    Backorder
                </span>
            <?php else: ?>
                <span class="inline-flex items-center gap-1 text-[11px] font-medium text-red-600 w-fit">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-400 inline-block"></span>
                    Out of stock
                </span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <span wire:cloak wire:show="recommendedQuantity > 0" class="text-[11px] text-zinc-400">
                Recommended qty: <?php echo e($recommendedQuantity); ?>

            </span>

        </div>
    </div>

    
    <div class="border-t border-zinc-200 px-3 py-2 flex items-center gap-2">

        
        <div class="flex items-center border border-zinc-300 rounded-md overflow-hidden">
            <button wire:click="decreaseQuantity"
                class="w-7 h-7 flex items-center justify-center text-zinc-500 hover:bg-zinc-100 transition-colors cursor-pointer"
                aria-label="Decrease quantity">−</button>

            <span class="w-7 text-center text-sm font-medium text-zinc-800 border-x border-zinc-200">
                <?php echo e($cartQuantity); ?>

            </span>

            <button wire:click="increaseQuantity"
                class="w-7 h-7 flex items-center justify-center text-zinc-500 hover:bg-zinc-100 transition-colors cursor-pointer"
                aria-label="Increase quantity">+</button>
        </div>

        
        <?php if (isset($component)) { $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button.index','data' => ['wire:click' => 'addToCart','wire:loading.attr' => 'disabled','wire:target' => 'addToCart','size' => 'sm','iconVariant' => 'outline','icon' => 'shopping-cart','class' => 'cursor-pointer']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'addToCart','wire:loading.attr' => 'disabled','wire:target' => 'addToCart','size' => 'sm','icon-variant' => 'outline','icon' => 'shopping-cart','class' => 'cursor-pointer']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            Add to Cart
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

        
        <?php if (isset($component)) { $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button.index','data' => ['wire:click.stop' => 'toggleWishlist','size' => 'sm','title' => ''.e($wishlisted ? 'Remove from wishlist' : 'Add to wishlist').'','class' => 'cursor-pointer ml-auto']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click.stop' => 'toggleWishlist','size' => 'sm','title' => ''.e($wishlisted ? 'Remove from wishlist' : 'Add to wishlist').'','class' => 'cursor-pointer ml-auto']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

             <?php $__env->slot('icon', null, []); ?> 
                <?php if (isset($component)) { $__componentOriginalfcc604edd6e541ab058ff166c8353443 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfcc604edd6e541ab058ff166c8353443 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.heart','data' => ['variant' => ''.e($wishlisted ? 'solid' : 'outline').'','class' => \Illuminate\Support\Arr::toCssClasses(['size-4', 'text-red-500' => $wishlisted])]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.heart'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => ''.e($wishlisted ? 'solid' : 'outline').'','class' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\Illuminate\Support\Arr::toCssClasses(['size-4', 'text-red-500' => $wishlisted]))]); ?>
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
             <?php $__env->endSlot(); ?>
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

        
        <?php if (isset($component)) { $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button.index','data' => ['size' => 'sm','href' => ''.e(route('products.show', $product)).'','target' => '_blank','rel' => 'noopener noreferrer','title' => 'View product','icon' => 'arrow-top-right-on-square','class' => 'cursor-pointer']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'sm','href' => ''.e(route('products.show', $product)).'','target' => '_blank','rel' => 'noopener noreferrer','title' => 'View product','icon' => 'arrow-top-right-on-square','class' => 'cursor-pointer']); ?>
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
<?php /**PATH C:\Users\jonah\Herd\sheffield_ecommerce\resources\views\components\accessory-item.blade.php ENDPATH**/ ?>