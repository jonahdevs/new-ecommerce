<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['title', 'titleEm' => '', 'danger' => false]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['title', 'titleEm' => '', 'danger' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
    'bg-white border rounded',
    'border-red-500' => $danger,
    'border-zinc-200' => !$danger,
]); ?>">
    <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
        'flex items-center justify-between px-5 py-4 border-b',
        'border-red-200 bg-red-50/40' => $danger,
        'border-zinc-200' => !$danger,
    ]); ?>">
        <div class="flex items-center gap-2">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($icon)): ?>
                <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    '[&_svg]:w-[15px] [&_svg]:h-[15px]',
                    'text-red-500' => $danger,
                    'text-primary' => !$danger,
                ]); ?>">
                    <?php echo e($icon); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <h3 class="font-serif text-[16px] font-extrabold uppercase tracking-wide">
                <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['text-red-500' => $danger, 'text-zinc-950' => !$danger]); ?>"><?php echo e($title); ?></span>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($titleEm): ?>
                    <em class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                        'not-italic',
                        'text-red-500' => $danger,
                        'text-primary' => !$danger,
                    ]); ?>"><?php echo e($titleEm); ?></em>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </h3>
        </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($action)): ?>
            <div><?php echo e($action); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <div>
        <?php echo e($slot); ?>

    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($footer)): ?>
        <div class="flex items-center gap-2.5 px-5 py-4 border-t border-zinc-200">
            <?php echo e($footer); ?>

        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\resources\views/components/customer/settings-card.blade.php ENDPATH**/ ?>