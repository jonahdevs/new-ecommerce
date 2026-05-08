<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Quotation <?php echo e($quote->reference); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.5;
            background: #ffffff;
        }

        .page {
            padding: 36px 48px;
        }

        /* ── Header ── */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1a3c2e;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo {
            width: 160px;
            height: auto;
        }

        .company-name {
            font-size: 15px;
            font-weight: bold;
            color: #1a3c2e;
            margin-bottom: 4px;
        }

        .company-details {
            font-size: 9.5px;
            color: #555555;
            line-height: 1.6;
        }

        /* ── Document title block ── */
        .doc-title {
            font-size: 22px;
            font-weight: bold;
            color: #1a3c2e;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .doc-meta {
            font-size: 10px;
            color: #333333;
            line-height: 1.8;
        }

        .doc-meta strong {
            color: #1a1a1a;
        }

        /* ── Validity banner ── */
        .validity-banner {
            width: 100%;
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 2px;
            margin-bottom: 20px;
        }

        .validity-banner td {
            padding: 8px 14px;
            font-size: 10px;
            color: #92400e;
        }

        .validity-banner .banner-label {
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: block;
            margin-bottom: 1px;
        }

        /* ── Addresses ── */
        .addresses-table {
            width: 100%;
            margin-bottom: 24px;
        }

        .addresses-table td {
            vertical-align: top;
            width: 50%;
        }

        .address-box {
            background: #f5f7f5;
            border-left: 3px solid #1a3c2e;
            padding: 10px 14px;
            border-radius: 2px;
        }

        .address-label {
            font-size: 9px;
            font-weight: bold;
            color: #1a3c2e;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .address-name {
            font-size: 11px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 2px;
        }

        .address-detail {
            font-size: 10px;
            color: #444444;
            line-height: 1.6;
        }

        /* ── Info strip ── */
        .info-strip {
            width: 100%;
            background: #1a3c2e;
            color: #ffffff;
            margin-bottom: 24px;
            border-radius: 2px;
        }

        .info-strip td {
            padding: 8px 14px;
            font-size: 10px;
            text-align: center;
        }

        .info-strip .strip-label {
            font-size: 8.5px;
            color: #aaccbb;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: block;
            margin-bottom: 2px;
        }

        .info-strip .strip-value {
            font-weight: bold;
            font-size: 11px;
        }

        /* ── Items table ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .items-table thead tr {
            background: #1a3c2e;
            color: #ffffff;
        }

        .items-table thead th {
            padding: 8px 10px;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
        }

        .items-table thead th.text-right {
            text-align: right;
        }

        .items-table tbody tr {
            border-bottom: 1px solid #e8ece8;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f9faf9;
        }

        .items-table tbody td {
            padding: 8px 10px;
            font-size: 10px;
            vertical-align: top;
        }

        .items-table tbody td.text-right {
            text-align: right;
        }

        .item-name {
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 1px;
        }

        .item-sku {
            font-size: 9px;
            color: #888888;
        }

        .custom-price-badge {
            font-size: 8px;
            background: #dbeafe;
            color: #1e40af;
            padding: 1px 5px;
            border-radius: 2px;
            border: 1px solid #93c5fd;
        }

        /* ── Totals ── */
        .totals-table {
            width: 260px;
            margin-left: auto;
            margin-bottom: 32px;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 5px 10px;
            font-size: 10.5px;
        }

        .totals-table .label {
            color: #555555;
        }

        .totals-table .value {
            text-align: right;
            font-weight: bold;
            color: #1a1a1a;
        }

        .totals-table .divider td {
            border-top: 1px solid #cccccc;
            padding-top: 6px;
        }

        .totals-table .total-row td {
            background: #1a3c2e;
            color: #ffffff;
            font-size: 12px;
            font-weight: bold;
            padding: 8px 10px;
        }

        .totals-table .note-row td {
            color: #b45309;
            font-size: 9.5px;
            font-style: italic;
            padding-top: 4px;
            text-align: center;
        }

        /* ── How to accept ── */
        .accept-box {
            width: 100%;
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 2px;
            margin-bottom: 20px;
        }

        .accept-box td {
            padding: 10px 14px;
            font-size: 10px;
            color: #166534;
            vertical-align: top;
        }

        .accept-box .accept-title {
            font-weight: bold;
            font-size: 10.5px;
            margin-bottom: 4px;
        }

        /* ── Notes ── */
        .notes-box {
            background: #f5f7f5;
            border: 1px solid #d4ddd4;
            padding: 10px 14px;
            margin-bottom: 24px;
            border-radius: 2px;
            font-size: 10px;
            color: #444444;
            line-height: 1.6;
        }

        .notes-label {
            font-weight: bold;
            color: #1a3c2e;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        /* ── Footer ── */
        .footer {
            border-top: 1px solid #cccccc;
            padding-top: 12px;
            text-align: center;
            font-size: 9px;
            color: #888888;
            line-height: 1.7;
        }

        .footer strong {
            color: #1a3c2e;
        }

        /* ── NOT A TAX INVOICE watermark strip ── */
        .not-invoice-strip {
            width: 100%;
            text-align: center;
            background: #fef3c7;
            border: 1px dashed #f59e0b;
            color: #92400e;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 5px;
            margin-bottom: 20px;
            border-radius: 2px;
        }
    </style>
</head>

<body>
    <div class="page">

        
        
        
        <table class="header-table">
            <tr>
                <td style="width: 55%;">
                    <img class="logo" src="<?php echo e(asset('logo.png')); ?>" alt="Sheffield Africa Steel Systems" />
                </td>
                <td style="width: 45%; text-align: right;">
                    <div class="company-name">SheffieldAfrica Steel Systems</div>
                    <div class="company-details">
                        Off Old Mombasa Road before the Nairobi SGR Terminus<br>
                        Nairobi, Kenya<br>
                        Tel: +254 713 777 111<br>
                        Email: info@sheffieldafrica.com<br>
                        Web: www.sheffieldafrica.com<br>
                        PIN: P051234567X
                    </div>
                </td>
            </tr>
        </table>

        
        <div class="not-invoice-strip">
            This is a quotation only &mdash; not a tax invoice. A tax invoice will be issued upon payment.
        </div>

        
        
        
        <table style="width: 100%; margin-bottom: 20px;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <div class="doc-title">Quotation</div>
                    <div style="margin-top: 6px; font-size: 10px; color: #555555;">
                        Product quotation with custom pricing
                    </div>
                </td>
                <td style="width: 50%; text-align: right; vertical-align: top;">
                    <div class="doc-meta">
                        <strong>Quotation No:</strong> <?php echo e($quote->reference); ?><br>
                        <strong>Date:</strong> <?php echo e($quote->quoted_at?->format('d M Y') ?? now()->format('d M Y')); ?><br>
                        <strong>Valid Until:</strong>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->expires_at): ?>
                            <?php echo e($quote->expires_at->format('d M Y')); ?>

                            (<?php echo e($quote->expires_at->diffForHumans()); ?>)
                        <?php else: ?>
                            Upon agreement
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <br>
                        <strong>Prepared by:</strong> Sheffield Africa Sales Team
                    </div>
                </td>
            </tr>
        </table>

        
        
        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->expires_at): ?>
            <table class="validity-banner">
                <tr>
                    <td style="width: 50%;">
                        <span class="banner-label">Quotation valid until</span>
                        <?php echo e($quote->expires_at->format('l, d F Y')); ?>

                    </td>
                    <td style="width: 50%; text-align: right;">
                        <span class="banner-label">Time remaining</span>
                        <?php echo e($quote->expires_at->isPast() ? 'Expired' : ucfirst($quote->expires_at->diffForHumans())); ?>

                    </td>
                </tr>
            </table>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        
        
        <table class="addresses-table">
            <tr>
                <td style="padding-right: 12px;">
                    <div class="address-box">
                        <div class="address-label">Prepared For</div>
                        <div class="address-name"><?php echo e($quote->customerName()); ?></div>
                        <div class="address-detail">
                            <?php echo e($quote->customerEmail()); ?><br>
                            <?php echo e($quote->customerPhone()); ?>

                        </div>
                    </div>
                </td>
                <td style="padding-left: 12px;">
                    <div class="address-box">
                        <div class="address-label">Delivery Preferences</div>
                        <div class="address-detail">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->preferred_area): ?>
                                <?php echo e($quote->preferred_area); ?><br>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->preferred_county): ?>
                                <?php echo e($quote->preferred_county); ?>

                            <?php else: ?>
                                Not specified
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        
        
        
        <table class="info-strip">
            <tr>
                <td>
                    <span class="strip-label">Items</span>
                    <span class="strip-value"><?php echo e($quote->items->sum('quantity')); ?></span>
                </td>
                <td>
                    <span class="strip-label">Currency</span>
                    <span class="strip-value"><?php echo e($quote->currency); ?></span>
                </td>
                <td>
                    <span class="strip-label">Status</span>
                    <span class="strip-value"><?php echo e($quote->status->label()); ?></span>
                </td>
            </tr>
        </table>


        
        
        
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 40%;">Description</th>
                    <th style="width: 10%;">SKU</th>
                    <th class="text-right" style="width: 8%;">Qty</th>
                    <th class="text-right" style="width: 18%;">Unit Price</th>
                    <th class="text-right" style="width: 19%;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $quote->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php
                        $name = $item->productName();
                        $sku = $item->productSku();
                        $brand = $item->product_snapshot['brand'] ?? null;
                        $hasCustomPrice = $item->hasCustomPrice();
                        $unitPrice = $item->effective_price;
                        $lineTotal = $unitPrice * $item->quantity;
                    ?>
                    <tr>
                        <td><?php echo e($index + 1); ?></td>
                        <td>
                            <div class="item-name"><?php echo e($name); ?></div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($brand): ?>
                                <div class="item-sku"><?php echo e($brand); ?></div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasCustomPrice): ?>
                                <span class="custom-price-badge">Custom price</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td><span class="item-sku"><?php echo e($sku); ?></span></td>
                        <td class="text-right"><?php echo e($item->quantity); ?></td>
                        <td class="text-right"><?php echo e(format_currency($unitPrice)); ?></td>
                        <td class="text-right"><?php echo e(format_currency($lineTotal)); ?></td>
                    </tr>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </tbody>
        </table>

        
        
        
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal</td>
                <td class="value"><?php echo e(format_currency($quote->subtotal)); ?></td>
            </tr>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->discount > 0): ?>
                <tr>
                    <td class="label">Discount</td>
                    <td class="value" style="color: #16a34a;">− <?php echo e(format_currency($quote->discount)); ?></td>
                </tr>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <tr>
                <td class="label">Delivery</td>
                <td class="value">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->shipping_cents === 0): ?>
                        <span style="color: #b45309; font-style: italic;">See note</span>
                    <?php else: ?>
                        <?php echo e(format_currency($quote->shipping)); ?>

                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </td>
            </tr>
            <tr class="divider">
                <td></td>
                <td></td>
            </tr>
            <tr class="total-row">
                <td>Quoted Total</td>
                <td style="text-align: right;"><?php echo e(format_currency($quote->total)); ?></td>
            </tr>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->shipping_cents === 0): ?>
                <tr class="note-row">
                    <td colspan="2">* Delivery cost not yet included</td>
                </tr>
            <?php else: ?>
                <tr class="note-row">
                    <td colspan="2">* Prices inclusive of VAT at 16%</td>
                </tr>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </table>

        
        
        
        <table class="accept-box">
            <tr>
                <td>
                    <div class="accept-title">How to accept this quotation</div>
                    Log in to your account at <strong>www.sheffieldafrica.com</strong>,
                    navigate to <strong>My Quotations</strong>, find this quotation
                    (<strong><?php echo e($quote->reference); ?></strong>), and click <strong>Accept Quote</strong>
                    to proceed to payment.
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->expires_at): ?>
                        This offer expires on <strong><?php echo e($quote->expires_at->format('d M Y')); ?></strong>.
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    For assistance, call <strong>+254 713 777 111</strong> or email
                    <strong>info@sheffieldafrica.com</strong>.
                </td>
            </tr>
        </table>

        
        
        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quote->customer_notes): ?>
            <div class="notes-box">
                <div class="notes-label">Customer Notes</div>
                <?php echo e($quote->customer_notes); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        
        
        <div class="notes-box">
            <div class="notes-label">Terms & Conditions</div>
            1. This quotation is valid for the period stated above and is subject to stock availability.<br>
            2. Prices are inclusive of VAT at 16% unless otherwise stated.<br>
            3. Delivery charges are as quoted. Additional charges may apply for remote areas.<br>
            4. Payment is required in full before delivery is arranged.<br>
            5. Goods remain the property of SheffieldAfrica Steel Systems until full payment is received.<br>
            6. For warranty and returns policy, please visit www.sheffieldafrica.com/terms.
        </div>

        
        
        
        <div class="footer">
            <strong>SheffieldAfrica Steel Systems</strong> &bull;
            Off Old Mombasa Road, Nairobi &bull;
            +254 713 777 111 &bull;
            info@sheffieldafrica.com &bull;
            www.sheffieldafrica.com<br>
            PIN: P051234567X &bull;
            This quotation was prepared by the Sheffield Africa Sales Team.
        </div>

    </div>
</body>

</html>
<?php /**PATH C:\Users\jonah\Herd\sheffield_ecommerce\resources\views\pdf\quotation.blade.php ENDPATH**/ ?>