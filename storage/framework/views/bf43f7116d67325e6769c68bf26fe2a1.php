<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Order Confirmed – <?php echo e($order->reference); ?></title>
    <style>
        /* Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f4f4f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #18181b;
        }

        a {
            color: #18181b;
        }

        img {
            display: block;
            max-width: 100%;
        }

        /* Layout */
        .wrapper {
            background: #f4f4f5;
            padding: 32px 16px;
        }

        .container {
            background: #ffffff;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e4e4e7;
        }

        /* Header */
        .header {
            background: #18181b;
            padding: 28px 32px;
            text-align: center;
        }

        .header .logo {
            color: #ffffff;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.5px;
            text-decoration: none;
        }

        .header .tagline {
            color: #a1a1aa;
            font-size: 12px;
            margin-top: 4px;
        }

        /* Hero */
        .hero {
            padding: 32px 32px 24px;
            text-align: center;
            border-bottom: 1px solid #f4f4f5;
        }

        .hero .icon {
            width: 56px;
            height: 56px;
            background: #f0fdf4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .hero h1 {
            font-size: 22px;
            font-weight: 700;
            color: #18181b;
            margin-bottom: 8px;
        }

        .hero p {
            color: #71717a;
            font-size: 14px;
            line-height: 1.6;
        }

        .hero .reference {
            display: inline-block;
            background: #f4f4f5;
            border-radius: 6px;
            padding: 4px 12px;
            font-size: 13px;
            font-weight: 600;
            color: #18181b;
            margin-top: 12px;
        }

        /* Section */
        .section {
            padding: 24px 32px;
            border-bottom: 1px solid #f4f4f5;
        }

        .section-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #a1a1aa;
            margin-bottom: 16px;
        }

        /* Items */
        .item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f4f4f5;
        }

        .item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .item-image {
            width: 52px;
            height: 52px;
            border-radius: 6px;
            background: #f4f4f5;
            border: 1px solid #e4e4e7;
            object-fit: cover;
            flex-shrink: 0;
        }

        .item-image-placeholder {
            width: 52px;
            height: 52px;
            border-radius: 6px;
            background: #f4f4f5;
            border: 1px solid #e4e4e7;
            flex-shrink: 0;
        }

        .item-name {
            font-size: 14px;
            font-weight: 500;
            color: #18181b;
        }

        .item-meta {
            font-size: 12px;
            color: #71717a;
            margin-top: 3px;
        }

        .item-price {
            font-size: 14px;
            font-weight: 600;
            color: #18181b;
            margin-left: auto;
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* Totals */
        .totals-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 14px;
        }

        .totals-row.discount {
            color: #16a34a;
        }

        .totals-row.total {
            font-weight: 700;
            font-size: 16px;
            border-top: 1px solid #e4e4e7;
            padding-top: 12px;
            margin-top: 6px;
        }

        /* Two col */
        .two-col {
            display: flex;
            gap: 16px;
        }

        .two-col .col {
            flex: 1;
            background: #fafafa;
            border: 1px solid #e4e4e7;
            border-radius: 6px;
            padding: 16px;
        }

        .col-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #a1a1aa;
            margin-bottom: 8px;
        }

        .col-value {
            font-size: 13px;
            color: #3f3f46;
            line-height: 1.6;
        }

        .col-value strong {
            color: #18181b;
            font-weight: 600;
        }

        /* Next steps */
        .step {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 8px 0;
        }

        .step-num {
            width: 22px;
            height: 22px;
            background: #18181b;
            color: #ffffff;
            border-radius: 50%;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .step-text {
            font-size: 13px;
            color: #3f3f46;
            line-height: 1.5;
            padding-top: 2px;
        }

        /* CTA */
        .cta {
            padding: 28px 32px;
            text-align: center;
        }

        .btn {
            display: inline-block;
            background: #18181b;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        /* Footer */
        .footer {
            background: #fafafa;
            border-top: 1px solid #e4e4e7;
            padding: 20px 32px;
            text-align: center;
        }

        .footer p {
            font-size: 12px;
            color: #a1a1aa;
            line-height: 1.6;
        }

        .footer a {
            color: #71717a;
        }

        /* Mobile */
        @media (max-width: 600px) {
            .section {
                padding: 20px;
            }

            .hero {
                padding: 24px 20px;
            }

            .cta {
                padding: 20px;
            }

            .two-col {
                flex-direction: column;
            }

            .header {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="container">

            
            <div class="header">
                <a href="<?php echo e(config('app.url')); ?>" class="logo">
                    <?php echo e(config('app.name')); ?>

                </a>
                <p class="tagline">Commercial Kitchen Equipment</p>
            </div>

            
            <div class="hero">
                <div class="icon">
                    
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                        <path d="M20 6L9 17L4 12" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>
                <h1>Your order is confirmed!</h1>
                <p>
                    Hi <?php echo e($customerName); ?>, thank you for your purchase.<br />
                    We've received your order and will get it ready for you shortly.
                </p>
                <span class="reference"><?php echo e($order->reference); ?></span>
            </div>

            
            <div class="section">
                <p class="section-title">What happens next</p>
                <div class="step">
                    <div class="step-num">1</div>
                    <p class="step-text">We're preparing your order for dispatch.</p>
                </div>
                <div class="step">
                    <div class="step-num">2</div>
                    <p class="step-text">You'll receive a notification when your order is on its way.</p>
                </div>
                <div class="step">
                    <div class="step-num">3</div>
                    <p class="step-text">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($deliveryWindow): ?>
                            Estimated delivery: <strong><?php echo e($deliveryWindow); ?></strong>.
                        <?php else: ?>
                            Delivery time will be communicated shortly.
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </p>
                </div>
            </div>

            
            <div class="section">
                <p class="section-title">Order Items</p>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                    <div class="item">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->product?->image_path): ?>
                            <img class="item-image"
                                src="<?php echo e($item->product->image_url ?? asset($item->product->image_path)); ?>"
                                alt="<?php echo e($item->name); ?>" />
                        <?php else: ?>
                            <div class="item-image-placeholder"></div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <div style="flex: 1; min-width: 0;">
                            <p class="item-name"><?php echo e($item->name); ?></p>
                            <p class="item-meta">
                                Qty: <?php echo e($item->quantity); ?>

                                · <?php echo e(format_currency($item->unit_price_cents / 100)); ?> each
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->sku): ?>
                                    · SKU: <?php echo e($item->sku); ?>

                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </p>
                        </div>

                        <span class="item-price">
                            <?php echo e(format_currency($item->total_cents / 100)); ?>

                        </span>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>

            
            <div class="section">
                <p class="section-title">Order Summary</p>

                <div class="totals-row">
                    <span>Subtotal</span>
                    <span><?php echo e(format_currency($order->subtotal)); ?></span>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->discount > 0): ?>
                    <div class="totals-row discount">
                        <span>Discount</span>
                        <span>− <?php echo e(format_currency($order->discount)); ?></span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <div class="totals-row">
                    <span>Shipping</span>
                    <span><?php echo e($order->shipping == 0 ? 'Free' : format_currency($order->shipping)); ?></span>
                </div>

                <div class="totals-row total">
                    <span>Total Paid</span>
                    <span><?php echo e(format_currency($order->total)); ?></span>
                </div>
            </div>

            
            <div class="section">
                <div class="two-col">
                    <div class="col">
                        <p class="col-title">Delivering to</p>
                        <div class="col-value">
                            <strong><?php echo e($order->shipping_address['full_name'] ?? ''); ?></strong><br />
                            <?php echo e($order->shipping_address['address'] ?? ''); ?><br />
                            <?php echo e(implode(
                                ', ',
                                array_filter([$order->shipping_address['area'] ?? null, $order->shipping_address['county'] ?? null]),
                            )); ?><br />
                            <?php echo e(format_phone($order->shipping_address['phone_number'] ?? '')); ?>

                        </div>
                    </div>

                    <div class="col">
                        <p class="col-title">Shipping & Payment</p>
                        <div class="col-value">
                            <strong><?php echo e($order->deliveryOrder?->shippingMethod?->name ?? '—'); ?></strong><br />
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($deliveryWindow): ?>
                                Est. <?php echo e($deliveryWindow); ?><br />
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->deliveryOrder?->pickupStation): ?>
                                Pickup: <?php echo e($order->deliveryOrder->pickupStation->name); ?><br />
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <br />
                            <strong><?php echo e($paymentLabel); ?></strong><br />
                            <span style="color: #16a34a; font-weight: 600;">✓ Paid</span>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="cta">
                <a href="<?php echo e(route('orders.confirmation', $order)); ?>" class="btn">
                    View Order Details
                </a>
                <p style="margin-top: 14px; font-size: 12px; color: #a1a1aa;">
                    Or visit
                    <a href="<?php echo e(route('customer.orders.index')); ?>" style="color: #71717a;">
                        your orders page
                    </a>
                    to track all your orders.
                </p>
            </div>

            
            <div class="footer">
                <p>
                    <?php echo e(config('app.name')); ?> · Commercial Kitchen Equipment<br />
                    Kenya · Uganda · Rwanda<br />
                    <br />
                    Questions? Reply to this email or
                    <a href="<?php echo e(config('app.url')); ?>/contact">contact our support team</a>.<br />
                    <br />
                    <span style="color: #d4d4d8;">
                        You're receiving this because you placed an order at <?php echo e(config('app.name')); ?>.
                    </span>
                </p>
            </div>

        </div>
    </div>
</body>

</html>
<?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\resources\views\mails\orders\confirmation.blade.php ENDPATH**/ ?>