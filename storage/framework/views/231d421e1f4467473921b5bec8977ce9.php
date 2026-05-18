<?php
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\{Layout, Defer, On, Title};
use App\Services\CartService;
use App\Services\WishlistService;
use Artesaos\SEOTools\Facades\SEOMeta;
?>

<div>
    
    <div class="bg-white border-b border-zinc-200 py-3">
        <?php if (isset($component)) { $__componentOriginalbbbea167ab072e3e3621cf7b736152aa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbbbea167ab072e3e3621cf7b736152aa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::breadcrumbs.index','data' => ['class' => 'container mx-auto px-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::breadcrumbs'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'container mx-auto px-4']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <?php if (isset($component)) { $__componentOriginalced986e8ff6641d3797206c3198c2b83 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalced986e8ff6641d3797206c3198c2b83 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::breadcrumbs.item','data' => ['href' => ''.e(route('home')).'','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::breadcrumbs.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('home')).'','wire:navigate' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                Home
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalced986e8ff6641d3797206c3198c2b83)): ?>
<?php $attributes = $__attributesOriginalced986e8ff6641d3797206c3198c2b83; ?>
<?php unset($__attributesOriginalced986e8ff6641d3797206c3198c2b83); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalced986e8ff6641d3797206c3198c2b83)): ?>
<?php $component = $__componentOriginalced986e8ff6641d3797206c3198c2b83; ?>
<?php unset($__componentOriginalced986e8ff6641d3797206c3198c2b83); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginalced986e8ff6641d3797206c3198c2b83 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalced986e8ff6641d3797206c3198c2b83 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::breadcrumbs.item','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::breadcrumbs.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Cart <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalced986e8ff6641d3797206c3198c2b83)): ?>
<?php $attributes = $__attributesOriginalced986e8ff6641d3797206c3198c2b83; ?>
<?php unset($__attributesOriginalced986e8ff6641d3797206c3198c2b83); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalced986e8ff6641d3797206c3198c2b83)): ?>
<?php $component = $__componentOriginalced986e8ff6641d3797206c3198c2b83; ?>
<?php unset($__componentOriginalced986e8ff6641d3797206c3198c2b83); ?>
<?php endif; ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbbbea167ab072e3e3621cf7b736152aa)): ?>
<?php $attributes = $__attributesOriginalbbbea167ab072e3e3621cf7b736152aa; ?>
<?php unset($__attributesOriginalbbbea167ab072e3e3621cf7b736152aa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbbbea167ab072e3e3621cf7b736152aa)): ?>
<?php $component = $__componentOriginalbbbea167ab072e3e3621cf7b736152aa; ?>
<?php unset($__componentOriginalbbbea167ab072e3e3621cf7b736152aa); ?>
<?php endif; ?>
    </div>

    <div class="mx-auto container px-4 py-4 min-h-[80svh]">

        
        <div class="flex items-center justify-between mb-4 gap-4">
            <?php if (isset($component)) { $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::heading','data' => ['level' => '1','class' => 'font-semibold! text-xl! sm:text-2xl! lg:text-3xl! font-serif!']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['level' => '1','class' => 'font-semibold! text-xl! sm:text-2xl! lg:text-3xl! font-serif!']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Cart
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $attributes = $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $component = $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->cartItems->isNotEmpty()): ?>
                <?php if (isset($component)) { $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button.index','data' => ['variant' => 'customer-outline','wire:click' => 'clearCart','class' => 'cursor-pointer','size' => 'customer']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'customer-outline','wire:click' => 'clearCart','class' => 'cursor-pointer','size' => 'customer']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    Clear Cart
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
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <div class="flex flex-col lg:flex-row lg:items-start lg:gap-6">

            
            <div class="lg:flex-1 min-w-0">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->cartItems->isEmpty()): ?>
                    <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
                        <div class="mb-8">
                            <img src="<?php echo e(asset('images/empty-states/empty-cart.svg')); ?>" alt="Empty Cart"
                                class="w-72 h-72 mx-auto" />
                        </div>

                        <?php if (isset($component)) { $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::heading','data' => ['size' => 'xl','class' => 'mb-3 text-lg! sm:text-xl! md:text-2xl!']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'xl','class' => 'mb-3 text-lg! sm:text-xl! md:text-2xl!']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Your cart is empty
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $attributes = $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $component = $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>

                        <?php if (isset($component)) { $__componentOriginal0638ebfbd490c7a414275d493e14cb4e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::text','data' => ['class' => 'mb-8 max-w-md text-xs! sm:text-sm!']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mb-8 max-w-md text-xs! sm:text-sm!']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            Looks like you haven't added anything to your cart yet.
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $attributes = $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $component = $__componentOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>

                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <?php if (isset($component)) { $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button.index','data' => ['href' => ''.e(route('shop.index')).'','wire:navigate' => true,'variant' => 'customer-primary','size' => 'customer-lg','class' => 'w-full sm:w-auto cursor-pointer']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('shop.index')).'','wire:navigate' => true,'variant' => 'customer-primary','size' => 'customer-lg','class' => 'w-full sm:w-auto cursor-pointer']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                <?php if (isset($component)) { $__componentOriginalc43f195f1b3468dd3dad13eb2914c8e1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc43f195f1b3468dd3dad13eb2914c8e1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.shopping-bag','data' => ['class' => 'w-3.5 h-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.shopping-bag'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-3.5 h-3.5']); ?>
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
                                Start Shopping
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button.index','data' => ['href' => ''.e(route('home')).'','wire:navigate' => true,'variant' => 'customer-outline','size' => 'customer-lg','class' => 'w-full sm:w-auto cursor-pointer']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('home')).'','wire:navigate' => true,'variant' => 'customer-outline','size' => 'customer-lg','class' => 'w-full sm:w-auto cursor-pointer']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                Back to Home
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
                    </div>
                <?php else: ?>
                    
                    <div class="bg-white border border-zinc-200 rounded-sm overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-zinc-50">
                                <tr>
                                    <th
                                        class="px-6 py-4 text-left text-[11px] font-bold uppercase tracking-widest text-zinc-500 border-b border-zinc-200">
                                        Product
                                    </th>
                                    <th
                                        class="px-4 py-4 text-center text-[11px] font-bold uppercase tracking-widest text-zinc-500 border-b border-zinc-200">
                                        Price
                                    </th>
                                    <th
                                        class="px-4 py-4 text-center text-[11px] font-bold uppercase tracking-widest text-zinc-500 border-b border-zinc-200">
                                        Quantity
                                    </th>
                                    <th
                                        class="px-6 py-4 text-right text-[11px] font-bold uppercase tracking-widest text-zinc-500 border-b border-zinc-200">
                                        Subtotal
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->cartItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <?php
                                        $wishlisted = in_array($item->product->id, $this->wishlistProductIds);
                                        $variant = $item->variant;

                                        // Use variant price/image when available, fall back to product
                                        $unitPrice = $variant?->final_price ?? $item->product->final_price;
                                        $lineTotal = $unitPrice * $item->quantity;
                                        $sku = $variant?->sku ?? $item->product->sku;
                                        $imageUrl = $variant?->image_path
                                            ? Storage::url($variant->image_path)
                                            : $item->product->image_url;

                                        // Variant attribute pills e.g. ['Color' => 'Red', 'Size' => 'Large']
                                        $variantAttrs = $variant
                                            ? $variant->attributeValues->mapWithKeys(
                                                fn($av) => [$av->attribute->name => $av->label ?: $av->value],
                                            )
                                            : collect();
                                    ?>

                                    <tr <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'cart-item-'.e($item->id).''; ?>wire:key="cart-item-<?php echo e($item->id); ?>" class="">
                                        
                                        <td class="px-6 py-6">
                                            <div class="flex items-center gap-4">
                                                
                                                <div
                                                    class="w-16 h-16 rounded border border-zinc-200 bg-zinc-50 overflow-hidden shrink-0">
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($imageUrl): ?>
                                                        <img class="object-cover w-full h-full"
                                                            src="<?php echo e($imageUrl); ?>"
                                                            alt="<?php echo e($item->product->name); ?>" />
                                                    <?php else: ?>
                                                        <?php if (isset($component)) { $__componentOriginal2d7605e1adbee8a1737ebec29a91da61 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2d7605e1adbee8a1737ebec29a91da61 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.photo','data' => ['class' => 'w-full h-full p-2 text-zinc-300 stroke-1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.photo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-full h-full p-2 text-zinc-300 stroke-1']); ?>
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
                                                </div>

                                                
                                                <div class="flex-1 min-w-0">
                                                    
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->product->brand): ?>
                                                        <p
                                                            class="text-[10px] font-bold uppercase tracking-widest text-zinc-400 mb-1">
                                                            <?php echo e($item->product->brand->name); ?>

                                                        </p>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                                    
                                                    <a href="<?php echo e(route('products.show', $item->product)); ?>"
                                                        wire:navigate
                                                        class="font-medium hover:underline block text-sm text-zinc-950 mb-1">
                                                        <?php echo e($item->product->name); ?>

                                                    </a>

                                                    
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($variantAttrs->isNotEmpty()): ?>
                                                        <div class="flex flex-wrap gap-1 mb-2">
                                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $variantAttrs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attrName => $attrValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                                <span class="text-[10px] text-zinc-500">
                                                                    <?php echo e($attrName); ?>: <?php echo e($attrValue); ?>

                                                                </span>
                                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                                        </div>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                                    
                                                    <div class="flex items-center gap-3 mt-2">
                                                        <button wire:click="toggleWishlist(<?php echo e($item->product->id); ?>)"
                                                            class="text-[11px] text-zinc-500 hover:text-zinc-700 transition-colors cursor-pointer">
                                                            <?php if (isset($component)) { $__componentOriginalfcc604edd6e541ab058ff166c8353443 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfcc604edd6e541ab058ff166c8353443 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.heart','data' => ['variant' => ''.e($wishlisted ? 'solid' : 'outline').'','class' => \Illuminate\Support\Arr::toCssClasses(['size-3 inline mr-1', 'text-red-500' => $wishlisted])]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.heart'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => ''.e($wishlisted ? 'solid' : 'outline').'','class' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\Illuminate\Support\Arr::toCssClasses(['size-3 inline mr-1', 'text-red-500' => $wishlisted]))]); ?>
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
                                                            <?php echo e($wishlisted ? 'Wishlisted' : 'Save for later'); ?>

                                                        </button>

                                                        <button wire:click="removeItem(<?php echo e($item->id); ?>)"
                                                            class="text-[11px] text-zinc-500 hover:text-red-500 transition-colors cursor-pointer">
                                                            <?php if (isset($component)) { $__componentOriginalca0d7d887f05c1393a9d98702b6659ea = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalca0d7d887f05c1393a9d98702b6659ea = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.trash','data' => ['class' => 'size-3 inline mr-1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.trash'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-3 inline mr-1']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalca0d7d887f05c1393a9d98702b6659ea)): ?>
