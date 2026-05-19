<?php $__env->startSection('title', 'Quotation ' . $quote->reference); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $general = app(\App\Settings\GeneralSettings::class);
        $tax = app(\App\Settings\TaxSettings::class);
        $quotationSettings = app(\App\Settings\QuotationSettings::class);

        $logoPath = public_path('logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $companyAddressLines = array_filter([
            $general->store_address,
            $general->store_address_line_2,
            trim(
                implode(', ', array_filter([$general->store_city, $general->store_state, $general->store_postal_code])),
            ),
            $general->store_country,
        ]);

        $hasTax = $quote->tax_cents > 0;
        $currency = $quote->currency;
        $taxRate = null;
        if ($hasTax && $quote->subtotal_cents > 0) {
            $taxRate = round(($quote->tax_cents / $quote->subtotal_cents) * 100);
        }
    ?>

    
    
    
    <div class="px-10 pt-8 pb-3">
        <div class="flex justify-between items-start gap-6">

            
            
            <div class="flex-1">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($logoBase64): ?>
                    <img src="<?php echo e($logoBase64); ?>" alt="<?php echo e($general->store_name); ?>" class="h-12 w-auto mb-2">
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <div class="text-[10px] leading-snug text-gray-800">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($general->store_tagline): ?>
                        <div><?php echo e($general->store_tagline); ?></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $companyAddressLines; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div><?php echo e($line); ?></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($general->store_phone): ?>
                        <div>Tel: <?php echo e($general->store_phone); ?></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($general->store_email): ?>
                        <div>Email: <span class="text-brand"><?php echo e($general->store_email); ?></span></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            
            
            
            <div class="text-right shrink-0">
                <div class="text-xl font-bold text-gray-900 tracking-wide mb-2">QUOTATION</div>
                <table class="text-xs ml-auto" style="border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th class="border border-gray-900 px-3 py-1 font-bold text-gray-900 bg-gray-50"
                                style="text-align: center;">DATE</th>
                            <th class="border border-gray-900 px-3 py-1 font-bold text-gray-900 bg-gray-50"
                                style="text-align: center;">NUMBER</th>
                        </tr>
                    </thead>
                    <tbody class="mt-2">
                        <tr>
                            <td class="border border-gray-900 px-3 py-1 bg-white" style="text-align: center;">
                                <?php echo e(($quote->quoted_at ?? $quote->created_at)->format('d/m/Y')); ?>

                            </td>
                            <td class="border border-gray-900 px-3 py-1 bg-white" style="text-align: center;">
                                <?php echo e($quote->reference); ?>

                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
    <div class="mx-10 border-t-2 border-gray-900"></div>
    <div class="mx-10 border-t border-gray-900 mt-0.5"></div>

    
    
    
    <div class="px-10 mt-5">
        <div class="text-xs font-bold text-gray-900 mb-1.5">QUOTATION TO:</div>
        <div class="inline-block border border-gray-900 px-3 py-2 text-[11px] leading-snug min-w-[14rem] max-w-xs"
            style="box-shadow: 3px 3px 0 rgba(0,0,0,0.85);">
            <div class="font-bold uppercase text-gray-900"><?php echo e($quote->customerName()); ?></div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->customerPhone()): ?>
                <div>TEL: <?php echo e($quote->customerPhone()); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->customerEmail()): ?>
                <div>EMAIL: <?php echo e($quote->customerEmail()); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->preferred_county || $quote->preferred_area): ?>
                <div class="uppercase">
                    <?php echo e(implode(', ', array_filter([$quote->preferred_area, $quote->preferred_county]))); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    
    
    
    <div class="px-10 mt-5">
        <table class="w-full border-collapse text-xs">
            <thead>
                <tr>
                    <th class="border border-gray-400 px-2 py-2 bg-white font-bold text-gray-900 w-12 text-left">ITEM
                    </th>
                    <th class="border border-gray-400 px-2 py-2 bg-white font-bold text-gray-900 text-center">DETAILS
                    </th>
                    <th class="border border-gray-400 px-2 py-2 bg-white font-bold text-gray-900 w-20 text-right">PRICE
                    </th>
                    <th class="border border-gray-400 px-2 py-2 bg-white font-bold text-gray-900 w-12 text-center">QTY
                    </th>
                    <th class="border border-gray-400 px-2 py-2 bg-white font-bold text-gray-900 w-24 text-right">AMOUNT
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $quote->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php
                        $name = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—');
                        $sku = $item->product_snapshot['sku'] ?? null;
                        $brand = $item->product_snapshot['brand'] ?? null;
                        $variantAttrs = $item->product_snapshot['variant'] ?? null;
                        $shortDesc = $item->product?->short_description;
                        $unitPrice = ($item->quoted_price_cents ?? $item->original_price_cents) / 100;
                        // Amount is always price × quantity — total_cents may be 0 on freshly-created quotes.
                        $lineAmount = $unitPrice * $item->quantity;
                    ?>
                    <tr>
                        <td class="border border-gray-400 px-2 py-2 align-top text-left"><?php echo e($index + 1); ?>.</td>
                        <td class="border border-gray-400 px-2 py-2 align-top">
                            <div class="font-bold text-gray-900 underline"><?php echo e(strtoupper($name)); ?></div>
                            <ul class="mt-1 ml-4 list-disc text-[11px] text-gray-800 space-y-0.5">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($brand): ?>
                                    <li>Brand: <?php echo e($brand); ?></li>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sku): ?>
                                    <li>SKU: <?php echo e($sku); ?></li>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(is_array($variantAttrs)): ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $variantAttrs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attr => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <li><?php echo e($attr); ?>: <?php echo e($value); ?></li>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($shortDesc): ?>
                                    <li><?php echo e(\Illuminate\Support\Str::limit(strip_tags($shortDesc), 140)); ?></li>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </ul>
                        </td>
                        <td class="border border-gray-400 px-2 py-2 align-top text-right">
                            <?php echo e(number_format($unitPrice, 2)); ?>

                        </td>
                        <td class="border border-gray-400 px-2 py-2 align-top text-center"><?php echo e($item->quantity); ?></td>
                        <td class="border border-gray-400 px-2 py-2 align-top text-right font-semibold">
                            <?php echo e(number_format($lineAmount, 2)); ?>

                        </td>
                    </tr>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                
                <tr>
                    <td colspan="4" class="border border-gray-400 px-2 py-2 text-right text-gray-700">Subtotal</td>
                    <td class="border border-gray-400 px-2 py-2 text-right font-semibold">
                        <?php echo e(number_format($quote->subtotal_cents / 100, 2)); ?>

                    </td>
                </tr>

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->discount_cents > 0): ?>
                    <tr>
                        <td colspan="4" class="border border-gray-400 px-2 py-2 text-right text-gray-700">Discount</td>
                        <td class="border border-gray-400 px-2 py-2 text-right">
                            -<?php echo e(number_format($quote->discount_cents / 100, 2)); ?>

                        </td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->shipping_cents > 0): ?>
                    <tr>
                        <td colspan="4" class="border border-gray-400 px-2 py-2 text-right text-gray-700">Estimated
                            Shipping</td>
                        <td class="border border-gray-400 px-2 py-2 text-right">
                            <?php echo e(number_format($quote->shipping_cents / 100, 2)); ?>

                        </td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasTax): ?>
                    <tr>
                        <td colspan="4" class="border border-gray-400 px-2 py-2 text-right text-gray-700">
                            <?php echo e($taxRate ? "{$taxRate}% " : ''); ?><?php echo e($tax->tax_name ?? 'VAT'); ?>

                        </td>
                        <td class="border border-gray-400 px-2 py-2 text-right">
                            <?php echo e(number_format($quote->tax_cents / 100, 2)); ?>

                        </td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <tr>
                    <td colspan="4" class="border border-gray-400 px-2 py-2 text-right font-bold text-base">
                        TOTAL (<?php echo e($currency); ?>)
                    </td>
                    <td class="border border-gray-400 px-2 py-2 text-right font-bold text-base">
                        <?php echo e(number_format($quote->total_cents / 100, 2)); ?>

                    </td>
                </tr>
            </tbody>
        </table>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tax->tax_registration_number ?? null): ?>
            <div class="mt-1 text-[10px] text-gray-800 leading-snug">
                <?php echo e(strtoupper($tax->tax_name ?? 'VAT')); ?> REG NO. <?php echo e($tax->tax_registration_number); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    
    
    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->customer_notes): ?>
        <div class="px-10 mt-5">
            <div class="text-xs font-bold text-gray-700 uppercase mb-1">Customer Notes</div>
            <div class="text-xs text-gray-700 italic">"<?php echo e($quote->customer_notes); ?>"</div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    
    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($quote->admin_notes ?: $quotationSettings->quote_terms)): ?>
        <div class="px-10 mt-5">
            <div class="text-xs font-bold text-gray-700 uppercase mb-1">Terms & Conditions</div>
            <div class="text-[11px] text-gray-700 leading-snug whitespace-pre-line">
                <?php echo e($quote->admin_notes ?: $quotationSettings->quote_terms); ?></div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    
    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->expires_at && $quote->status->value === 'sent'): ?>
        <div class="px-10 mt-3 text-[11px] text-gray-700">
            <strong>Note:</strong> This quotation is valid until <?php echo e($quote->expires_at->format('d M, Y')); ?>. Prices and
            availability are subject to change after this date.
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    
    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quotationSettings->quote_footer_note): ?>
        <div class="mt-8 mb-4 text-center">
            <div class="text-[10px] text-gray-600"><?php echo e($quotationSettings->quote_footer_note); ?></div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('pdf.browsershot.layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\jonah\Herd\sheffield_ecommerce\resources\views/pdf/browsershot/quotation.blade.php ENDPATH**/ ?>