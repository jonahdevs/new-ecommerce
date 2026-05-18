<?php
use App\Enums\{OrderStatus, PaymentStatus};
use App\Models\Order;
use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Component;
use App\Services\CartService;
?>

<div>
    <div class="bg-white border border-zinc-200">
        <div class="p-4 bg-white border-b border-zinc-200 flex items-center justify-between flex-wrap gap-3">
            <a href="<?php echo e(route('customer.orders.index')); ?>" wire:navigate
                class="flex items-center gap-1.5 text-[12px] font-bold tracking-widest uppercase text-zinc-500 transition-colors hover:text-primary cursor-pointer">
                <?php if (isset($component)) { $__componentOriginal93e8a1cf63877447e3f60f50005ff258 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93e8a1cf63877447e3f60f50005ff258 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.chevron-left','data' => ['class' => 'w-3.5 h-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.chevron-left'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-3.5 h-3.5']); ?>
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
                Back to Orders
            </a>
            <div class="font-serif text-[18px] font-black text-zinc-950">
                ORDER <em class="text-primary not-italic">#<?php echo e($order->reference); ?></em>
            </div>
            <?php if (isset($component)) { $__componentOriginal4cc377eda9b63b796b6668ee7832d023 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4cc377eda9b63b796b6668ee7832d023 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::badge.index','data' => ['color' => $order->status->color()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($order->status->color())]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
<?php echo e($order->status->label()); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4cc377eda9b63b796b6668ee7832d023)): ?>
<?php $attributes = $__attributesOriginal4cc377eda9b63b796b6668ee7832d023; ?>
<?php unset($__attributesOriginal4cc377eda9b63b796b6668ee7832d023); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4cc377eda9b63b796b6668ee7832d023)): ?>
<?php $component = $__componentOriginal4cc377eda9b63b796b6668ee7832d023; ?>
<?php unset($__componentOriginal4cc377eda9b63b796b6668ee7832d023); ?>
<?php endif; ?>
        </div>

        <div class="p-5 flex flex-col gap-5">

            
            
            
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->wasConvertedFromQuote() && $order->quote): ?>
                <div class="flex items-center gap-3 p-3 bg-blue-50 border border-blue-200 rounded-sm mb-6">
                    <?php if (isset($component)) { $__componentOriginal372652fcc747cd9bb1f591829ed1255a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal372652fcc747cd9bb1f591829ed1255a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.tag','data' => ['class' => 'size-4 shrink-0 text-blue-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.tag'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4 shrink-0 text-blue-500']); ?>
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
                    <?php if (isset($component)) { $__componentOriginal0638ebfbd490c7a414275d493e14cb4e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::text','data' => ['class' => 'text-sm text-blue-800 flex-1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-sm text-blue-800 flex-1']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                        This order was created from quote
                        <?php if (isset($component)) { $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::link','data' => ['href' => route('customer.quotes.show', $order->quote),'wire:navigate' => true,'class' => 'font-medium']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('customer.quotes.show', $order->quote)),'wire:navigate' => true,'class' => 'font-medium']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            <?php echo e($order->quote->reference); ?>

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
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pb-6 border-b border-zinc-200">
                <div>
                    <div class="text-[10px] font-bold tracking-widest uppercase text-zinc-500 mb-1">Placed On</div>
                    <div class="text-[14px] font-semibold text-zinc-950"><?php echo e($order->created_at->format('M j, Y')); ?>

                    </div>
                </div>
            </div>

            
            <div class="">
                <h3 class="text-base font-bold uppercase tracking-wider text-zinc-900 mb-4 font-serif">Items in Your
                    Order
                </h3>

                <div class="flex flex-col border border-zinc-200">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php
                            $name = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—');
                            $sku = $item->product_snapshot['sku'] ?? null;
                            $brand = $item->product?->brand?->name ?? null;
                            $imagePath = $item->product_image_url ?? $item->product?->image_url;
                            $inStock = ($item->product?->stock_quantity ?? 0) > 0;
                            $variantAttrs = $item->product_snapshot['variant']['attributes'] ?? [];
                        ?>

                        <div class="flex items-center gap-3.5 p-3.5 border-b border-zinc-200 last:border-b-0">
                            <div class="w-14 h-14 bg-zinc-50 flex items-center justify-center shrink-0 relative">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($imagePath): ?>
                                    <img src="<?php echo e(asset($imagePath)); ?>" alt="<?php echo e($name); ?>"
                                        class="w-[90%] h-[90%] object-contain">
                                <?php else: ?>
                                    <?php if (isset($component)) { $__componentOriginal2d7605e1adbee8a1737ebec29a91da61 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2d7605e1adbee8a1737ebec29a91da61 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.photo','data' => ['class' => 'w-8 h-8 text-zinc-200']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.photo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-8 h-8 text-zinc-200']); ?>
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
                                <div class="text-[9px] font-bold tracking-widest uppercase text-zinc-500 mb-0.5">
                                    <?php echo e($brand); ?> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sku): ?>
                                        · SKU: <?php echo e($sku); ?>

                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div class="text-[13px] font-semibold text-zinc-950 mb-0.5 truncate">
                                    <?php echo e($name); ?>

                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($variantAttrs)): ?>
                                    <div class="text-[10px] text-zinc-400 mb-0.5">
                                        <?php echo e(collect($variantAttrs)->map(fn($v, $k) => "$k: $v")->join(' · ')); ?>

                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <div class="text-[11px] text-zinc-500">Qty: <?php echo e($item->quantity); ?></div>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <div class="text-[13px] font-bold text-zinc-950 shrink-0">
                                    <?php echo e(format_currency($item->total_cents / 100)); ?></div>
                                <button
                                    class="text-[10px] font-bold uppercase text-primary tracking-widest hover:underline cursor-pointer"
                                    wire:click="buyAgain(<?php echo e($item->product_id); ?>)"
                                    <?php if(!$inStock): ?> disabled <?php endif; ?>>
                                    <?php echo e($inStock ? 'Buy Again' : 'Out of Stock'); ?>

                                </button>
                            </div>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-5">
                    
                    

                    
                    <div class="bg-zinc-50 border border-zinc-200 rounded-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-zinc-200 bg-white">
                            <h3 class="text-[13px] font-bold uppercase tracking-widest text-zinc-950 font-serif">Order
                                Summary</h3>
                        </div>
                        <div class="p-5 space-y-3">
                            <div class="flex justify-between text-[13px]">
                                <span class="text-zinc-500 font-medium">Subtotal</span>
                                <span class="text-zinc-950 font-bold"><?php echo e(format_currency($order->subtotal)); ?></span>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->discount > 0): ?>
                                <div class="flex justify-between text-[13px]">
                                    <span class="text-green-600 font-medium">Discount</span>
                                    <span class="text-green-600 font-bold">−
                                        <?php echo e(format_currency($order->discount)); ?></span>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <div class="flex justify-between text-[13px]">
                                <span class="text-zinc-500 font-medium">Shipping</span>
                                <span class="text-zinc-950 font-bold">
                                    <?php echo e($order->shipping == 0 ? 'FREE' : format_currency($order->shipping)); ?>

                                </span>
                            </div>
                            <div class="pt-3 border-t border-zinc-200 flex justify-between items-baseline">
                                <span class="text-[14px] font-bold uppercase tracking-widest text-zinc-950">Total</span>
                                <span class="text-[24px] font-black text-primary font-barlow-condensed leading-none">
                                    <?php echo e(format_currency($order->total)); ?>

                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($component)) { $__componentOriginalc7f54b8f583d0f8b3734fff24e9ce48e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc7f54b8f583d0f8b3734fff24e9ce48e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.card','data' => ['title' => 'Delivery Information','bodyClass' => 'py-3 px-5 text-[13px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Delivery Information','bodyClass' => 'py-3 px-5 text-[13px]']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                        <div class="font-bold text-zinc-950 mb-1">
                            <?php echo e(trim(($order->shipping_address['first_name'] ?? '') . ' ' . ($order->shipping_address['last_name'] ?? '')) ?: $order->shipping_address['full_name'] ?? 'N/A'); ?>

                        </div>
                        <div class="text-zinc-500">
                            <?php echo e(format_phone($order->shipping_address['phone_number'] ?? '')); ?><br>
                            <?php echo e($order->shipping_address['address'] ?? 'N/A'); ?><br>
                            <?php echo e(implode(', ', array_filter([$order->shipping_address['area'] ?? null, $order->shipping_address['county'] ?? null]))); ?>

                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->shipping_snapshot['method_name'] ?? null): ?>
                            <div class="mt-2 pt-2 border-t border-zinc-100">
                                <span class="text-[11px] font-bold uppercase text-zinc-400 block mb-0.5">Method</span>
                                <span class="font-semibold"><?php echo e($order->shipping_snapshot['method_name']); ?></span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->shipping_snapshot['delivery_window'] ?? null): ?>
                                    <span class="text-zinc-400 block text-[11px]">Est.
                                        <?php echo e($order->shipping_snapshot['delivery_window']); ?></span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc7f54b8f583d0f8b3734fff24e9ce48e)): ?>