<?php $attributes = $__attributesOriginalca0d7d887f05c1393a9d98702b6659ea; ?>
<?php unset($__attributesOriginalca0d7d887f05c1393a9d98702b6659ea); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalca0d7d887f05c1393a9d98702b6659ea)): ?>
<?php $component = $__componentOriginalca0d7d887f05c1393a9d98702b6659ea; ?>
<?php unset($__componentOriginalca0d7d887f05c1393a9d98702b6659ea); ?>
<?php endif; ?>
                                                            Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        
                                        <td class="px-4 py-6 text-center">
                                            <?php
                                                $regularPrice = $variant?->price ?? $item->product->price;
                                                $salePrice = $variant?->sale_price ?? $item->product->sale_price;
                                                $hasDiscount = $salePrice && $salePrice < $regularPrice;
                                            ?>

                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasDiscount): ?>
                                                <p class="text-sm font-semibold text-zinc-950">
                                                    <?php echo e(format_currency($salePrice)); ?>

                                                </p>
                                                <p class="text-xs text-zinc-400 line-through">
                                                    <?php echo e(format_currency($regularPrice)); ?>

                                                </p>
                                            <?php else: ?>
                                                <p class="text-sm font-semibold text-zinc-950">
                                                    <?php echo e(format_currency($unitPrice)); ?>

                                                </p>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </td>

                                        
                                        <td class="px-4 py-6 text-center">
                                            <div class="flex items-center justify-center">
                                                <div
                                                    class="flex items-center border border-zinc-200 rounded overflow-hidden">
                                                    <button
                                                        wire:click="updateQuantity(<?php echo e($item->id); ?>, <?php echo e($item->quantity - 1); ?>)"
                                                        class="w-8 h-8 flex items-center justify-center text-zinc-500 hover:text-zinc-700 hover:bg-zinc-50 transition-colors border-r border-zinc-200 cursor-pointer">
                                                        <?php if (isset($component)) { $__componentOriginal01ef35ccfb2d03cc6412dbe2dc9e1a50 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal01ef35ccfb2d03cc6412dbe2dc9e1a50 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.minus','data' => ['class' => 'size-3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.minus'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-3']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal01ef35ccfb2d03cc6412dbe2dc9e1a50)): ?>
