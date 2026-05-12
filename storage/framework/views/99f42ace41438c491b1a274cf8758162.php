<?php
use Livewire\Component;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Product;
use App\Models\Category;
?>

<div class="w-full">

    
    <div class="hidden lg:block w-full max-w-xl relative">
        <?php if (isset($component)) { $__componentOriginal26c546557cdc09040c8dd00b2090afd0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal26c546557cdc09040c8dd00b2090afd0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::input.index','data' => ['wire:model.live.debounce.300ms' => 'search','wire:loading.attr.remove' => 'disabled','icon' => 'magnifying-glass','placeholder' => 'Search products...','class' => 'w-full','autocomplete' => 'off','clearable' => true,'@focus' => '$wire.showSuggestions = ($wire.suggestions?.products?.length > 0)','@keydown.escape' => '$wire.showSuggestions = false','@keydown.enter' => 'window.location.href = \''.e(route('shop.index')).'?search=\' + encodeURIComponent($wire.search)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:model.live.debounce.300ms' => 'search','wire:loading.attr.remove' => 'disabled','icon' => 'magnifying-glass','placeholder' => 'Search products...','class' => 'w-full','autocomplete' => 'off','clearable' => true,'@focus' => '$wire.showSuggestions = ($wire.suggestions?.products?.length > 0)','@keydown.escape' => '$wire.showSuggestions = false','@keydown.enter' => 'window.location.href = \''.e(route('shop.index')).'?search=\' + encodeURIComponent($wire.search)']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal26c546557cdc09040c8dd00b2090afd0)): ?>
<?php $attributes = $__attributesOriginal26c546557cdc09040c8dd00b2090afd0; ?>
<?php unset($__attributesOriginal26c546557cdc09040c8dd00b2090afd0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal26c546557cdc09040c8dd00b2090afd0)): ?>
<?php $component = $__componentOriginal26c546557cdc09040c8dd00b2090afd0; ?>
<?php unset($__componentOriginal26c546557cdc09040c8dd00b2090afd0); ?>
<?php endif; ?>

        <div wire:show="showSuggestions" @click.outside="$wire.showSuggestions = false"
            class="absolute z-50 w-full bg-white rounded-lg shadow-lg border border-zinc-200 top-full mt-1 max-h-[30rem] overflow-y-auto">
            <?php echo $__env->make('partials.search-suggestions', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>

    
    <button wire:click="openMobile" type="button"
        class="lg:hidden flex items-center justify-center w-9 h-9 rounded-md text-zinc-700 hover:bg-zinc-100 transition-colors"
        aria-label="Open search">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </button>

    
    
    <template x-teleport="body">
        <div x-show="$wire.mobileOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2" class="fixed inset-0 z-[200] bg-white flex flex-col"
            @keydown.escape.window="$wire.closeMobile()">

            
            <div class="flex items-center gap-3 px-4 py-3 border-b border-zinc-200 shrink-0">
                <button wire:click="closeMobile" type="button"
                    class="shrink-0 text-zinc-600 hover:text-zinc-900 transition-colors" aria-label="Close search">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <?php if (isset($component)) { $__componentOriginal26c546557cdc09040c8dd00b2090afd0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal26c546557cdc09040c8dd00b2090afd0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::input.index','data' => ['wire:model.live.debounce.300ms' => 'search','icon' => 'magnifying-glass','placeholder' => 'Search products...','class' => 'w-full','autocomplete' => 'off','clearable' => true,'xInit' => '$nextTick(() => $el.querySelector(\'input\')?.focus())','@keydown.escape' => '$wire.closeMobile()','@keydown.enter' => '
                        window.location.href = \''.e(route('shop.index')).'?search=\' + encodeURIComponent($wire.search);
                        $wire.closeMobile();
                    ']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:model.live.debounce.300ms' => 'search','icon' => 'magnifying-glass','placeholder' => 'Search products...','class' => 'w-full','autocomplete' => 'off','clearable' => true,'x-init' => '$nextTick(() => $el.querySelector(\'input\')?.focus())','@keydown.escape' => '$wire.closeMobile()','@keydown.enter' => '
                        window.location.href = \''.e(route('shop.index')).'?search=\' + encodeURIComponent($wire.search);
                        $wire.closeMobile();
                    ']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal26c546557cdc09040c8dd00b2090afd0)): ?>
<?php $attributes = $__attributesOriginal26c546557cdc09040c8dd00b2090afd0; ?>
<?php unset($__attributesOriginal26c546557cdc09040c8dd00b2090afd0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal26c546557cdc09040c8dd00b2090afd0)): ?>
<?php $component = $__componentOriginal26c546557cdc09040c8dd00b2090afd0; ?>
<?php unset($__componentOriginal26c546557cdc09040c8dd00b2090afd0); ?>
<?php endif; ?>
            </div>

            
            <div class="flex-1 overflow-y-auto">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showSuggestions): ?>
                    <?php echo $__env->make('partials.search-suggestions', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center h-full gap-3 text-zinc-400 px-8 text-center">
                        <svg class="w-12 h-12 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <p class="text-sm">Type at least 2 characters to search.</p>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

        </div>
    </template>

</div>

<?php $__env->startPush('scripts'); ?>
    <script>
        // Reset any stuck Livewire loading states on error pages
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[wire\\:loading]').forEach(el => {
                el.style.display = 'none';
            });

            // Also stop any Alpine loading states
            document.querySelectorAll('[wire\\:loading\\.class]').forEach(el => {
                el.classList.remove('opacity-50', 'pointer-events-none', 'cursor-wait');
            });
        });
    </script>
<?php $__env->stopPush(); ?><?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\storage\framework/views/livewire/views/87a5632a.blade.php ENDPATH**/ ?>