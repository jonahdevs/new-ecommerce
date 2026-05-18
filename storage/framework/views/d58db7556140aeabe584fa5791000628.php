<?php
use App\Services\{OrderSummaryService, CartService, CheckoutSession, CheckoutService};
use App\Services\Payment\ValueObjects\PaymentResponse;
use Livewire\Attributes\{Computed, On};
use Livewire\Component;
use App\Models\{Order, Address};
use App\Enums\{OrderStatus, PaymentStatus};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
?>

<div class="bg-white border border-zinc-200 rounded-sm overflow-hidden">

    
    <div class="px-5 py-4 border-b border-zinc-200 bg-white">
        <h3 class="text-[13px] font-bold uppercase tracking-widest text-zinc-950">Order Summary</h3>
    </div>

    
    <div class="divide-y divide-zinc-200 max-h-52 overflow-y-auto">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->cartItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <?php
                $variant = $item->variant;
                $imageUrl = $variant?->image_path ? Storage::url($variant->image_path) : $item->product?->image_url;
                $unitPrice = $variant?->final_price ?? $item->product->final_price;
                $variantAttrs = $variant
                    ? $variant->attributeValues->mapWithKeys(
                        fn($av) => [$av->attribute->name => $av->label ?: $av->value],
                    )
                    : collect();
            ?>
            <div class="flex items-center gap-3 px-4 py-3.5">
                <div class="w-12 h-12 rounded border border-zinc-200 bg-zinc-50 overflow-hidden shrink-0">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($imageUrl): ?>
                        <img src="<?php echo e($imageUrl); ?>" alt="<?php echo e($item->product->name); ?>"
                            class="w-full h-full object-cover" />
                    <?php else: ?>
                        <?php if (isset($component)) { $__componentOriginal2d7605e1adbee8a1737ebec29a91da61 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2d7605e1adbee8a1737ebec29a91da61 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.photo','data' => ['class' => 'w-full h-full p-2 text-zinc-300']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.photo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-full h-full p-2 text-zinc-300']); ?>
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
                    <p class="text-[13px] font-bold text-zinc-950 truncate mb-0.5"><?php echo e($item->product->name); ?></p>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($variantAttrs->isNotEmpty()): ?>
                        <p class="text-[11px] text-zinc-500 truncate font-medium mb-1">
                            <?php echo e($variantAttrs->map(fn($v, $k) => "$k: $v")->join(' · ')); ?>

                        </p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <p class="text-[11px] text-zinc-400 font-medium">Qty: <?php echo e($item->quantity); ?></p>
                </div>
                <div class="flex items-center gap-2.5 shrink-0">
                    <span class="text-[14px] font-bold text-zinc-950">
                        <?php echo e(format_currency($unitPrice * $item->quantity)); ?>

                    </span>
                    <button wire:click="removeItem(<?php echo e($item->id); ?>)" wire:confirm="Remove this item from your cart?"
                        class="text-zinc-300 hover:text-red-500 transition-colors cursor-pointer" title="Remove item">
                        <?php if (isset($component)) { $__componentOriginal155e76c41fe51242bc25d269fabf82f5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal155e76c41fe51242bc25d269fabf82f5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.x-mark','data' => ['class' => 'size-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.x-mark'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4']); ?>
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
                    </button>
                </div>
            </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    </div>

    
    <div class="px-5 py-4 bg-zinc-50 border-t border-zinc-200 space-y-3">

        <div class="flex justify-between items-center">
            <span class="text-[13px] text-zinc-500 font-medium">Subtotal</span>
            <span class="text-[14px] text-zinc-950 font-bold"><?php echo e(format_currency($this->summary['subtotal'])); ?></span>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->summary['discount'] > 0): ?>
            <div class="flex justify-between items-center">
                <span class="text-[13px] text-green-600 font-medium">Discount</span>
                <span class="text-[14px] text-green-600 font-bold">−
                    <?php echo e(format_currency($this->summary['discount'])); ?></span>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div class="flex justify-between items-center">
            <span class="text-[13px] text-zinc-500 font-medium">Shipping</span>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$this->summary['shipping_selected']): ?>
                <?php if (isset($component)) { $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::link','data' => ['href' => route('checkout.shipping'),'wire:navigate' => true,'class' => 'text-primary text-[11px] font-bold uppercase tracking-wider']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('checkout.shipping')),'wire:navigate' => true,'class' => 'text-primary text-[11px] font-bold uppercase tracking-wider']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    Select <?php if (isset($component)) { $__componentOriginal35b86e1ac5a257d741538ecc79e20be3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal35b86e1ac5a257d741538ecc79e20be3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.arrow-long-right','data' => ['class' => 'size-3 inline-block ms-0.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.arrow-long-right'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-3 inline-block ms-0.5']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal35b86e1ac5a257d741538ecc79e20be3)): ?>
<?php $attributes = $__attributesOriginal35b86e1ac5a257d741538ecc79e20be3; ?>
<?php unset($__attributesOriginal35b86e1ac5a257d741538ecc79e20be3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal35b86e1ac5a257d741538ecc79e20be3)): ?>
<?php $component = $__componentOriginal35b86e1ac5a257d741538ecc79e20be3; ?>
<?php unset($__componentOriginal35b86e1ac5a257d741538ecc79e20be3); ?>
<?php endif; ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477)): ?>
<?php $attributes = $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477; ?>
<?php unset($__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477)): ?>
<?php $component = $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477; ?>
<?php unset($__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477); ?>
<?php endif; ?>
            <?php elseif($this->summary['shipping_cost'] == 0): ?>
                <span class="text-[14px] text-green-600 font-bold">FREE</span>
            <?php else: ?>
                <span
                    class="text-[14px] text-zinc-950 font-bold"><?php echo e(format_currency($this->summary['shipping_cost'])); ?></span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->summary['tax_enabled'] && !$this->summary['tax_inclusive'] && $this->summary['tax'] > 0): ?>
            <div class="flex justify-between items-center">
                <span class="text-[13px] text-zinc-500 font-medium">
                    <?php echo e($this->summary['tax_name']); ?> (<?php echo e($this->summary['tax_rate']); ?>)
                </span>
                <span class="text-[14px] text-zinc-950 font-bold"><?php echo e(format_currency($this->summary['tax'])); ?></span>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div class="pt-3 border-t border-zinc-200 flex justify-between items-baseline">
            <span class="text-[14px] font-bold uppercase tracking-widest text-zinc-950">Total</span>
            <span class="text-[22px] font-black text-primary leading-none">
                <?php echo e(format_currency($this->summary['total'])); ?>

            </span>
        </div>
    </div>

    
    <div class="p-4 border-t border-zinc-200 bg-white">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($slot)): ?>
            
            <?php echo e($slot); ?>

        <?php else: ?>
            
            <?php if (isset($component)) { $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button.index','data' => ['wire:click' => 'completeOrder','wire:loading.attr' => 'disabled','wire:target' => 'completeOrder','class' => 'w-full group cursor-pointer','variant' => 'customer-primary','size' => 'customer-lg','disabled' => !$this->summary['shipping_selected'] || $isProcessing]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'completeOrder','wire:loading.attr' => 'disabled','wire:target' => 'completeOrder','class' => 'w-full group cursor-pointer','variant' => 'customer-primary','size' => 'customer-lg','disabled' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(!$this->summary['shipping_selected'] || $isProcessing)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <span wire:loading.remove wire:target="completeOrder">Place Order</span>
                <span wire:loading wire:target="completeOrder" class="flex items-center gap-2">
                    <?php if (isset($component)) { $__componentOriginal18ce857dfc449fdd246010f7208cb6d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18ce857dfc449fdd246010f7208cb6d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.arrow-path','data' => ['class' => 'size-3.5 animate-spin']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.arrow-path'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-3.5 animate-spin']); ?>
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
                    Processing...
                </span>
                 <?php $__env->slot('iconTrailing', null, []); ?> 
                    <?php if (isset($component)) { $__componentOriginal31cb76c8d087d4f00797aeea7232b4c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal31cb76c8d087d4f00797aeea7232b4c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.chevron-right','data' => ['class' => 'size-3.5 group-hover:translate-x-1 transition-transform','wire:loading.class' => 'hidden','wire:target' => 'completeOrder']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.chevron-right'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-3.5 group-hover:translate-x-1 transition-transform','wire:loading.class' => 'hidden','wire:target' => 'completeOrder']); ?>
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

            <div class="mt-3 flex items-center justify-center gap-1.5 text-xs text-zinc-400 font-medium">
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
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    
    <div class="py-4 px-5 border-t border-zinc-100">
        <div class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-3">We accept</div>
        <div class="flex flex-wrap gap-1.5 mb-6">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['VISA', 'MPESA', 'MASTERCARD', 'PAYPAL']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pay): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <span
                    class="inline-block px-2 py-1 bg-zinc-100 border border-zinc-200 rounded text-[9px] font-extrabold text-zinc-600 tracking-wider"><?php echo e($pay); ?></span>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>

        <div class="space-y-3">
            <div class="flex items-center gap-2.5 text-[12px] text-zinc-500 font-medium">
                <?php if (isset($component)) { $__componentOriginal18ce857dfc449fdd246010f7208cb6d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18ce857dfc449fdd246010f7208cb6d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.arrow-path','data' => ['class' => 'size-4 text-zinc-400 shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.arrow-path'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4 text-zinc-400 shrink-0']); ?>
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
            <div class="flex items-center gap-2.5 text-[12px] text-zinc-500 font-medium">
                <?php if (isset($component)) { $__componentOriginal7a62c53a9a388e917a2ccf86cb1b44e8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7a62c53a9a388e917a2ccf86cb1b44e8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.truck','data' => ['class' => 'size-4 text-zinc-400 shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.truck'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4 text-zinc-400 shrink-0']); ?>
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

    
    <?php if (isset($component)) { $__componentOriginal8cc9d3143946b992b324617832699c5f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8cc9d3143946b992b324617832699c5f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::modal.index','data' => ['name' => 'stk-waiting','class' => 'max-w-sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'stk-waiting','class' => 'max-w-sm']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <div x-data="{
            timeLeft: 60,
            checkoutRequestId: null,
            interval: null,
        
            init() {
                Livewire.on('stk-push-initiated', ({ checkoutRequestId }) => {
                    this.checkoutRequestId = checkoutRequestId;
                    $flux.modal('stk-waiting').show();
                    this.startCountdown();
                });
            },
        
            startCountdown() {
                if (this.interval) clearInterval(this.interval);
                this.timeLeft = 60;
                this.interval = setInterval(() => {
                    this.timeLeft--;
                    if (this.timeLeft <= 0) {
                        clearInterval(this.interval);
                        window.location.href = '<?php echo e(route('customer.orders.index')); ?>';
                    }
                }, 1000);
            },
        }">
            <div class="text-center p-6">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <?php if (isset($component)) { $__componentOriginalf08af65e6f8e1abc9ae6e66c02da0bd2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf08af65e6f8e1abc9ae6e66c02da0bd2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.device-phone-mobile','data' => ['class' => 'size-8 text-green-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.device-phone-mobile'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-8 text-green-600']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf08af65e6f8e1abc9ae6e66c02da0bd2)): ?>