<?php $attributes = $__attributesOriginal01ef35ccfb2d03cc6412dbe2dc9e1a50; ?>
<?php unset($__attributesOriginal01ef35ccfb2d03cc6412dbe2dc9e1a50); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal01ef35ccfb2d03cc6412dbe2dc9e1a50)): ?>
<?php $component = $__componentOriginal01ef35ccfb2d03cc6412dbe2dc9e1a50; ?>
<?php unset($__componentOriginal01ef35ccfb2d03cc6412dbe2dc9e1a50); ?>
<?php endif; ?>
                                                    </button>
                                                    <span
                                                        class="w-12 h-8 flex items-center justify-center text-sm font-medium bg-white">
                                                        <?php echo e($item->quantity); ?>

                                                    </span>
                                                    <button
                                                        wire:click="updateQuantity(<?php echo e($item->id); ?>, <?php echo e($item->quantity + 1); ?>)"
                                                        class="w-8 h-8 flex items-center justify-center text-zinc-500 hover:text-zinc-700 hover:bg-zinc-50 transition-colors border-l border-zinc-200 cursor-pointer">
                                                        <?php if (isset($component)) { $__componentOriginal37c717510e7a32140849d8d5dd9d632e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal37c717510e7a32140849d8d5dd9d632e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.plus','data' => ['class' => 'size-3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.plus'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-3']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal37c717510e7a32140849d8d5dd9d632e)): ?>
