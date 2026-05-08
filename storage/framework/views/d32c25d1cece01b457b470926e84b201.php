<?php $__env->startSection('title', 'Tax Invoice ' . $order->reference); ?>

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
            <h1 class="text-2xl font-bold text-gray-900 uppercase">Tax Invoice</h1>
            <div class="flex items-center gap-2 mt-2 text-sm">
                <span class="text-gray-500">Invoice No:</span>
                <span class="text-gray-900 font-semibold">#<?php echo e($order->reference); ?></span>
            </div>
            <div class="flex items-center gap-2 mt-1 text-sm">
                <span class="text-gray-500">Date:</span>
                <span class="text-gray-900 font-semibold"><?php echo e($order->created_at->format('d M, Y')); ?></span>
            </div>
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
        
        <div class="grid grid-cols-2 gap-4 flex-1">
            
            <div class="border border-gray-300">
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-300">
                    <div class="text-xs font-bold text-gray-700 uppercase">Customer</div>
                </div>
                <div class="p-4 space-y-2">
                    <div class="font-semibold text-sm text-gray-900"><?php echo e($order->customerName()); ?></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->customerEmail()): ?>
                        <div class="text-xs text-gray-600"><?php echo e($order->customerEmail()); ?></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->customerPhone()): ?>
                        <div class="text-xs text-gray-600"><?php echo e($order->customerPhone()); ?></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->shipping_address): ?>
                        <div class="pt-3 mt-3 border-t border-gray-200">
                            <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Delivery Address</div>
                            <div class="text-xs text-gray-600 leading-relaxed">
                                <?php echo e($order->shipping_address['address'] ?? ''); ?><br>
                                <?php echo e(implode(
                                    ', ',
                                    array_filter([$order->shipping_address['area'] ?? null, $order->shipping_address['county'] ?? null]),
                                )); ?>

                            </div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            
            <div class="border border-gray-300">
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-300">
                    <div class="text-xs font-bold text-gray-700 uppercase">Payment Info</div>
                </div>
                <div class="p-4 space-y-3">
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase">Payment Method</div>
                        <div class="text-sm font-semibold text-gray-900 mt-0.5">
                            <?php echo e(ucfirst($order->payment?->gateway ?? 'Online Payment')); ?>

                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase">Status</div>
                        <div class="text-sm font-semibold text-gray-900 mt-0.5">
                            <?php echo e($order->payment_status->label()); ?>

                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase">Order Reference</div>
                        <div class="text-sm font-semibold text-gray-900 mt-0.5">#<?php echo e($order->reference); ?></div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase">Currency</div>
                        <div class="text-sm font-semibold text-gray-900 mt-0.5">KES</div>
                    </div>
                </div>
            </div>
        </div>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->kra_cu_number): ?>
            <div class="flex flex-col items-center justify-center shrink-0">
                <div class="w-32 h-32 bg-white p-2">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo e(urlencode($order->kra_cu_number)); ?>"
                        alt="QR Code" class="w-full h-full">
                </div>
                <div class="text-xs text-gray-500 text-center font-semibold uppercase mt-2">Scan to Verify</div>
                <div class="text-xs font-mono font-semibold text-gray-900 text-center mt-2">
                    <?php echo e($order->kra_cu_number); ?>

                </div>
                <div class="text-xs text-gray-600 text-center mt-1">
                    <?php echo e($order->kra_validated_at?->format('d M Y, H:i') ?? $order->created_at->format('d M Y, H:i')); ?>

                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    
    
    
    <div class="px-10 py-6">
        <table class="w-full border-collapse">
            <thead class="bg-slate-50">
                <tr class="border-b-2 border-gray-300">
                    <th class="py-3 ps-2 text-xs font-bold text-gray-700 uppercase text-left">#</th>
                    <th class="py-3 text-xs font-bold text-gray-700 uppercase text-left">Description</th>
                    <th class="py-3 text-xs font-bold text-gray-700 uppercase text-center">Qty</th>
                    <th class="py-3 text-xs font-bold text-gray-700 uppercase text-right">Unit Price</th>
                    <th class="py-3 text-xs font-bold text-gray-700 uppercase text-right">Discount</th>
                    <th class="py-3 pe-2 text-xs font-bold text-gray-700 uppercase text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php
                        $name = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—');
                        $sku = $item->product_snapshot['sku'] ?? '—';
                        $discountAmount = $item->discount_cents / 100;
                    ?>
                    <tr class="border-b border-gray-200">
                        <td class="py-3 text-xs text-gray-500"><?php echo e($index + 1); ?></td>
                        <td class="py-3">
                            <div class="text-sm font-semibold text-gray-900"><?php echo e($name); ?></div>
                            <div class="text-xs text-gray-500 mt-0.5"><?php echo e($sku); ?></div>
                        </td>
                        <td class="py-3 text-sm text-gray-900 text-center"><?php echo e($item->quantity); ?></td>
                        <td class="py-3 text-sm text-gray-900 text-right">
                            <?php echo e(number_format($item->unit_price_cents / 100, 2)); ?>

                        </td>
                        <td class="py-3 text-sm text-gray-600 text-right">
                            <?php echo e($discountAmount > 0 ? '-' . number_format($discountAmount, 2) : '—'); ?>

                        </td>
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
                    <span>Subtotal (Excl. VAT)</span>
                    <span class="font-semibold text-gray-900">KES
                        <?php echo e(number_format(($order->total_cents - $order->tax_cents) / 100, 2)); ?></span>
                </div>
                <div class="flex justify-between text-sm text-gray-600">
                    <span>VAT Amount (16%)</span>
                    <span class="font-semibold text-gray-900">KES <?php echo e(number_format($order->tax_cents / 100, 2)); ?></span>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->shipping_cents > 0): ?>
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Shipping & Delivery</span>
                        <span class="font-semibold text-gray-900">KES
                            <?php echo e(number_format($order->shipping_cents / 100, 2)); ?></span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->discount_cents > 0): ?>
                    <div class="flex justify-between text-sm text-red-600">
                        <span>Total Discount</span>
                        <span class="font-semibold">-KES <?php echo e(number_format($order->discount_cents / 100, 2)); ?></span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <div class="pt-3 mt-3 border-t-2 border-gray-300 flex justify-between items-center">
                    <span class="text-base font-bold text-gray-900 uppercase">Total Payable</span>
                    <span class="text-xl font-bold text-gray-900">KES
                        <?php echo e(number_format($order->total_cents / 100, 2)); ?></span>
                </div>
            </div>
        </div>
    </div>

    
    
    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->customer_note): ?>
        <div class="px-10 py-4 border-t border-gray-200">
            <div class="text-xs font-bold text-gray-500 uppercase mb-1">Order Notes</div>
            <div class="text-sm text-gray-600 italic">
                "<?php echo e($order->customer_note); ?>"
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    
    
    <?php $purchaseNote = app(\App\Settings\OrderSettings::class)->purchase_note; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($purchaseNote): ?>
        <div class="px-10 py-4 border-t border-gray-200">
            <div class="text-xs font-bold text-gray-500 uppercase mb-1">Note</div>
            <div class="text-xs text-gray-600"><?php echo e($purchaseNote); ?></div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('pdf.browsershot.layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\jonah\Herd\sheffield_ecommerce\resources\views\pdf\browsershot\invoice.blade.php ENDPATH**/ ?>