<?php $attributes = $__attributesOriginalf08af65e6f8e1abc9ae6e66c02da0bd2; ?>
<?php unset($__attributesOriginalf08af65e6f8e1abc9ae6e66c02da0bd2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf08af65e6f8e1abc9ae6e66c02da0bd2)): ?>
<?php $component = $__componentOriginalf08af65e6f8e1abc9ae6e66c02da0bd2; ?>
<?php unset($__componentOriginalf08af65e6f8e1abc9ae6e66c02da0bd2); ?>
<?php endif; ?>
                </div>

                <?php if (isset($component)) { $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::heading','data' => ['size' => 'lg','class' => 'mb-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'lg','class' => 'mb-2']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Check your phone <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::text','data' => ['class' => 'text-zinc-500 text-sm mb-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-zinc-500 text-sm mb-6']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    An M-Pesa payment request has been sent to your phone.
                    Enter your PIN to complete payment.
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

                <div class="text-2xl font-mono font-bold text-zinc-800 mb-2" x-text="timeLeft + 's'"></div>
                <div class="w-full bg-zinc-100 rounded-full h-1.5 mb-6">
                    <div class="bg-green-500 h-1.5 rounded-full transition-all duration-1000"
                        :style="'width: ' + (timeLeft / 60 * 100) + '%'"></div>
                </div>

                <?php if (isset($component)) { $__componentOriginal0638ebfbd490c7a414275d493e14cb4e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::text','data' => ['class' => 'text-xs text-zinc-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-xs text-zinc-400']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Waiting for confirmation... <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $attributes = $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $component = $__componentOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
            </div>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8cc9d3143946b992b324617832699c5f)): ?>
<?php $attributes = $__attributesOriginal8cc9d3143946b992b324617832699c5f; ?>
<?php unset($__attributesOriginal8cc9d3143946b992b324617832699c5f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8cc9d3143946b992b324617832699c5f)): ?>
<?php $component = $__componentOriginal8cc9d3143946b992b324617832699c5f; ?>
<?php unset($__componentOriginal8cc9d3143946b992b324617832699c5f); ?>
<?php endif; ?>
</div><?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\storage\framework/views/livewire/views/2717a403.blade.php ENDPATH**/ ?>