<?php $attributes = $__attributesOriginalc7f54b8f583d0f8b3734fff24e9ce48e; ?>
<?php unset($__attributesOriginalc7f54b8f583d0f8b3734fff24e9ce48e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc7f54b8f583d0f8b3734fff24e9ce48e)): ?>
<?php $component = $__componentOriginalc7f54b8f583d0f8b3734fff24e9ce48e; ?>
<?php unset($__componentOriginalc7f54b8f583d0f8b3734fff24e9ce48e); ?>
<?php endif; ?>

                    <div class="bg-white border border-zinc-200 rounded-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-zinc-200">
                            <h3 class="text-[12px] font-bold uppercase tracking-widest text-zinc-950 font-serif">
                                Payment Method</h3>
                        </div>
                        <div class="p-5">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->payment): ?>
                                <div class="flex items-center justify-between mb-4">
                                    <div class="text-[13px] font-bold text-zinc-950 uppercase tracking-tight">
                                        <?php echo e($order->payment->gateway ?? '—'); ?>

                                    </div>
                                    <?php if (isset($component)) { $__componentOriginal4cc377eda9b63b796b6668ee7832d023 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4cc377eda9b63b796b6668ee7832d023 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::badge.index','data' => ['size' => 'sm','color' => $order->payment->status->color()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'sm','color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($order->payment->status->color())]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                        <?php echo e($order->payment->status->label()); ?>

                                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4cc377eda9b63b796b6668ee7832d023)): ?>