<?php $attributes = $__attributesOriginal37c717510e7a32140849d8d5dd9d632e; ?>
<?php unset($__attributesOriginal37c717510e7a32140849d8d5dd9d632e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal37c717510e7a32140849d8d5dd9d632e)): ?>
<?php $component = $__componentOriginal37c717510e7a32140849d8d5dd9d632e; ?>
<?php unset($__componentOriginal37c717510e7a32140849d8d5dd9d632e); ?>
<?php endif; ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>

                                        
                                        <td class="px-6 py-6 text-right">
                                            <p class="text-sm font-semibold text-zinc-950">
                                                <?php echo e(format_currency($lineTotal)); ?>

                                            </p>
                                        </td>
                                    </tr>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    
                    <div class="mt-6">
                        <?php if (isset($component)) { $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button.index','data' => ['href' => ''.e(route('shop.index')).'','wire:navigate' => true,'variant' => 'customer-outline','size' => 'customer','class' => 'cursor-pointer']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('shop.index')).'','wire:navigate' => true,'variant' => 'customer-outline','size' => 'customer','class' => 'cursor-pointer']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            <?php if (isset($component)) { $__componentOriginal93e8a1cf63877447e3f60f50005ff258 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93e8a1cf63877447e3f60f50005ff258 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.chevron-left','data' => ['class' => 'size-3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.chevron-left'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-3']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal93e8a1cf63877447e3f60f50005ff258)): ?>
<?php $attributes = $__attributesOriginal93e8a1cf63877447e3f60f50005ff258; ?>
<?php unset($__attributesOriginal93e8a1cf63877447e3f60f50005ff258); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal93e8a1cf63877447e3f60f50005ff258)): ?>
<?php $component = $__componentOriginal93e8a1cf63877447e3f60f50005ff258; ?>
<?php unset($__componentOriginal93e8a1cf63877447e3f60f50005ff258); ?>
<?php endif; ?>
                            Continue Shopping
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
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->cartItems->isNotEmpty()): ?>
                <div class="w-full lg:w-96 shrink-0 mt-4 lg:mt-0 lg:sticky lg:top-34">
                    <div class="bg-white border border-zinc-200 rounded-sm overflow-hidden">

                        
                        <div class="px-5 py-4 border-b border-zinc-200 bg-white">
                            <h3 class="text-[13px] font-bold uppercase tracking-widest text-zinc-950 font-serif">Cart
                                Summary</h3>
                        </div>

                        
                        <div class="px-5 py-4 space-y-3">
                            <div class="flex justify-between text-[13px]">
                                <span class="text-zinc-500 font-medium">Subtotal</span>
                                <span
                                    class="text-zinc-950 font-bold"><?php echo e(format_currency($this->cartSummary['subtotal'])); ?></span>
                            </div>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->cartSummary['discount'] > 0): ?>
                                <div class="flex justify-between text-[13px]">
                                    <span class="text-green-600 font-medium">Discount</span>
                                    <span class="text-green-600 font-bold">−
                                        <?php echo e(format_currency($this->cartSummary['discount'])); ?></span>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            <div class="flex justify-between text-[13px]">
                                <span class="text-zinc-500 font-medium">Shipping</span>
                                <span class="text-zinc-500 text-[11px] font-medium">Calculated at checkout</span>
                            </div>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->cartSummary['tax_enabled'] && !$this->cartSummary['tax_inclusive'] && $this->cartSummary['tax'] > 0): ?>
                                <div class="flex justify-between text-[13px]">
                                    <span class="text-zinc-500 font-medium">
                                        <?php echo e($this->cartSummary['tax_name']); ?> (<?php echo e($this->cartSummary['tax_rate']); ?>)
                                    </span>
                                    <span
                                        class="text-zinc-950 font-bold"><?php echo e(format_currency($this->cartSummary['tax'])); ?></span>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            <div class="pt-3 border-t border-zinc-200 flex justify-between items-baseline">
                                <span
                                    class="text-[14px] font-bold uppercase tracking-widest text-zinc-950">Total</span>
                                <span class="text-[24px] font-black text-primary font-barlow-condensed leading-none">
                                    <?php echo e(format_currency($this->cartSummary['subtotal'] - $this->cartSummary['discount'] + ($this->cartSummary['tax_enabled'] && !$this->cartSummary['tax_inclusive'] ? $this->cartSummary['tax'] : 0))); ?>

                                </span>
                            </div>
                        </div>

                        
                        <div class="p-4 border-t border-zinc-200 bg-white">
                            <?php if (isset($component)) { $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button.index','data' => ['wire:click' => 'proceedToCheckout','class' => 'w-full group cursor-pointer','variant' => 'customer-primary','size' => 'customer-lg']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'proceedToCheckout','class' => 'w-full group cursor-pointer','variant' => 'customer-primary','size' => 'customer-lg']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                <span>Proceed to Checkout</span>
                                 <?php $__env->slot('iconTrailing', null, []); ?> 
                                    <?php if (isset($component)) { $__componentOriginal31cb76c8d087d4f00797aeea7232b4c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal31cb76c8d087d4f00797aeea7232b4c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.chevron-right','data' => ['class' => 'size-3.5 group-hover:translate-x-1 transition-transform']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.chevron-right'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-3.5 group-hover:translate-x-1 transition-transform']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal31cb76c8d087d4f00797aeea7232b4c3)): ?>
<?php $attributes = $__attributesOriginal31cb76c8d087d4f00797aeea7232b4c3; ?>
<?php unset($__attributesOriginal31cb76c8d087d4f00797aeea7232b4c3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal31cb76c8d087d4f00797aeea7232b4c3)): ?>
<?php $component = $__componentOriginal31cb76c8d087d4f00797aeea7232b4c3; ?>
<?php unset($__componentOriginal31cb76c8d087d4f00797aeea7232b4c3); ?>
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

                            <div class="mt-3 flex items-center justify-center gap-1.5 text-xs text-zinc-400">
                                <?php if (isset($component)) { $__componentOriginalf870514c33bb1b53395ba02235f60146 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf870514c33bb1b53395ba02235f60146 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.shield-check','data' => ['class' => 'size-3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.shield-check'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-3']); ?>
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
                                <span class="uppercase tracking-widest">SSL Encrypted & Secure</span>
                            </div>
                        </div>

                        
                        <div class="py-4 px-5 border-t border-zinc-100">
                            <div class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-3">We accept
                            </div>
                            <div class="flex flex-wrap gap-1.5 mb-6">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['VISA', 'MPESA', 'MASTERCARD', 'PAYPAL']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pay): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <span
                                        class="inline-block px-2 py-1 bg-zinc-100 border border-zinc-200 rounded text-[9px] font-bold text-zinc-600 tracking-wider"><?php echo e($pay); ?></span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center gap-2 text-xs text-zinc-500">
                                    <?php if (isset($component)) { $__componentOriginal18ce857dfc449fdd246010f7208cb6d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18ce857dfc449fdd246010f7208cb6d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.arrow-path','data' => ['class' => 'size-3.5 text-zinc-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.arrow-path'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-3.5 text-zinc-400']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18ce857dfc449fdd246010f7208cb6d5)): ?>
