

<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'color' => null,
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
    'color' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
$trackClasses = Flux::classes()
    ->add('h-1.5 relative w-full overflow-hidden bg-zinc-200 dark:bg-white/10')
    ->add('[print-color-adjust:exact]')
    ->add('rounded-full')
    ;

$barClasses = Flux::classes()
    ->add('h-full rounded-full transition-[width] duration-300 ease-out')
    ->add(match ($color) {
        'red'     => 'bg-red-600 dark:bg-red-400',
        'orange'  => 'bg-orange-600 dark:bg-orange-400',
        'amber'   => 'bg-amber-600 dark:bg-amber-400',
        'yellow'  => 'bg-yellow-600 dark:bg-yellow-400',
        'lime'    => 'bg-lime-600 dark:bg-lime-400',
        'green'   => 'bg-green-600 dark:bg-green-400',
        'emerald' => 'bg-emerald-600 dark:bg-emerald-400',
        'teal'    => 'bg-teal-600 dark:bg-teal-400',
        'cyan'    => 'bg-cyan-600 dark:bg-cyan-400',
        'sky'     => 'bg-sky-600 dark:bg-sky-400',
        'blue'    => 'bg-blue-600 dark:bg-blue-400',
        'indigo'  => 'bg-indigo-600 dark:bg-indigo-400',
        'violet'  => 'bg-violet-600 dark:bg-violet-400',
        'purple'  => 'bg-purple-600 dark:bg-purple-400',
        'fuchsia' => 'bg-fuchsia-600 dark:bg-fuchsia-400',
        'pink'    => 'bg-pink-600 dark:bg-pink-400',
        'rose'    => 'bg-rose-600 dark:bg-rose-400',
        default   => 'bg-accent',
    })
    ;
?>

<ui-progress <?php echo e($attributes->class($trackClasses)); ?> data-flux-progress>
    <div class="<?php echo e($barClasses); ?>" style="width: var(--flux-progress-percentage)"></div>
</ui-progress>
<?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\vendor\livewire\flux\stubs\resources\views\flux\progress.blade.php ENDPATH**/ ?>