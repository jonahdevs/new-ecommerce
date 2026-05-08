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

    
    
    
    <div
        style="padding: 40px 40px 24px; display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #e5e7eb;">
        <div>
            <h1 style="font-size: 24px; font-weight: bold; color: #111827; text-transform: uppercase; margin: 0;">Tax Invoice
            </h1>
            <div style="display: flex; align-items: center; gap: 8px; margin-top: 8px; font-size: 14px;">
                <span style="color: #6b7280;">Invoice No:</span>
                <span style="color: #111827; font-weight: 600;">#<?php echo e($order->reference); ?></span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px; font-size: 14px;">
                <span style="color: #6b7280;">Date:</span>
                <span style="color: #111827; font-weight: 600;"><?php echo e($order->created_at->format('d M, Y')); ?></span>
            </div>
        </div>

        <div style="text-align: right;">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($logoBase64): ?>
                <img src="<?php echo e($logoBase64); ?>" alt="Sheffield Africa" style="height: 48px; width: auto; margin-left: auto;">
            <?php else: ?>
                <div style="font-size: 20px; font-weight: bold; color: #c02434; text-transform: uppercase;">SHEFFIELD</div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    
    
    
    <div style="padding: 24px 40px; display: flex; justify-content: space-between; gap: 24px;">
        
        <div style="flex: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            
            <div style="border: 1px solid #d1d5db;">
                <div style="padding: 8px 16px; background-color: #f9fafb; border-bottom: 1px solid #d1d5db;">
                    <div style="font-size: 10px; font-weight: bold; color: #374151; text-transform: uppercase;">Customer
                    </div>
                </div>
                <div style="padding: 16px;">
                    <div style="font-weight: 600; font-size: 14px; color: #111827; margin-bottom: 8px;">
                        <?php echo e($order->customerName()); ?></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->customerEmail()): ?>
                        <div style="font-size: 10px; color: #4b5563; margin-bottom: 4px;"><?php echo e($order->customerEmail()); ?>

                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->customerPhone()): ?>
                        <div style="font-size: 10px; color: #4b5563; margin-bottom: 4px;"><?php echo e($order->customerPhone()); ?>

                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->shipping_address): ?>
                        <div style="padding-top: 12px; margin-top: 12px; border-top: 1px solid #e5e7eb;">
                            <div
                                style="font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase; margin-bottom: 4px;">
                                Delivery Address</div>
                            <div style="font-size: 10px; color: #4b5563; line-height: 1.6;">
                                <?php echo e($order->shipping_address['address'] ?? ''); ?><br>
                                <?php echo e(implode(', ', array_filter([$order->shipping_address['area'] ?? null, $order->shipping_address['county'] ?? null]))); ?>

                            </div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            
            <div style="border: 1px solid #d1d5db;">
                <div style="padding: 8px 16px; background-color: #f9fafb; border-bottom: 1px solid #d1d5db;">
                    <div style="font-size: 10px; font-weight: bold; color: #374151; text-transform: uppercase;">Payment Info
                    </div>
                </div>
                <div style="padding: 16px;">
                    <div style="margin-bottom: 12px;">
                        <div style="font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Payment
                            Method</div>
                        <div style="font-size: 14px; font-weight: 600; color: #111827; margin-top: 2px;">
                            <?php echo e(ucfirst($order->payment?->gateway ?? 'Online Payment')); ?>

                        </div>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <div style="font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Status
                        </div>
                        <div style="font-size: 14px; font-weight: 600; color: #111827; margin-top: 2px;">
                            <?php echo e($order->payment_status->label()); ?>

                        </div>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <div style="font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Order
                            Reference</div>
                        <div style="font-size: 14px; font-weight: 600; color: #111827; margin-top: 2px;">
                            #<?php echo e($order->reference); ?></div>
                    </div>
                    <div>
                        <div style="font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Currency
                        </div>
                        <div style="font-size: 14px; font-weight: 600; color: #111827; margin-top: 2px;">KES</div>
                    </div>
                </div>
            </div>
        </div>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->kra_cu_number): ?>
            <div
                style="display: flex; flex-direction: column; align-items: center; justify-content: center; flex-shrink: 0;">
                <div style="width: 128px; height: 128px; background-color: white; padding: 8px;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo e(urlencode($order->kra_cu_number)); ?>"
                        alt="QR Code" style="width: 100%; height: 100%;">
                </div>
                <div
                    style="font-size: 10px; color: #6b7280; text-align: center; font-weight: 600; text-transform: uppercase; margin-top: 8px;">
                    Scan to Verify</div>
                <div
                    style="font-size: 10px; font-family: monospace; font-weight: 600; color: #111827; text-align: center; margin-top: 8px;">
                    <?php echo e($order->kra_cu_number); ?>

                </div>
                <div style="font-size: 10px; color: #4b5563; text-align: center; margin-top: 4px;">
                    <?php echo e($order->kra_validated_at?->format('d M Y, H:i') ?? $order->created_at->format('d M Y, H:i')); ?>

                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    
    
    
    <div style="padding: 24px 40px;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background-color: #f8fafc;">
                <tr style="border-bottom: 2px solid #d1d5db;">
                    <th
                        style="padding: 12px 0 12px 8px; font-size: 10px; font-weight: bold; color: #374151; text-transform: uppercase; text-align: left;">
                        #</th>
                    <th
                        style="padding: 12px 0; font-size: 10px; font-weight: bold; color: #374151; text-transform: uppercase; text-align: left;">
                        Description</th>
                    <th
                        style="padding: 12px 0; font-size: 10px; font-weight: bold; color: #374151; text-transform: uppercase; text-align: center;">
                        Qty</th>
                    <th
                        style="padding: 12px 0; font-size: 10px; font-weight: bold; color: #374151; text-transform: uppercase; text-align: right;">
                        Unit Price</th>
                    <th
                        style="padding: 12px 0; font-size: 10px; font-weight: bold; color: #374151; text-transform: uppercase; text-align: right;">
                        Discount</th>
                    <th
                        style="padding: 12px 8px 12px 0; font-size: 10px; font-weight: bold; color: #374151; text-transform: uppercase; text-align: right;">
                        Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php
                        $name = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—');
                        $sku = $item->product_snapshot['sku'] ?? '—';
                        $discountAmount = $item->discount_cents / 100;
                    ?>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px 0 12px 0; font-size: 10px; color: #6b7280;"><?php echo e($index + 1); ?></td>
                        <td style="padding: 12px 0;">
                            <div style="font-size: 14px; font-weight: 600; color: #111827;"><?php echo e($name); ?></div>
                            <div style="font-size: 10px; color: #6b7280; margin-top: 2px;"><?php echo e($sku); ?></div>
                        </td>
                        <td style="padding: 12px 0; font-size: 14px; color: #111827; text-align: center;">
                            <?php echo e($item->quantity); ?></td>
                        <td style="padding: 12px 0; font-size: 14px; color: #111827; text-align: right;">
                            <?php echo e(number_format($item->unit_price_cents / 100, 2)); ?>

                        </td>
                        <td style="padding: 12px 0; font-size: 14px; color: #4b5563; text-align: right;">
                            <?php echo e($discountAmount > 0 ? '-' . number_format($discountAmount, 2) : '—'); ?>

                        </td>
                        <td
                            style="padding: 12px 8px 12px 0; font-size: 14px; font-weight: 600; color: #111827; text-align: right;">
                            <?php echo e(number_format($item->total_cents / 100, 2)); ?>

                        </td>
                    </tr>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </tbody>
        </table>
    </div>

    
    
    
    <div style="padding: 24px 40px; display: flex; justify-content: flex-end;">
        <div style="width: 320px;">
            <div
                style="margin-bottom: 8px; display: flex; justify-content: space-between; font-size: 14px; color: #4b5563;">
                <span>Subtotal (Excl. VAT)</span>
                <span style="font-weight: 600; color: #111827;">KES
                    <?php echo e(number_format(($order->total_cents - $order->tax_cents) / 100, 2)); ?></span>
            </div>
            <div
                style="margin-bottom: 8px; display: flex; justify-content: space-between; font-size: 14px; color: #4b5563;">
                <span>VAT Amount (16%)</span>
                <span style="font-weight: 600; color: #111827;">KES <?php echo e(number_format($order->tax_cents / 100, 2)); ?></span>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->shipping_cents > 0): ?>
                <div
                    style="margin-bottom: 8px; display: flex; justify-content: space-between; font-size: 14px; color: #4b5563;">
                    <span>Shipping & Delivery</span>
                    <span style="font-weight: 600; color: #111827;">KES
                        <?php echo e(number_format($order->shipping_cents / 100, 2)); ?></span>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->discount_cents > 0): ?>
                <div
                    style="margin-bottom: 8px; display: flex; justify-content: space-between; font-size: 14px; color: #dc2626;">
                    <span>Total Discount</span>
                    <span style="font-weight: 600;">-KES <?php echo e(number_format($order->discount_cents / 100, 2)); ?></span>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <div
                style="padding-top: 12px; margin-top: 12px; border-top: 2px solid #d1d5db; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 16px; font-weight: bold; color: #111827; text-transform: uppercase;">Total
                    Payable</span>
                <span style="font-size: 20px; font-weight: bold; color: #111827;">KES
                    <?php echo e(number_format($order->total_cents / 100, 2)); ?></span>
            </div>
        </div>
    </div>

    
    
    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->customer_note): ?>
        <div style="padding: 16px 40px; border-top: 1px solid #e5e7eb;">
            <div style="font-size: 10px; font-weight: bold; color: #6b7280; text-transform: uppercase; margin-bottom: 4px;">
                Order Notes</div>
            <div style="font-size: 14px; color: #4b5563; font-style: italic;">
                "<?php echo e($order->customer_note); ?>"
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    
    
    <?php $purchaseNote = app(\App\Settings\OrderSettings::class)->purchase_note; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($purchaseNote): ?>
        <div style="padding: 16px 40px; border-top: 1px solid #e5e7eb;">
            <div style="font-size: 10px; font-weight: bold; color: #6b7280; text-transform: uppercase; margin-bottom: 4px;">
                Note</div>
            <div style="font-size: 11px; color: #4b5563;"><?php echo e($purchaseNote); ?></div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    
    
    <div
        style="position: absolute; bottom: 0; left: 0; right: 0; padding: 12px 40px; background-color: white; border-top: 1px solid #e5e7eb;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;">
            <div style="flex: 1;">
                <div style="font-weight: bold; color: #111827; font-size: 11px; margin-bottom: 4px;">Sheffield Steel Systems
                    Limited</div>
                <div style="color: #6b7280; font-size: 9px; line-height: 1.6;">
                    Off Old Mombasa Road, Opposite Hilton Garden Inn<br>
                    P.O. Box 48670-00100, Nairobi, Kenya<br>
                    PIN: P051148391Z
                </div>
            </div>
            <div style="color: #6b7280; font-size: 9px; text-align: right;">
                +254 713 444 000 / +254 713 777 111<br>
                info@sheffieldafrica.com<br>
                www.sheffieldafrica.com
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('pdf.dompdf.layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\jonah\Herd\sheffield_ecommerce\resources\views\pdf\dompdf\invoice.blade.php ENDPATH**/ ?>