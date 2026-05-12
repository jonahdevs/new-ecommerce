<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['title', 'description', 'lastItem' => false]));

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

foreach (array_filter((['title', 'description', 'lastItem' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['flex items-start justify-between gap-5 px-5 py-4 border-b border-zinc-200', 'border-b-0' => $lastItem]); ?>">
    <div class="flex-1">
        <div class="text-[13px] font-bold text-zinc-950 mb-0.5"><?php echo e($title); ?></div>
        <div class="text-[12px] text-zinc-500 leading-relaxed"><?php echo e($description); ?></div>
    </div>
    <div class="flex items-center gap-2.5 shrink-0">
        <?php echo e($slot); ?>

    </div>
</div>
<?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\resources\views/components/customer/privacy-row.blade.php ENDPATH**/ ?>