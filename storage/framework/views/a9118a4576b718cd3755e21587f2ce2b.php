<section>
    <!-- Products slider -->
    <div class="relative" x-data="{
        swiper: null,
        init() {
            if (this.swiper) {
                this.swiper.destroy(true, true);
            }
    
            this.swiper = new Swiper('#youMayAlsoLike', {
                slidesPerView: 2,
                slidesPerGroup: 1,
                spaceBetween: 12,
                loop: true,
                speed: 600,
                breakpoints: {
                    375: {
                        slidesPerView: 2,
                        slidesPerGroup: 2,
                        grid: { rows: 2, fill: 'row' }
                    },
                    480: {
                        slidesPerView: 2,
                        slidesPerGroup: 2,
                        grid: { rows: 2, fill: 'row' }
                    },
                    640: {
                        slidesPerView: 3,
                        slidesPerGroup: 3,
                        grid: { rows: 2, fill: 'row' }
                    },
                    768: {
                        slidesPerView: 4,
                        slidesPerGroup: 4,
                        grid: { rows: 2, fill: 'row' }
                    },
                    1024: {
                        slidesPerView: 5,
                        slidesPerGroup: 5,
                        grid: { rows: 2, fill: 'row' }
                    },
                    1280: {
                        slidesPerView: 6,
                        slidesPerGroup: 6,
                        grid: { rows: 2, fill: 'row' }
                    },
                },
            });
            this.$nextTick(() => {
                document.getElementById('youMayAlsoLike').classList.remove('opacity-0')
            })
        }
    }">
        <div class="swiper opacity-0 transition-opacity duration-500" id="youMayAlsoLike">
            <div class="swiper-wrapper pb-5">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                    <div class="swiper-slide h-auto!" :key="'product-' . $product->id">
                        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('product-card', ['product' => $product]);

$__keyOuter = $__key ?? null;

$__key = null;
$__componentSlots = [];

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-3424125146-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key, $__componentSlots);

echo $__html;

unset($__html);
unset($__key);
$__key = $__keyOuter;
unset($__keyOuter);
unset($__name);
unset($__params);
unset($__componentSlots);
unset($__split);
?>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        </div>

        
        <button type="button" @click="swiper?.slidePrev()"
            class="absolute top-1/2 left-0 -translate-y-1/2 -translate-x-1/2 z-30 w-7 h-7 rounded-full flex items-center justify-center bg-black/20 hover:bg-black/40 backdrop-blur-sm border border-white/20 hover:border-white/40 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white/50 cursor-pointer">
            <?php if (isset($component)) { $__componentOriginal93e8a1cf63877447e3f60f50005ff258 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93e8a1cf63877447e3f60f50005ff258 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.chevron-left','data' => ['class' => 'size-3.5 text-white']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.chevron-left'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-3.5 text-white']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal93e8a1cf63877447e3f60f50005ff258)): ?>
<?php $attributes = $__attributesOriginal93e8a1cf63877447e3f60f50005ff258; ?>
<?php unset($__attributesOriginal93e8a1cf63877447e3f60f50005ff258); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal93e8a1cf63877447e3f60f50005ff258)): ?>
<?php $component = $__componentOriginal93e8a1cf63877447e3f60f50005ff258; ?>
<?php unset($__componentOriginal93e8a1cf63877447e3f60f50005ff258); ?>
<?php endif; ?>
            <span class="sr-only">Previous</span>
        </button>

        <button type="button" @click="swiper?.slideNext()"
            class="absolute top-1/2 right-0 -translate-y-1/2  translate-x-1/2  z-30 w-7 h-7 rounded-full flex items-center justify-center bg-black/20 hover:bg-black/40 backdrop-blur-sm border border-white/20 hover:border-white/40 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white/50 cursor-pointer">
            <?php if (isset($component)) { $__componentOriginal31cb76c8d087d4f00797aeea7232b4c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal31cb76c8d087d4f00797aeea7232b4c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.chevron-right','data' => ['class' => 'size-3.5 text-white']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.chevron-right'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-3.5 text-white']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal31cb76c8d087d4f00797aeea7232b4c3)): ?>
<?php $attributes = $__attributesOriginal31cb76c8d087d4f00797aeea7232b4c3; ?>
<?php unset($__attributesOriginal31cb76c8d087d4f00797aeea7232b4c3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal31cb76c8d087d4f00797aeea7232b4c3)): ?>
<?php $component = $__componentOriginal31cb76c8d087d4f00797aeea7232b4c3; ?>
<?php unset($__componentOriginal31cb76c8d087d4f00797aeea7232b4c3); ?>
<?php endif; ?>
            <span class="sr-only">Next</span>
        </button>
    </div>
</section>
<?php /**PATH C:\Users\jonah\Herd\sheffield_ecommerce\resources\views/pages/home/products.blade.php ENDPATH**/ ?>