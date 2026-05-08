<?php $__env->startSection('title', 'Quotation ' . $quote->reference); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $logoPath = public_path('logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }
    ?>

    
    
    
    <div class="px-10 py-6 flex justify-between items-start border-b border-gray-200">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 uppercase">Quotation</h1>
            <div class="flex items-center gap-2 mt-2 text-sm">
                <span class="text-gray-500">Quote No:</span>
                <span class="text-gray-900 font-semibold">#<?php echo e($quote->reference); ?></span>
            </div>
            <div class="flex items-center gap-2 mt-1 text-sm">
                <span class="text-gray-500">Date:</span>
                <span class="text-gray-900 font-semibold"><?php echo e($quote->created_at->format('d M, Y')); ?></span>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->expires_at): ?>
                <div class="flex items-center gap-2 mt-1 text-sm">
                    <span class="text-gray-500">Valid Until:</span>
                    <span class="text-gray-900 font-semibold"><?php echo e($quote->expires_at->format('d M, Y')); ?></span>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <div class="text-right">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($logoBase64): ?>
                <img src="<?php echo e($logoBase64); ?>" alt="Sheffield Africa" class="h-12 w-auto ml-auto">
            <?php else: ?>
                <div class="text-xl font-bold text-brand uppercase">SHEFFIELD</div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    
    
    
    <div class="px-10 py-6 flex justify-between gap-6">
        
        <div class="flex-1">
            <div class="border border-gray-300">
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-300">
                    <div class="text-xs font-bold text-gray-700 uppercase">Customer Information</div>
                </div>
                <div class="p-4 space-y-2">
                    <div class="font-semibold text-sm text-gray-900"><?php echo e($quote->customerName()); ?></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->customerEmail()): ?>
                        <div class="text-xs text-gray-600"><?php echo e($quote->customerEmail()); ?></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->customerPhone()): ?>
                        <div class="text-xs text-gray-600"><?php echo e($quote->customerPhone()); ?></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->preferred_county || $quote->preferred_area): ?>
                        <div class="pt-3 mt-3 border-t border-gray-200">
                            <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Preferred Location</div>
                            <div class="text-xs text-gray-600">
                                <?php echo e(implode(', ', array_filter([$quote->preferred_area, $quote->preferred_county]))); ?>

                            </div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </div>

        
        <div class="flex-1">
            <div class="border border-gray-300">
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-300">
                    <div class="text-xs font-bold text-gray-700 uppercase">Quote Details</div>
                </div>
                <div class="p-4 space-y-3">
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase">Status</div>
                        <div class="text-sm font-semibold text-gray-900 mt-0.5">
                            <?php echo e($quote->status->label()); ?>

                        </div>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->quoted_at): ?>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase">Quoted On</div>
                            <div class="text-sm font-semibold text-gray-900 mt-0.5">
                                <?php echo e($quote->quoted_at->format('d M, Y')); ?>

                            </div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase">Currency</div>
                        <div class="text-sm font-semibold text-gray-900 mt-0.5"><?php echo e($quote->currency); ?></div>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->expires_at): ?>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase">Validity</div>
                            <div class="text-sm font-semibold text-gray-900 mt-0.5">
                                <?php echo e($quote->expires_at->diffInDays($quote->quoted_at ?? $quote->created_at)); ?> days
                            </div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    
    
    
    <div class="px-10 py-6">
        <table class="w-full border-collapse">
            <thead class="bg-slate-50">
                <tr class="border-b-2 border-gray-300">
                    <th class="py-3 ps-2 text-xs font-bold text-gray-700 uppercase text-left">#</th>
                    <th class="py-3 text-xs font-bold text-gray-700 uppercase text-left">Description</th>
                    <th class="py-3 text-xs font-bold text-gray-700 uppercase text-center">Qty</th>
                    <th class="py-3 text-xs font-bold text-gray-700 uppercase text-right">Unit Price</th>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->items->some(fn($item) => $item->hasCustomPrice())): ?>
                        <th class="py-3 text-xs font-bold text-gray-700 uppercase text-right">Quoted Price</th>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <th class="py-3 pe-2 text-xs font-bold text-gray-700 uppercase text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $quote->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php
                        $name = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—');
                        $sku = $item->product_snapshot['sku'] ?? '—';
                        $showQuotedPrice = $quote->items->some(fn($i) => $i->hasCustomPrice());
                    ?>
                    <tr class="border-b border-gray-200">
                        <td class="py-3 text-xs text-gray-500"><?php echo e($index + 1); ?></td>
                        <td class="py-3">
                            <div class="text-sm font-semibold text-gray-900"><?php echo e($name); ?></div>
                            <div class="text-xs text-gray-500 mt-0.5"><?php echo e($sku); ?></div>
                        </td>
                        <td class="py-3 text-sm text-gray-900 text-center"><?php echo e($item->quantity); ?></td>
                        <td class="py-3 text-sm text-gray-900 text-right">
                            <?php echo e(number_format($item->original_price_cents / 100, 2)); ?>

                        </td>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showQuotedPrice): ?>
                            <td class="py-3 text-sm font-semibold text-gray-900 text-right">
                                <?php echo e(number_format(($item->quoted_price_cents ?? $item->original_price_cents) / 100, 2)); ?>

                            </td>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <td class="py-3 text-sm font-semibold text-gray-900 text-right">
                            <?php echo e(number_format($item->total_cents / 100, 2)); ?>

                        </td>
                    </tr>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </tbody>
        </table>
    </div>

    
    
    
    <div class="px-10 py-6 flex justify-end">
        <div class="w-80">
            <div class="space-y-2">
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Subtotal</span>
                    <span class="font-semibold text-gray-900"><?php echo e($quote->currency); ?>

                        <?php echo e(number_format($quote->subtotal_cents / 100, 2)); ?></span>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->discount_cents > 0): ?>
                    <div class="flex justify-between text-sm text-red-600">
                        <span>Discount</span>
                        <span class="font-semibold">-<?php echo e($quote->currency); ?>

                            <?php echo e(number_format($quote->discount_cents / 100, 2)); ?></span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->shipping_cents > 0): ?>
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Estimated Shipping</span>
                        <span class="font-semibold text-gray-900"><?php echo e($quote->currency); ?>

                            <?php echo e(number_format($quote->shipping_cents / 100, 2)); ?></span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <div class="pt-3 mt-3 border-t-2 border-gray-300 flex justify-between items-center">
                    <span class="text-base font-bold text-gray-900 uppercase">Total Quote</span>
                    <span class="text-xl font-bold text-gray-900"><?php echo e($quote->currency); ?>

                        <?php echo e(number_format($quote->total_cents / 100, 2)); ?></span>
                </div>
            </div>
        </div>
    </div>

    
    
    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->customer_notes): ?>
        <div class="px-10 py-4 border-t border-gray-200">
            <div class="text-xs font-bold text-gray-500 uppercase mb-1">Customer Notes</div>
            <div class="text-sm text-gray-600 italic">
                "<?php echo e($quote->customer_notes); ?>"
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    
    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->admin_notes): ?>
        <div class="px-10 py-4 border-t border-gray-200">
            <div class="text-xs font-bold text-gray-500 uppercase mb-1">Terms & Conditions</div>
            <div class="text-sm text-gray-600">
                <?php echo e($quote->admin_notes); ?>

            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    
    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->expires_at && $quote->status->value === 'sent'): ?>
        <div class="px-10 py-4 border-t border-gray-200 bg-gray-50">
            <div class="text-xs text-gray-600">
                <strong>Note:</strong> This quotation is valid until <?php echo e($quote->expires_at->format('d M, Y')); ?>.
                Prices and availability are subject to change after this date.
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('pdf.browsershot.layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\jonah\Herd\sheffield_ecommerce\resources\views\pdf\browsershot\quotation.blade.php ENDPATH**/ ?>