<?php $attributes = $__attributesOriginal4cc377eda9b63b796b6668ee7832d023; ?>
<?php unset($__attributesOriginal4cc377eda9b63b796b6668ee7832d023); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4cc377eda9b63b796b6668ee7832d023)): ?>
<?php $component = $__componentOriginal4cc377eda9b63b796b6668ee7832d023; ?>
<?php unset($__componentOriginal4cc377eda9b63b796b6668ee7832d023); ?>
<?php endif; ?>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex justify-between text-[12px]">
                                        <span class="text-zinc-500">Amount Paid</span>
                                        <span
                                            class="font-bold text-zinc-950"><?php echo e(format_currency(($order->payment->amount_cents ?? 0) / 100)); ?></span>
                                    </div>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->payment->paid_at): ?>
                                        <div class="flex justify-between text-[12px]">
                                            <span class="text-zinc-500">Transaction Date</span>
                                            <span
                                                class="font-bold text-zinc-950"><?php echo e($order->payment->paid_at->format('M j, Y')); ?></span>
                                        </div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>

                                
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->isPaid): ?>
                                    <div class="mt-6 pt-5 border-t border-zinc-200">
                                        <h4 class="text-[11px] font-bold uppercase tracking-widest text-zinc-400 mb-3">
                                            Tax Invoice</h4>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->hasKraReceipt): ?>
                                            <?php if (isset($component)) { $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button.index','data' => ['tag' => 'a','href' => route('customer.orders.receipt', $order),'size' => 'sm','variant' => 'customer-primary','icon' => 'arrow-down-tray','class' => 'w-full font-bold uppercase tracking-wider text-[10px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tag' => 'a','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('customer.orders.receipt', $order)),'size' => 'sm','variant' => 'customer-primary','icon' => 'arrow-down-tray','class' => 'w-full font-bold uppercase tracking-wider text-[10px]']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                                Download Invoice
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
                                            <div class="mt-2 text-center">
                                                <div
                                                    class="text-[10px] text-emerald-600 font-bold uppercase tracking-wide">
                                                    KRA Validated</div>
                                                <div class="text-[9px] text-zinc-500 mt-0.5">CU No:
                                                    <?php echo e($order->kra_cu_number); ?></div>
                                            </div>
                                        <?php elseif($this->isAwaitingKraValidation): ?>
                                            <div
                                                class="p-3 bg-purple-50 border border-purple-100 rounded-sm text-[11px] text-purple-700 leading-relaxed font-medium">
                                                Pending KRA validation. This usually takes a few minutes.
                                            </div>
                                        <?php elseif($this->hasSapSyncFailed): ?>
                                            <div
                                                class="p-3 bg-red-50 border border-red-100 rounded-sm text-[11px] text-red-700 leading-relaxed font-medium">
                                                Invoice generation issue. Support has been notified.
                                            </div>
                                        <?php else: ?>
                                            <div
                                                class="p-3 bg-zinc-50 border border-zinc-100 rounded-sm text-[11px] text-zinc-500 leading-relaxed italic">
                                                Your invoice is being prepared...
                                            </div>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php else: ?>
                                <div class="p-4 bg-zinc-50 border border-zinc-200 border-dashed text-center">
                                    <div class="text-[12px] text-zinc-400 italic">No payment info available</div>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="flex flex-wrap gap-2.5">
                <?php if (isset($component)) { $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button.index','data' => ['tag' => 'a','variant' => 'customer-outline','href' => ''.e(route('customer.orders.tracking', $order)).'','wire:navigate' => true,'size' => 'customer','class' => 'px-5!']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tag' => 'a','variant' => 'customer-outline','href' => ''.e(route('customer.orders.tracking', $order)).'','wire:navigate' => true,'size' => 'customer','class' => 'px-5!']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    <?php if (isset($component)) { $__componentOriginal4a4fffe04433d6d6be16f26ad2650578 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4a4fffe04433d6d6be16f26ad2650578 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.clock','data' => ['class' => 'w-3.5 h-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.clock'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-3.5 h-3.5']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4a4fffe04433d6d6be16f26ad2650578)): ?>
