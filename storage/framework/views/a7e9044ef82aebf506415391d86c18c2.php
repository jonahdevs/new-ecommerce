<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'model' => 'content',
    'placeholder' => 'Start writing...',
    'value' => '',
    'label' => null,
    'error' => null,
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
    'model' => 'content',
    'placeholder' => 'Start writing...',
    'value' => '',
    'label' => null,
    'error' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div x-data="richEditor('<?php echo e($model); ?>', '<?php echo e($placeholder); ?>', <?php echo \Illuminate\Support\Js::from($value ?? '')->toHtml() ?>)" class="w-full">

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($label): ?>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            <?php echo e($label); ?>

        </label>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div
        class="border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-blue-500">

        
        <div
            class="flex flex-wrap items-center gap-0.5 px-2 py-1.5 bg-gray-50 dark:bg-zinc-800 border-b border-gray-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-200">

            
            <div class="flex items-center gap-0.5 pr-2 mr-1 border-r border-gray-300 dark:border-zinc-600">
                <button type="button" title="Bold" @click="toggleBold()"
                    :class="isActive('bold') ? 'bg-gray-200 dark:bg-zinc-600' : 'hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="w-7 h-7 flex items-center justify-center rounded text-sm font-bold">B</button>

                <button type="button" title="Italic" @click="toggleItalic()"
                    :class="isActive('italic') ? 'bg-gray-200 dark:bg-zinc-600' : 'hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="w-7 h-7 flex items-center justify-center rounded text-sm italic">I</button>

                <button type="button" title="Underline" @click="toggleUnderline()"
                    :class="isActive('underline') ? 'bg-gray-200 dark:bg-zinc-600' : 'hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="w-7 h-7 flex items-center justify-center rounded text-sm underline">U</button>
            </div>

            
            <div class="flex items-center gap-0.5 pr-2 mr-1 border-r border-gray-300 dark:border-zinc-600">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = [1, 2, 3]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $level): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <button type="button" title="Heading <?php echo e($level); ?>"
                        @click="toggleHeading(<?php echo e($level); ?>)"
                        :class="isActive('heading', { level: <?php echo e($level); ?> }) ? 'bg-gray-200 dark:bg-zinc-600' : 'hover:bg-gray-200 dark:hover:bg-zinc-700'"
                        class="w-8 h-7 flex items-center justify-center rounded text-xs font-semibold">H<?php echo e($level); ?></button>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>

            
            <div class="flex items-center gap-0.5 pr-2 mr-1 border-r border-gray-300 dark:border-zinc-600">
                <button type="button" title="Bullet list" @click="toggleBulletList()"
                    :class="isActive('bulletList') ? 'bg-gray-200 dark:bg-zinc-600' : 'hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="w-7 h-7 flex items-center justify-center rounded">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="9" y1="6" x2="20" y2="6" />
                        <line x1="9" y1="12" x2="20" y2="12" />
                        <line x1="9" y1="18" x2="20" y2="18" />
                        <circle cx="4" cy="6" r="1.5" fill="currentColor" stroke="none" />
                        <circle cx="4" cy="12" r="1.5" fill="currentColor" stroke="none" />
                        <circle cx="4" cy="18" r="1.5" fill="currentColor" stroke="none" />
                    </svg>
                </button>

                <button type="button" title="Numbered list" @click="toggleOrderedList()"
                    :class="isActive('orderedList') ? 'bg-gray-200 dark:bg-zinc-600' : 'hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="w-7 h-7 flex items-center justify-center rounded">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="10" y1="6" x2="21" y2="6" />
                        <line x1="10" y1="12" x2="21" y2="12" />
                        <line x1="10" y1="18" x2="21" y2="18" />
                        <path d="M4 6h1v4M4 10h2M6 18H4c0-1 2-2 2-3s-1-1.5-2-1" />
                    </svg>
                </button>

                <button type="button" title="Blockquote" @click="toggleBlockquote()"
                    :class="isActive('blockquote') ? 'bg-gray-200 dark:bg-zinc-600' : 'hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="w-7 h-7 flex items-center justify-center rounded">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5c-1.073 0-2.099-.49-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5c-1.073 0-2.099-.49-2.748-1.179z" />
                    </svg>
                </button>
            </div>

            
            <div class="flex items-center gap-0.5 pr-2 mr-1 border-r border-gray-300 dark:border-zinc-600">
                <button type="button" title="Align left" @click="alignLeft()"
                    :class="isActive({ textAlign: 'left' }) ? 'bg-gray-200 dark:bg-zinc-600' : 'hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="w-7 h-7 flex items-center justify-center rounded">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="21" y1="6" x2="3" y2="6" />
                        <line x1="15" y1="12" x2="3" y2="12" />
                        <line x1="17" y1="18" x2="3" y2="18" />
                    </svg>
                </button>

                <button type="button" title="Align center" @click="alignCenter()"
                    :class="isActive({ textAlign: 'center' }) ? 'bg-gray-200 dark:bg-zinc-600' : 'hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="w-7 h-7 flex items-center justify-center rounded">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="21" y1="6" x2="3" y2="6" />
                        <line x1="17" y1="12" x2="7" y2="12" />
                        <line x1="19" y1="18" x2="5" y2="18" />
                    </svg>
                </button>

                <button type="button" title="Align right" @click="alignRight()"
                    :class="isActive({ textAlign: 'right' }) ? 'bg-gray-200 dark:bg-zinc-600' : 'hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="w-7 h-7 flex items-center justify-center rounded">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="21" y1="6" x2="3" y2="6" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                        <line x1="21" y1="18" x2="7" y2="18" />
                    </svg>
                </button>
            </div>

            
            <div class="flex items-center gap-0.5">
                <button type="button" title="Undo" @click="undo()"
                    class="w-7 h-7 flex items-center justify-center rounded hover:bg-gray-200 dark:hover:bg-zinc-700">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 7v6h6" />
                        <path d="M21 17A9 9 0 006 5.7L3 8" />
                    </svg>
                </button>
                <button type="button" title="Redo" @click="redo()"
                    class="w-7 h-7 flex items-center justify-center rounded hover:bg-gray-200 dark:hover:bg-zinc-700">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 7v6h-6" />
                        <path d="M3 17a9 9 0 0015-3.7L21 8" />
                    </svg>
                </button>
            </div>
        </div>

        
        <div x-ref="editor" wire:ignore class="bg-white dark:bg-zinc-900"></div>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($error): ?>
        <p class="mt-1 text-sm text-red-600"><?php echo e($error); ?></p>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

</div>
<?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\resources\views/components/rich-editor.blade.php ENDPATH**/ ?>