<?php $attributes = $__attributesOriginal18ce857dfc449fdd246010f7208cb6d5; ?>
<?php unset($__attributesOriginal18ce857dfc449fdd246010f7208cb6d5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18ce857dfc449fdd246010f7208cb6d5)): ?>
<?php $component = $__componentOriginal18ce857dfc449fdd246010f7208cb6d5; ?>
<?php unset($__componentOriginal18ce857dfc449fdd246010f7208cb6d5); ?>
<?php endif; ?>
                                    <span>30-Day Easy Returns Policy</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-zinc-500">
                                    <?php if (isset($component)) { $__componentOriginal7a62c53a9a388e917a2ccf86cb1b44e8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7a62c53a9a388e917a2ccf86cb1b44e8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.truck','data' => ['class' => 'size-3.5 text-zinc-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.truck'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-3.5 text-zinc-400']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7a62c53a9a388e917a2ccf86cb1b44e8)): ?>
<?php $attributes = $__attributesOriginal7a62c53a9a388e917a2ccf86cb1b44e8; ?>
<?php unset($__attributesOriginal7a62c53a9a388e917a2ccf86cb1b44e8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7a62c53a9a388e917a2ccf86cb1b44e8)): ?>
<?php $component = $__componentOriginal7a62c53a9a388e917a2ccf86cb1b44e8; ?>
<?php unset($__componentOriginal7a62c53a9a388e917a2ccf86cb1b44e8); ?>
<?php endif; ?>
                                    <span>Free delivery on orders over KES 5,000</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>


        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->cartItems->isNotEmpty()): ?>
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('product-recommendations', ['type' => 'cart_related']);

$__keyOuter = $__key ?? null;

$__key = null;
$__componentSlots = [];

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-2873665004-0', $__key);

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

        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('product-recommendations', ['type' => 'recently_viewed']);

$__keyOuter = $__key ?? null;

$__key = null;
$__componentSlots = [];

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-2873665004-1', $__key);

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
</div><?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\storage\framework/views/livewire/views/d09935e0.blade.php ENDPATH**/ ?>