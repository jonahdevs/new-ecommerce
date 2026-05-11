<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'variant' => 'primary', // primary, secondary, outline
    'size' => 'default', // default, large
    'type' => 'button',
    'disabled' => false,
]));

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

foreach (array_filter(([
    'variant' => 'primary', // primary, secondary, outline
    'size' => 'default', // default, large
    'type' => 'button',
    'disabled' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $baseClasses =
        'inline-flex items-center justify-center font-barlow-condensed font-black uppercase tracking-[2px] transition-all rounded-sm cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed';

    $variantClasses = match ($variant) {
        'primary' => 'bg-zinc-950 text-white hover:bg-zinc-800 border-2 border-zinc-950',
        'secondary' => 'bg-white text-zinc-950 hover:bg-zinc-50 border-2 border-zinc-950',
        'outline' => 'bg-transparent text-zinc-950 hover:bg-zinc-50 border-2 border-zinc-200 hover:border-zinc-950',
        default => 'bg-zinc-950 text-white hover:bg-zinc-800 border-2 border-zinc-950',
    };

    $sizeClasses = match ($size) {
        'large' => 'h-[56px] px-8 text-[15px]',
        'default' => 'h-[48px] px-6 text-[14px]',
        'small' => 'h-[40px] px-4 text-[13px]',
    };

    $classes = $baseClasses . ' ' . $variantClasses . ' ' . $sizeClasses;
?>

<button type="<?php echo e($type); ?>" <?php echo e($attributes->merge(['class' => $classes])); ?>

    <?php if($disabled): ?> disabled <?php endif; ?>>
    <?php echo e($slot); ?>

</button>
<?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\resources\views/components/ui/button.blade.php ENDPATH**/ ?>