<?php $attributes = $__attributesOriginal4a4fffe04433d6d6be16f26ad2650578; ?>
<?php unset($__attributesOriginal4a4fffe04433d6d6be16f26ad2650578); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4a4fffe04433d6d6be16f26ad2650578)): ?>
<?php $component = $__componentOriginal4a4fffe04433d6d6be16f26ad2650578; ?>
<?php unset($__componentOriginal4a4fffe04433d6d6be16f26ad2650578); ?>
<?php endif; ?>
                    Track Order
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

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->isPaid && $this->hasKraReceipt): ?>
                    <?php if (isset($component)) { $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button.index','data' => ['tag' => 'a','variant' => 'customer-outline','href' => ''.e(route('customer.orders.receipt', $order)).'','size' => 'customer','class' => 'px-5!']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tag' => 'a','variant' => 'customer-outline','href' => ''.e(route('customer.orders.receipt', $order)).'','size' => 'customer','class' => 'px-5!']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                        <?php if (isset($component)) { $__componentOriginal2ec15606c28ac475c0acbe5c53b8b490 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ec15606c28ac475c0acbe5c53b8b490 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.arrow-down-tray','data' => ['class' => 'w-3.5 h-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.arrow-down-tray'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-3.5 h-3.5']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2ec15606c28ac475c0acbe5c53b8b490)): ?>
