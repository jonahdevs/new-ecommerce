<div class="container mx-auto px-4">
    <section class="flex items-center justify-between py-4 ">
        <h2 class="font-semibold text-xl text-zinc-800">You May Also Like</h2>

        <a href="<?php echo e(route('shop.index')); ?>" wire:navigate class="text-sheffield-red hover:underline text-sm">View
            All</a>
    </section>

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

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-2204193380-0', $__key);

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

            <!-- Navigation buttons -->
            <button type="button" @click="swiper?.slidePrev()"
                class="absolute top-1/2 left-0  -translate-y-1/2 -translate-x-1/2 z-30 flex items-center justify-center cursor-pointer group focus:outline-none w-8 h-8 rounded-full bg-brand-secondary/30 group-hover:bg-brand-secondary/50 group-focus:ring-4 group-focus:ring-brand-secondary/70 group-focus:outline-none">
                <?php if (isset($component)) { $__componentOriginala38b22240d1f0026bfe37a3c5effc3d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala38b22240d1f0026bfe37a3c5effc3d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.arrow-long-left','data' => ['class' => 'size-4 text-white']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.arrow-long-left'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4 text-white']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala38b22240d1f0026bfe37a3c5effc3d4)): ?>
<?php $attributes = $__attributesOriginala38b22240d1f0026bfe37a3c5effc3d4; ?>
<?php unset($__attributesOriginala38b22240d1f0026bfe37a3c5effc3d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala38b22240d1f0026bfe37a3c5effc3d4)): ?>
<?php $component = $__componentOriginala38b22240d1f0026bfe37a3c5effc3d4; ?>
<?php unset($__componentOriginala38b22240d1f0026bfe37a3c5effc3d4); ?>
<?php endif; ?>
                <span class="sr-only">Previous</span>
            </button>

            <button type="button" @click="swiper?.slideNext()"
                class="absolute top-1/2 right-0 -translate-y-1/2 translate-x-1/2 z-30 flex items-center justify-center cursor-pointer group focus:outline-none w-8 h-8 rounded-full bg-brand-secondary/30 group-hover:bg-brand-secondary/50 group-focus:ring-4 group-focus:ring-brand-secondary/70 group-focus:outline-none">
                <?php if (isset($component)) { $__componentOriginal35b86e1ac5a257d741538ecc79e20be3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal35b86e1ac5a257d741538ecc79e20be3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.arrow-long-right','data' => ['class' => 'size-4 text-white']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.arrow-long-right'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4 text-white']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal35b86e1ac5a257d741538ecc79e20be3)): ?>
<?php $attributes = $__attributesOriginal35b86e1ac5a257d741538ecc79e20be3; ?>
<?php unset($__attributesOriginal35b86e1ac5a257d741538ecc79e20be3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal35b86e1ac5a257d741538ecc79e20be3)): ?>
<?php $component = $__componentOriginal35b86e1ac5a257d741538ecc79e20be3; ?>
<?php unset($__componentOriginal35b86e1ac5a257d741538ecc79e20be3); ?>
<?php endif; ?>
                <span class="sr-only">Next</span>
            </button>
        </div>
    </section>
</div>
<?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\resources\views/pages/home/products.blade.php ENDPATH**/ ?>