<?php $attributes = $__attributesOriginal2ec15606c28ac475c0acbe5c53b8b490; ?>
<?php unset($__attributesOriginal2ec15606c28ac475c0acbe5c53b8b490); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2ec15606c28ac475c0acbe5c53b8b490)): ?>
<?php $component = $__componentOriginal2ec15606c28ac475c0acbe5c53b8b490; ?>
<?php unset($__componentOriginal2ec15606c28ac475c0acbe5c53b8b490); ?>
<?php endif; ?>
                        Download Invoice
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

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->status->value === OrderStatus::DELIVERED->value): ?>
                    <?php if (isset($component)) { $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button.index','data' => ['size' => 'customer','variant' => 'customer-outline','class' => 'px-5!']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'customer','variant' => 'customer-outline','class' => 'px-5!']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                        <?php if (isset($component)) { $__componentOriginal0bc6ca59f258b8d2577c76df279598af = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0bc6ca59f258b8d2577c76df279598af = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.star','data' => ['class' => 'w-3.5 h-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.star'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-3.5 h-3.5']); ?>
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
                        Leave Review
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

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->isPaid): ?>
                <div class="mt-2">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->isAwaitingKraValidation): ?>
                        <div class="flex items-start gap-2 p-3 bg-purple-50 border border-purple-200 rounded-sm">
                            <?php if (isset($component)) { $__componentOriginal4a4fffe04433d6d6be16f26ad2650578 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4a4fffe04433d6d6be16f26ad2650578 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.clock','data' => ['class' => 'size-4 shrink-0 mt-0.5 text-purple-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.clock'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4 shrink-0 mt-0.5 text-purple-500']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4a4fffe04433d6d6be16f26ad2650578)): ?>
<?php $attributes = $__attributesOriginal4a4fffe04433d6d6be16f26ad2650578; ?>
<?php unset($__attributesOriginal4a4fffe04433d6d6be16f26ad2650578); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4a4fffe04433d6d6be16f26ad2650578)): ?>
<?php $component = $__componentOriginal4a4fffe04433d6d6be16f26ad2650578; ?>
<?php unset($__componentOriginal4a4fffe04433d6d6be16f26ad2650578); ?>
<?php endif; ?>
                            <div class="text-xs text-purple-700">
                                Invoice pending KRA validation. This usually completes within a few minutes. We'll email
                                you
                                once it's ready.
                            </div>
                        </div>
                    <?php elseif($this->hasSapSyncFailed): ?>
                        <div class="flex items-start gap-2 p-3 bg-rose-50 border border-rose-200 rounded-sm">
                            <?php if (isset($component)) { $__componentOriginal7f0e8d69add49581695c1337b3f85fff = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7f0e8d69add49581695c1337b3f85fff = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.exclamation-triangle','data' => ['class' => 'size-4 shrink-0 mt-0.5 text-rose-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.exclamation-triangle'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4 shrink-0 mt-0.5 text-rose-500']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7f0e8d69add49581695c1337b3f85fff)): ?>
<?php $attributes = $__attributesOriginal7f0e8d69add49581695c1337b3f85fff; ?>
<?php unset($__attributesOriginal7f0e8d69add49581695c1337b3f85fff); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7f0e8d69add49581695c1337b3f85fff)): ?>
<?php $component = $__componentOriginal7f0e8d69add49581695c1337b3f85fff; ?>
<?php unset($__componentOriginal7f0e8d69add49581695c1337b3f85fff); ?>
<?php endif; ?>
                            <div class="text-xs text-rose-700">
                                Invoice generation encountered an issue. Our team has been notified. Please contact
                                support
                                if this persists.
                            </div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


            <div class="text-center mt-6">
                <div class="text-[13px] text-zinc-500">
                    Need help with this order? <a href="#"
                        class="text-primary font-bold hover:underline">Contact
                        Support</a>
                </div>
            </div>
        </div>
    </div>
</div>

    <?php
        $__scriptKey = '1969286099-0';
        ob_start();
    ?>
<script>
    if (window.Echo) {
        window.Echo.private('order.<?php echo e($order->id); ?>')
            .listen('.order.updated', (e) => {
                $wire.handleOrderUpdate(e);
            });
    }
</script>
    <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?><?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\storage\framework/views/livewire/views/727bb877.blade.php ENDPATH**/ ?>