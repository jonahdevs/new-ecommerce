<?php
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Livewire\Attributes\{Computed, Title};
use Livewire\Component;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
?>

<div>

    
    
    
    <div class="flex items-center justify-between mb-5">
        <div>
            <?php if (isset($component)) { $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::heading','data' => ['size' => 'xl','class' => 'font-bold tracking-tight']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'xl','class' => 'font-bold tracking-tight']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Dashboard <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $attributes = $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $component = $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal43e8c568bbb8b06b9124aad3ccf4ec97 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal43e8c568bbb8b06b9124aad3ccf4ec97 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::subheading','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::subheading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
<?php echo e($this->periodLabel); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal43e8c568bbb8b06b9124aad3ccf4ec97)): ?>
<?php $attributes = $__attributesOriginal43e8c568bbb8b06b9124aad3ccf4ec97; ?>
<?php unset($__attributesOriginal43e8c568bbb8b06b9124aad3ccf4ec97); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal43e8c568bbb8b06b9124aad3ccf4ec97)): ?>
<?php $component = $__componentOriginal43e8c568bbb8b06b9124aad3ccf4ec97; ?>
<?php unset($__componentOriginal43e8c568bbb8b06b9124aad3ccf4ec97); ?>
<?php endif; ?>
        </div>
        <div class="flex items-center gap-2">
            <?php if (isset($component)) { $__componentOriginalb06f0c5905a9427a630c5e299af7ce46 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb06f0c5905a9427a630c5e299af7ce46 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.loading','data' => ['wire:loading' => true,'wire:target' => 'setDateRange','class' => 'dark:text-white! size-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.loading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:loading' => true,'wire:target' => 'setDateRange','class' => 'dark:text-white! size-3.5']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb06f0c5905a9427a630c5e299af7ce46)): ?>
<?php $attributes = $__attributesOriginalb06f0c5905a9427a630c5e299af7ce46; ?>
<?php unset($__attributesOriginalb06f0c5905a9427a630c5e299af7ce46); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb06f0c5905a9427a630c5e299af7ce46)): ?>
<?php $component = $__componentOriginalb06f0c5905a9427a630c5e299af7ce46; ?>
<?php unset($__componentOriginalb06f0c5905a9427a630c5e299af7ce46); ?>
<?php endif; ?>

            <div class="relative" wire:ignore>
                <input type="text" readonly
                    class="dashboard-date-range w-64 pl-8 pr-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-zinc-300 hover:border-zinc-400 transition-colors" />
                <?php if (isset($component)) { $__componentOriginalec36de86b0c342dc76f8040ade97006d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalec36de86b0c342dc76f8040ade97006d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.calendar-days','data' => ['class' => 'size-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.calendar-days'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalec36de86b0c342dc76f8040ade97006d)): ?>
<?php $attributes = $__attributesOriginalec36de86b0c342dc76f8040ade97006d; ?>
<?php unset($__attributesOriginalec36de86b0c342dc76f8040ade97006d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalec36de86b0c342dc76f8040ade97006d)): ?>
<?php $component = $__componentOriginalec36de86b0c342dc76f8040ade97006d; ?>
<?php unset($__componentOriginalec36de86b0c342dc76f8040ade97006d); ?>
<?php endif; ?>
            </div>
        </div>
    </div>

    
    
    
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

        <?php if (isset($component)) { $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::card.index','data' => ['class' => 'p-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'p-4']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <div class="flex items-start justify-between mb-3">
                <?php if (isset($component)) { $__componentOriginal0638ebfbd490c7a414275d493e14cb4e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::text','data' => ['class' => 'text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    Total revenue <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $attributes = $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $component = $__componentOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
                <div
                    class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center shrink-0">
                    <?php if (isset($component)) { $__componentOriginal1a2aab62646bbf4070a26cfe0540f0d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1a2aab62646bbf4070a26cfe0540f0d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.banknotes','data' => ['class' => 'size-4 text-emerald-600 dark:text-emerald-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.banknotes'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4 text-emerald-600 dark:text-emerald-400']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1a2aab62646bbf4070a26cfe0540f0d4)): ?>
<?php $attributes = $__attributesOriginal1a2aab62646bbf4070a26cfe0540f0d4; ?>
<?php unset($__attributesOriginal1a2aab62646bbf4070a26cfe0540f0d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1a2aab62646bbf4070a26cfe0540f0d4)): ?>
<?php $component = $__componentOriginal1a2aab62646bbf4070a26cfe0540f0d4; ?>
<?php unset($__componentOriginal1a2aab62646bbf4070a26cfe0540f0d4); ?>
<?php endif; ?>
                </div>
            </div>
            <?php if (isset($component)) { $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::heading','data' => ['size' => 'xl','class' => 'text-2xl! font-bold! mb-1.5','wire:key' => 'kpi-revenue-'.e($this->salesStats['revenue']).'','xData' => 'countUp({ to: '.e($this->salesStats['revenue']).', decimals: 2, prefix: \'KES \' })','xText' => 'display']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'xl','class' => 'text-2xl! font-bold! mb-1.5','wire:key' => 'kpi-revenue-'.e($this->salesStats['revenue']).'','x-data' => 'countUp({ to: '.e($this->salesStats['revenue']).', decimals: 2, prefix: \'KES \' })','x-text' => 'display']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $attributes = $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $component = $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
            <div class="flex items-center gap-1.5">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->salesStats['revenue_trend'] !== null): ?>
                    <span
                        class="inline-flex items-center text-[10px] font-semibold px-1.5 py-0.5 rounded-full <?php echo e($this->salesStats['revenue_trend'] >= 0 ? 'bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400' : 'bg-rose-100 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400'); ?>">
                        <?php echo e($this->salesStats['revenue_trend'] >= 0 ? '▲' : '▼'); ?>

                        <?php echo e(abs($this->salesStats['revenue_trend'])); ?>%
                    </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal0638ebfbd490c7a414275d493e14cb4e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::text','data' => ['class' => 'text-xs text-zinc-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-xs text-zinc-400']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
than last period <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $attributes = $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $component = $__componentOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $attributes = $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $component = $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::card.index','data' => ['class' => 'p-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'p-4']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <div class="flex items-start justify-between mb-3">
                <?php if (isset($component)) { $__componentOriginal0638ebfbd490c7a414275d493e14cb4e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::text','data' => ['class' => 'text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    Orders <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $attributes = $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $component = $__componentOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
                <div
                    class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center shrink-0">
                    <?php if (isset($component)) { $__componentOriginalc43f195f1b3468dd3dad13eb2914c8e1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc43f195f1b3468dd3dad13eb2914c8e1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.shopping-bag','data' => ['class' => 'size-4 text-blue-600 dark:text-blue-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.shopping-bag'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4 text-blue-600 dark:text-blue-400']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc43f195f1b3468dd3dad13eb2914c8e1)): ?>
<?php $attributes = $__attributesOriginalc43f195f1b3468dd3dad13eb2914c8e1; ?>
<?php unset($__attributesOriginalc43f195f1b3468dd3dad13eb2914c8e1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc43f195f1b3468dd3dad13eb2914c8e1)): ?>
<?php $component = $__componentOriginalc43f195f1b3468dd3dad13eb2914c8e1; ?>
<?php unset($__componentOriginalc43f195f1b3468dd3dad13eb2914c8e1); ?>
<?php endif; ?>
                </div>
            </div>
            <?php if (isset($component)) { $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::heading','data' => ['size' => 'xl','class' => 'text-2xl! font-bold! mb-1.5','wire:key' => 'kpi-orders-'.e($this->salesStats['order_count']).'','xData' => 'countUp({ to: '.e($this->salesStats['order_count']).' })','xText' => 'display']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'xl','class' => 'text-2xl! font-bold! mb-1.5','wire:key' => 'kpi-orders-'.e($this->salesStats['order_count']).'','x-data' => 'countUp({ to: '.e($this->salesStats['order_count']).' })','x-text' => 'display']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $attributes = $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $component = $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
            <div class="flex items-center gap-1.5">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->salesStats['orders_trend'] !== null): ?>
                    <span
                        class="inline-flex items-center text-[10px] font-semibold px-1.5 py-0.5 rounded-full <?php echo e($this->salesStats['orders_trend'] >= 0 ? 'bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400' : 'bg-rose-100 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400'); ?>">
                        <?php echo e($this->salesStats['orders_trend'] >= 0 ? '▲' : '▼'); ?>

                        <?php echo e(abs($this->salesStats['orders_trend'])); ?>%
                    </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal0638ebfbd490c7a414275d493e14cb4e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::text','data' => ['class' => 'text-xs text-zinc-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-xs text-zinc-400']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
<?php echo e($this->salesStats['paid_count']); ?> paid
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $attributes = $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $component = $__componentOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $attributes = $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $component = $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::card.index','data' => ['class' => 'p-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'p-4']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <div class="flex items-start justify-between mb-3">
                <?php if (isset($component)) { $__componentOriginal0638ebfbd490c7a414275d493e14cb4e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::text','data' => ['class' => 'text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    Customers <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $attributes = $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $component = $__componentOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
                <div
                    class="w-9 h-9 rounded-lg bg-violet-50 dark:bg-violet-950/50 flex items-center justify-center shrink-0">
                    <?php if (isset($component)) { $__componentOriginal4e4f522adb19cc742fb2b199df7e6c95 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4e4f522adb19cc742fb2b199df7e6c95 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.users','data' => ['class' => 'size-4 text-violet-600 dark:text-violet-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.users'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4 text-violet-600 dark:text-violet-400']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4e4f522adb19cc742fb2b199df7e6c95)): ?>
<?php $attributes = $__attributesOriginal4e4f522adb19cc742fb2b199df7e6c95; ?>
<?php unset($__attributesOriginal4e4f522adb19cc742fb2b199df7e6c95); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4e4f522adb19cc742fb2b199df7e6c95)): ?>
<?php $component = $__componentOriginal4e4f522adb19cc742fb2b199df7e6c95; ?>
<?php unset($__componentOriginal4e4f522adb19cc742fb2b199df7e6c95); ?>
<?php endif; ?>
                </div>
            </div>
            <?php if (isset($component)) { $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::heading','data' => ['size' => 'xl','class' => 'text-2xl! font-bold! mb-1.5','wire:key' => 'kpi-customers-'.e($this->customerStats['total']).'','xData' => 'countUp({ to: '.e($this->customerStats['total']).' })','xText' => 'display']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'xl','class' => 'text-2xl! font-bold! mb-1.5','wire:key' => 'kpi-customers-'.e($this->customerStats['total']).'','x-data' => 'countUp({ to: '.e($this->customerStats['total']).' })','x-text' => 'display']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $attributes = $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $component = $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
            <div class="flex items-center gap-1.5">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->customerStats['new_trend'] !== null): ?>
                    <span
                        class="inline-flex items-center text-[10px] font-semibold px-1.5 py-0.5 rounded-full <?php echo e($this->customerStats['new_trend'] >= 0 ? 'bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400' : 'bg-rose-100 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400'); ?>">
                        <?php echo e($this->customerStats['new_trend'] >= 0 ? '▲' : '▼'); ?>

                        <?php echo e($this->customerStats['new']); ?> new
                    </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal0638ebfbd490c7a414275d493e14cb4e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::text','data' => ['class' => 'text-xs text-zinc-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-xs text-zinc-400']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
than last period <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $attributes = $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $component = $__componentOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $attributes = $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $component = $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::card.index','data' => ['class' => 'p-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'p-4']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <div class="flex items-start justify-between mb-3">
                <?php if (isset($component)) { $__componentOriginal0638ebfbd490c7a414275d493e14cb4e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::text','data' => ['class' => 'text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    Products <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $attributes = $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $component = $__componentOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
                <div
                    class="w-9 h-9 rounded-lg bg-teal-50 dark:bg-teal-950/50 flex items-center justify-center shrink-0">
                    <?php if (isset($component)) { $__componentOriginald5dcfefa1bd397da70b3e8652c3e12ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald5dcfefa1bd397da70b3e8652c3e12ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.cube','data' => ['class' => 'size-4 text-teal-600 dark:text-teal-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.cube'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4 text-teal-600 dark:text-teal-400']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald5dcfefa1bd397da70b3e8652c3e12ee)): ?>
<?php $attributes = $__attributesOriginald5dcfefa1bd397da70b3e8652c3e12ee; ?>
<?php unset($__attributesOriginald5dcfefa1bd397da70b3e8652c3e12ee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald5dcfefa1bd397da70b3e8652c3e12ee)): ?>
<?php $component = $__componentOriginald5dcfefa1bd397da70b3e8652c3e12ee; ?>
<?php unset($__componentOriginald5dcfefa1bd397da70b3e8652c3e12ee); ?>
<?php endif; ?>
                </div>
            </div>
            <?php if (isset($component)) { $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::heading','data' => ['size' => 'xl','class' => 'text-2xl! font-bold! mb-1.5','wire:key' => 'kpi-products-'.e($this->productStats['active']).'','xData' => 'countUp({ to: '.e($this->productStats['active']).' })','xText' => 'display']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'xl','class' => 'text-2xl! font-bold! mb-1.5','wire:key' => 'kpi-products-'.e($this->productStats['active']).'','x-data' => 'countUp({ to: '.e($this->productStats['active']).' })','x-text' => 'display']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $attributes = $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $component = $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
            <div class="flex items-center gap-1.5">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->productStats['low_stock'] + $this->productStats['out_of_stock'] > 0): ?>
                    <span
                        class="inline-flex items-center text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-400">
                        <?php echo e($this->productStats['low_stock'] + $this->productStats['out_of_stock']); ?> need
                        attention
                    </span>
                <?php else: ?>
                    <span
                        class="inline-flex items-center text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400">All
                        stocked</span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal0638ebfbd490c7a414275d493e14cb4e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::text','data' => ['class' => 'text-xs text-zinc-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-xs text-zinc-400']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
active <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $attributes = $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $component = $__componentOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $attributes = $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $component = $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>

    </div>

    
    
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        
        <?php if (isset($component)) { $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::card.index','data' => ['class' => 'p-0 lg:col-span-2 h-full flex flex-col ']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'p-0 lg:col-span-2 h-full flex flex-col ']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <div>
                    <?php if (isset($component)) { $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::heading','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Revenue <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $attributes = $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $component = $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal0638ebfbd490c7a414275d493e14cb4e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::text','data' => ['class' => 'text-[11px] text-zinc-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-[11px] text-zinc-400']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
<?php echo e($this->periodLabel); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $attributes = $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $component = $__componentOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
                </div>
                <div class="flex items-center gap-1 p-0.5 rounded-lg bg-zinc-100 dark:bg-zinc-800"
                    x-data="{ activeType: 'area' }">
                    <button @click="activeType = 'area'; window.switchRevenueChartType('area')" title="Area chart"
                        :class="activeType === 'area'
                            ?
                            'bg-white dark:bg-zinc-700 shadow-sm text-zinc-800 dark:text-zinc-100' :
                            'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300'"
                        class="p-1.5 rounded-md transition-colors">
                        <?php if (isset($component)) { $__componentOriginal086375a9b5b245d475b84c0aa58c183d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal086375a9b5b245d475b84c0aa58c183d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.presentation-chart-line','data' => ['class' => 'size-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.presentation-chart-line'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal086375a9b5b245d475b84c0aa58c183d)): ?>
<?php $attributes = $__attributesOriginal086375a9b5b245d475b84c0aa58c183d; ?>
<?php unset($__attributesOriginal086375a9b5b245d475b84c0aa58c183d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal086375a9b5b245d475b84c0aa58c183d)): ?>
<?php $component = $__componentOriginal086375a9b5b245d475b84c0aa58c183d; ?>
<?php unset($__componentOriginal086375a9b5b245d475b84c0aa58c183d); ?>
<?php endif; ?>
                    </button>
                    <button @click="activeType = 'bar'; window.switchRevenueChartType('bar')" title="Bar chart"
                        :class="activeType === 'bar'
                            ?
                            'bg-white dark:bg-zinc-700 shadow-sm text-zinc-800 dark:text-zinc-100' :
                            'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300'"
                        class="p-1.5 rounded-md transition-colors">
                        <?php if (isset($component)) { $__componentOriginal82067727c95f13dc4198f80e35cb9c11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal82067727c95f13dc4198f80e35cb9c11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.chart-bar','data' => ['class' => 'size-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.chart-bar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal82067727c95f13dc4198f80e35cb9c11)): ?>
<?php $attributes = $__attributesOriginal82067727c95f13dc4198f80e35cb9c11; ?>
<?php unset($__attributesOriginal82067727c95f13dc4198f80e35cb9c11); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal82067727c95f13dc4198f80e35cb9c11)): ?>
<?php $component = $__componentOriginal82067727c95f13dc4198f80e35cb9c11; ?>
<?php unset($__componentOriginal82067727c95f13dc4198f80e35cb9c11); ?>
<?php endif; ?>
                    </button>
                </div>
            </div>

            
            <div class="flex flex-col md:flex-row min-h-0 flex-1">

                
                <div
                    class="flex-1 min-w-0 px-5 pt-4 pb-5 border-b md:border-b-0 md:border-r border-zinc-100 dark:border-zinc-800">
                    <div id="revenueChartData" data-labels="<?php echo e(json_encode($this->revenueChartData['labels'])); ?>"
                        data-revenue="<?php echo e(json_encode($this->revenueChartData['values'])); ?>"
                        data-orders="<?php echo e(json_encode($this->revenueChartData['order_counts'])); ?>"
                        data-refunds="<?php echo e(json_encode($this->revenueChartData['refund_counts'])); ?>"
                        data-chart-type="area">
                    </div>
                    <div wire:ignore style="position:relative; height:100%; width:100%;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                
                <div class="w-full md:w-48 shrink-0 flex flex-col divide-y divide-zinc-100 dark:divide-zinc-800">

                    <div class="px-5 py-4">
                        <p class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest mb-1.5">Orders</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 leading-none"
                            <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'chart-orders-'.e($this->salesStats['order_count']).''; ?>wire:key="chart-orders-<?php echo e($this->salesStats['order_count']); ?>" x-data="countUp({ to: <?php echo e($this->salesStats['order_count']); ?> })"
                            x-text="display">
                        </p>
                    </div>

                    <div class="px-5 py-4">
                        <p class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest mb-1.5">Earnings</p>
                        <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100 leading-none break-all"
                            <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'chart-revenue-'.e($this->salesStats['revenue']).''; ?>wire:key="chart-revenue-<?php echo e($this->salesStats['revenue']); ?>" x-data="countUp({ to: <?php echo e($this->salesStats['revenue']); ?>, decimals: 2, prefix: 'KES ' })"
                            x-text="display">
                        </p>
                    </div>

                    <div class="px-5 py-4">
                        <p class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest mb-1.5">Paid orders
                        </p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 leading-none"
                            <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'chart-paid-'.e($this->salesStats['paid_count']).''; ?>wire:key="chart-paid-<?php echo e($this->salesStats['paid_count']); ?>" x-data="countUp({ to: <?php echo e($this->salesStats['paid_count']); ?> })"
                            x-text="display">
                        </p>
                    </div>

                    <div class="px-5 py-4">
                        <p class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest mb-1.5">Conversion
                        </p>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->quotationStats['conversion_rate'] !== null): ?>
                            <p class="text-2xl font-bold text-emerald-500 leading-none"
                                <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'chart-conversion-'.e($this->quotationStats['conversion_rate']).''; ?>wire:key="chart-conversion-<?php echo e($this->quotationStats['conversion_rate']); ?>"
                                x-data="countUp({ to: <?php echo e($this->quotationStats['conversion_rate']); ?>, decimals: 1, suffix: '%' })" x-text="display">
                            </p>
                        <?php else: ?>
                            <p class="text-2xl font-bold text-zinc-300 dark:text-zinc-600 leading-none">—</p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                </div>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $attributes = $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $component = $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>


        
        <?php if (isset($component)) { $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::card.index','data' => ['class' => 'p-0 overflow-hidden']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'p-0 overflow-hidden']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-100 dark:border-zinc-800">
                <div>
                    <?php if (isset($component)) { $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::heading','data' => ['size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'sm']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Top Sales Locations <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $attributes = $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $component = $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal0638ebfbd490c7a414275d493e14cb4e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::text','data' => ['class' => 'text-[10px] text-zinc-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-[10px] text-zinc-400']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Distribution by city <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $attributes = $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $component = $__componentOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
                </div>
                <?php if (isset($component)) { $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::link','data' => ['href' => route('admin.coming-soon', ['feature' => 'Sales Location Report', 'description' =>
                        'Detailed analytics on sales distribution across different cities and regions.'
                    ]),'wire:navigate' => true,'class' => 'text-xs']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.coming-soon', ['feature' => 'Sales Location Report', 'description' =>
                        'Detailed analytics on sales distribution across different cities and regions.'
                    ])),'wire:navigate' => true,'class' => 'text-xs']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Report <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477)): ?>
<?php $attributes = $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477; ?>
<?php unset($__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477)): ?>
<?php $component = $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477; ?>
<?php unset($__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477); ?>
<?php endif; ?>
            </div>
            <?php
                $locations = [
                    ['name' => 'Nairobi', 'count' => 234, 'color' => 'bg-emerald-500'],
                    ['name' => 'Mombasa', 'count' => 98, 'color' => 'bg-blue-500'],
                    ['name' => 'Kisumu', 'count' => 67, 'color' => 'bg-amber-500'],
                    ['name' => 'Nakuru', 'count' => 41, 'color' => 'bg-violet-500'],
                    ['name' => 'Eldoret', 'count' => 28, 'color' => 'bg-rose-500'],
                ];
                $maxCount = $locations[0]['count'];
            ?>


            <div class="px-4 py-4">
                
                <div
                    class="relative flex items-center justify-center h-32 overflow-hidden rounded-xl bg-zinc-50 dark:bg-white/5">
                    
                    <svg viewBox="0 0 200 100"
                        class="absolute inset-0 w-full h-full opacity-10 dark:opacity-20 stroke-current text-zinc-400">
                        <path d="M40,20 Q60,10 80,30 T120,20 T160,40" fill="none" stroke-width="0.5" />
                        <circle cx="150" cy="60" r="40" fill="none" stroke-dasharray="2 2" />
                    </svg>

                    <div class="relative flex gap-6">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = array_slice($locations, 0, 3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <div class="flex flex-col items-center">
                                <div class="relative">
                                    <div class="w-3 h-3 rounded-full <?php echo e($loc['color']); ?> shadow-lg shadow-current/20">
                                    </div>
                                    <div
                                        class="absolute inset-0 w-3 h-3 rounded-full <?php echo e($loc['color']); ?> animate-ping opacity-20">
                                    </div>
                                </div>
                                <span class="mt-2 text-[10px] font-medium text-zinc-500"><?php echo e($loc['name']); ?></span>
                            </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="px-4 pb-4 space-y-3">

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <div class="group">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full <?php echo e($loc['color']); ?>"></div>
                                <?php if (isset($component)) { $__componentOriginal0638ebfbd490c7a414275d493e14cb4e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::text','data' => ['class' => 'text-xs font-medium']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-xs font-medium']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
<?php echo e($loc['name']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $attributes = $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $component = $__componentOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
                            </div>
                            <?php if (isset($component)) { $__componentOriginal0638ebfbd490c7a414275d493e14cb4e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::text','data' => ['class' => 'text-xs text-zinc-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-xs text-zinc-400']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
<?php echo e($loc['count']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $attributes = $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $component = $__componentOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
                        </div>
                        
                        <div class="w-full h-1.5 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                            <div class="<?php echo e($loc['color']); ?> h-full rounded-full transition-all duration-500"
                                style="width: <?php echo e(($loc['count'] / $maxCount) * 100); ?>%"></div>
                        </div>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>

            <div class="px-5 py-3 border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-white/5">
                <div class="flex items-center justify-between">
                    <?php if (isset($component)) { $__componentOriginal0638ebfbd490c7a414275d493e14cb4e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::text','data' => ['class' => 'text-[10px] uppercase tracking-wider font-semibold text-zinc-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-[10px] uppercase tracking-wider font-semibold text-zinc-400']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Total Volume
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $attributes = $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $component = $__componentOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal0638ebfbd490c7a414275d493e14cb4e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::text','data' => ['class' => 'text-xs font-bold']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-xs font-bold']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
<?php echo e(array_sum(array_column($locations, 'count'))); ?>

                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $attributes = $__attributesOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__attributesOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e)): ?>
<?php $component = $__componentOriginal0638ebfbd490c7a414275d493e14cb4e; ?>
<?php unset($__componentOriginal0638ebfbd490c7a414275d493e14cb4e); ?>
<?php endif; ?>
                </div>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $attributes = $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $component = $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>
    </div>


    
    
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        
        <?php if (isset($component)) { $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::card.index','data' => ['class' => 'p-0 flex flex-col']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'p-0 flex flex-col']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <?php if (isset($component)) { $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::heading','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Recent Activity <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $attributes = $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $component = $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::link','data' => ['href' => route('admin.activity-logs.index'),'wire:navigate' => true,'class' => 'text-xs']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.activity-logs.index')),'wire:navigate' => true,'class' => 'text-xs']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
View all
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477)): ?>
<?php $attributes = $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477; ?>
<?php unset($__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477)): ?>
<?php $component = $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477; ?>
<?php unset($__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477); ?>
<?php endif; ?>
            </div>

            <div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $this->recentActivities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div
                            class="flex items-start gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                            <div class="shrink-0 mt-0.5">
                                <?php
                                    $iconClass = match (true) {
                                        str_contains($activity->description, 'failed') ||
                                            str_contains($activity->description, 'cancelled')
                                            => 'text-red-600 dark:text-red-400',
                                        str_contains($activity->description, 'confirmed') ||
                                            str_contains($activity->description, 'paid') ||
                                            str_contains($activity->description, 'success') ||
                                            str_contains($activity->description, 'accepted')
                                            => 'text-green-600 dark:text-green-400',
                                        str_contains($activity->description, 'initiated') ||
                                            str_contains($activity->description, 'requested')
                                            => 'text-yellow-600 dark:text-yellow-400',
                                        default => 'text-blue-600 dark:text-blue-400',
                                    };
                                ?>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(str_contains($activity->description, 'payment')): ?>
                                    <?php if (isset($component)) { $__componentOriginal970eea507e21d10a438cf9d4525d45dc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal970eea507e21d10a438cf9d4525d45dc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.currency-dollar','data' => ['class' => 'size-5 '.e($iconClass).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.currency-dollar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-5 '.e($iconClass).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal970eea507e21d10a438cf9d4525d45dc)): ?>
<?php $attributes = $__attributesOriginal970eea507e21d10a438cf9d4525d45dc; ?>
<?php unset($__attributesOriginal970eea507e21d10a438cf9d4525d45dc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal970eea507e21d10a438cf9d4525d45dc)): ?>
<?php $component = $__componentOriginal970eea507e21d10a438cf9d4525d45dc; ?>
<?php unset($__componentOriginal970eea507e21d10a438cf9d4525d45dc); ?>
<?php endif; ?>
                                <?php elseif(str_contains($activity->description, 'order')): ?>
                                    <?php if (isset($component)) { $__componentOriginalc43f195f1b3468dd3dad13eb2914c8e1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc43f195f1b3468dd3dad13eb2914c8e1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.shopping-bag','data' => ['class' => 'size-5 '.e($iconClass).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.shopping-bag'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-5 '.e($iconClass).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc43f195f1b3468dd3dad13eb2914c8e1)): ?>
<?php $attributes = $__attributesOriginalc43f195f1b3468dd3dad13eb2914c8e1; ?>
<?php unset($__attributesOriginalc43f195f1b3468dd3dad13eb2914c8e1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc43f195f1b3468dd3dad13eb2914c8e1)): ?>
<?php $component = $__componentOriginalc43f195f1b3468dd3dad13eb2914c8e1; ?>
<?php unset($__componentOriginalc43f195f1b3468dd3dad13eb2914c8e1); ?>
<?php endif; ?>
                                <?php elseif(str_contains($activity->description, 'inventory')): ?>
                                    <?php if (isset($component)) { $__componentOriginal82067727c95f13dc4198f80e35cb9c11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal82067727c95f13dc4198f80e35cb9c11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.chart-bar','data' => ['class' => 'size-5 '.e($iconClass).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.chart-bar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-5 '.e($iconClass).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal82067727c95f13dc4198f80e35cb9c11)): ?>
<?php $attributes = $__attributesOriginal82067727c95f13dc4198f80e35cb9c11; ?>
<?php unset($__attributesOriginal82067727c95f13dc4198f80e35cb9c11); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal82067727c95f13dc4198f80e35cb9c11)): ?>
<?php $component = $__componentOriginal82067727c95f13dc4198f80e35cb9c11; ?>
<?php unset($__componentOriginal82067727c95f13dc4198f80e35cb9c11); ?>
<?php endif; ?>
                                <?php elseif(str_contains($activity->description, 'sap')): ?>
                                    <?php if (isset($component)) { $__componentOriginal18ce857dfc449fdd246010f7208cb6d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18ce857dfc449fdd246010f7208cb6d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.arrow-path','data' => ['class' => 'size-5 '.e($iconClass).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.arrow-path'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-5 '.e($iconClass).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18ce857dfc449fdd246010f7208cb6d5)): ?>
<?php $attributes = $__attributesOriginal18ce857dfc449fdd246010f7208cb6d5; ?>
<?php unset($__attributesOriginal18ce857dfc449fdd246010f7208cb6d5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18ce857dfc449fdd246010f7208cb6d5)): ?>
<?php $component = $__componentOriginal18ce857dfc449fdd246010f7208cb6d5; ?>
<?php unset($__componentOriginal18ce857dfc449fdd246010f7208cb6d5); ?>
<?php endif; ?>
                                <?php elseif(str_contains($activity->description, 'quote')): ?>
                                    <?php if (isset($component)) { $__componentOriginal74697c151ccb8418c53b50a995b31225 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal74697c151ccb8418c53b50a995b31225 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.document-text','data' => ['class' => 'size-5 '.e($iconClass).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.document-text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-5 '.e($iconClass).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal74697c151ccb8418c53b50a995b31225)): ?>
<?php $attributes = $__attributesOriginal74697c151ccb8418c53b50a995b31225; ?>
<?php unset($__attributesOriginal74697c151ccb8418c53b50a995b31225); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal74697c151ccb8418c53b50a995b31225)): ?>
<?php $component = $__componentOriginal74697c151ccb8418c53b50a995b31225; ?>
<?php unset($__componentOriginal74697c151ccb8418c53b50a995b31225); ?>
<?php endif; ?>
                                <?php elseif(str_contains($activity->description, 'user')): ?>
                                    <?php if (isset($component)) { $__componentOriginalcbe89caa4ae8c58f7efd0ed6343c35ff = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcbe89caa4ae8c58f7efd0ed6343c35ff = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.user','data' => ['class' => 'size-5 '.e($iconClass).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.user'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-5 '.e($iconClass).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcbe89caa4ae8c58f7efd0ed6343c35ff)): ?>
<?php $attributes = $__attributesOriginalcbe89caa4ae8c58f7efd0ed6343c35ff; ?>
<?php unset($__attributesOriginalcbe89caa4ae8c58f7efd0ed6343c35ff); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcbe89caa4ae8c58f7efd0ed6343c35ff)): ?>
<?php $component = $__componentOriginalcbe89caa4ae8c58f7efd0ed6343c35ff; ?>
<?php unset($__componentOriginalcbe89caa4ae8c58f7efd0ed6343c35ff); ?>
<?php endif; ?>
                                <?php elseif(str_contains($activity->description, 'webhook')): ?>
                                    <?php if (isset($component)) { $__componentOriginal2357204bbfb73ef228c684f3b7e8f9fa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2357204bbfb73ef228c684f3b7e8f9fa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.bell','data' => ['class' => 'size-5 '.e($iconClass).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.bell'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-5 '.e($iconClass).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2357204bbfb73ef228c684f3b7e8f9fa)): ?>
<?php $attributes = $__attributesOriginal2357204bbfb73ef228c684f3b7e8f9fa; ?>
<?php unset($__attributesOriginal2357204bbfb73ef228c684f3b7e8f9fa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2357204bbfb73ef228c684f3b7e8f9fa)): ?>
<?php $component = $__componentOriginal2357204bbfb73ef228c684f3b7e8f9fa; ?>
<?php unset($__componentOriginal2357204bbfb73ef228c684f3b7e8f9fa); ?>
<?php endif; ?>
                                <?php else: ?>
                                    <?php if (isset($component)) { $__componentOriginal1f8061448e375a811323d4736f7bf58b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1f8061448e375a811323d4736f7bf58b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.information-circle','data' => ['class' => 'size-5 '.e($iconClass).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.information-circle'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-5 '.e($iconClass).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1f8061448e375a811323d4736f7bf58b)): ?>
<?php $attributes = $__attributesOriginal1f8061448e375a811323d4736f7bf58b; ?>
<?php unset($__attributesOriginal1f8061448e375a811323d4736f7bf58b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1f8061448e375a811323d4736f7bf58b)): ?>
<?php $component = $__componentOriginal1f8061448e375a811323d4736f7bf58b; ?>
<?php unset($__componentOriginal1f8061448e375a811323d4736f7bf58b); ?>
<?php endif; ?>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1">
                                        <p class="text-xs font-medium <?php echo e($iconClass); ?>">
                                            <?php echo e(str_replace('_', ' ', ucwords($activity->description, '_'))); ?>

                                        </p>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activity->causer): ?>
                                            <p class="text-[10px] text-zinc-400 mt-0.5">
                                                by <?php echo e($activity->causer->name ?? 'System'); ?>

                                            </p>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activity->subject): ?>
                                            <p class="text-[10px] text-zinc-500 dark:text-zinc-500 mt-1">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activity->subject_type === 'App\Models\Order'): ?>
                                                    Order #<?php echo e($activity->subject->reference ?? 'N/A'); ?>

                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activity->properties->has('total')): ?>
                                                        • <?php echo e(format_currency($activity->properties->get('total'))); ?>

                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                <?php elseif($activity->subject_type === 'App\Models\Payment'): ?>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activity->properties->has('order_reference')): ?>
                                                        Order #<?php echo e($activity->properties->get('order_reference')); ?>

                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activity->properties->has('amount')): ?>
                                                        • <?php echo e(format_currency($activity->properties->get('amount'))); ?>

                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                <?php elseif($activity->subject_type === 'App\Models\Quote'): ?>
                                                    Quote #<?php echo e($activity->subject->reference ?? 'N/A'); ?>

                                                <?php elseif($activity->subject_type === 'App\Models\User'): ?>
                                                    <?php echo e($activity->subject->email ?? 'User'); ?>

                                                <?php else: ?>
                                                    <?php echo e(class_basename($activity->subject_type)); ?>

                                                    #<?php echo e($activity->subject_id); ?>

                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </p>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>

                                    <time class="text-[10px] text-zinc-400 whitespace-nowrap">
                                        <?php echo e($activity->created_at->diffForHumans()); ?>

                                    </time>
                                </div>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activity->properties->has('reason') || $activity->properties->has('error')): ?>
                                    <p class="text-[10px] text-red-600 dark:text-red-400 mt-1">
                                        <?php echo e($activity->properties->get('reason') ?? $activity->properties->get('error')); ?>

                                    </p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <div class="px-5 py-10 text-center text-zinc-400 text-sm">
                            No recent activity
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $attributes = $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $component = $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>

        
        <?php if (isset($component)) { $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::card.index','data' => ['class' => 'p-0 lg:col-span-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'p-0 lg:col-span-2']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <?php if (isset($component)) { $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::heading','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Recent orders <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $attributes = $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $component = $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::link','data' => ['href' => route('admin.orders.index'),'wire:navigate' => true,'class' => 'text-xs']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.orders.index')),'wire:navigate' => true,'class' => 'text-xs']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
View all
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477)): ?>
<?php $attributes = $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477; ?>
<?php unset($__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477)): ?>
<?php $component = $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477; ?>
<?php unset($__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477); ?>
<?php endif; ?>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <th
                                class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest whitespace-nowrap">
                                Reference</th>
                            <th
                                class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest whitespace-nowrap">
                                Customer</th>
                            <th
                                class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest whitespace-nowrap">
                                Items</th>
                            <th
                                class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest whitespace-nowrap">
                                Amount</th>
                            <th
                                class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest whitespace-nowrap">
                                Date</th>
                            <th
                                class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest whitespace-nowrap">
                                Payment</th>
                            <th
                                class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest whitespace-nowrap">
                                Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $this->recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                                <td class="px-5 py-3">
                                    <a href="<?php echo e(route('admin.orders.show', $order)); ?>" wire:navigate
                                        class="text-blue-600 dark:text-blue-400 font-medium text-xs hover:underline"><?php echo e($order->reference); ?></a>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="w-7 h-7 rounded-full bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center text-[10px] font-semibold text-zinc-500 shrink-0">
                                            <?php echo e(strtoupper(substr($order->user?->name ?? '?', 0, 2))); ?>

                                        </div>
                                        <span
                                            class="text-xs text-zinc-800 dark:text-zinc-200"><?php echo e($order->user?->name ?? '—'); ?></span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-xs text-zinc-500"><?php echo e($order->items_count); ?></td>
                                <td class="px-5 py-3 text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                    <?php echo e(format_currency($order->total)); ?></td>
                                <td class="px-5 py-3 text-xs text-zinc-400 whitespace-nowrap">
                                    <?php echo e($order->created_at->diffForHumans()); ?></td>
                                <td class="px-5 py-3">
                                    <?php
                                        $pStatus = $order->payment_status->value;
                                        $pColor = match ($pStatus) {
                                            'paid'
                                                => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400',
                                            'failed'
                                                => 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400',
                                            'processing'
                                                => 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400',
                                            default => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                                        };
                                    ?>
                                    <span
                                        class="text-[10px] font-semibold px-2 py-0.5 rounded-full <?php echo e($pColor); ?>"><?php echo e(ucfirst($pStatus)); ?></span>
                                </td>
                                <td class="px-5 py-3">
                                    <?php if (isset($component)) { $__componentOriginal4cc377eda9b63b796b6668ee7832d023 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4cc377eda9b63b796b6668ee7832d023 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::badge.index','data' => ['size' => 'sm','color' => $order->status->color()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'sm','color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($order->status->color())]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                        <?php echo e($order->status->label()); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4cc377eda9b63b796b6668ee7832d023)): ?>
<?php $attributes = $__attributesOriginal4cc377eda9b63b796b6668ee7832d023; ?>
<?php unset($__attributesOriginal4cc377eda9b63b796b6668ee7832d023); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4cc377eda9b63b796b6668ee7832d023)): ?>
<?php $component = $__componentOriginal4cc377eda9b63b796b6668ee7832d023; ?>
<?php unset($__componentOriginal4cc377eda9b63b796b6668ee7832d023); ?>
<?php endif; ?>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-zinc-400 text-sm">No
                                    orders yet</td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $attributes = $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $component = $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>

    </div>

    
    
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        
        <?php if (isset($component)) { $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::card.index','data' => ['class' => 'p-0 lg:col-span-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'p-0 lg:col-span-2']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <?php if (isset($component)) { $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::heading','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Stock report <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $attributes = $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $component = $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::link','data' => ['href' => route('admin.catalog.products.index'),'wire:navigate' => true,'class' => 'text-xs']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.catalog.products.index')),'wire:navigate' => true,'class' => 'text-xs']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Manage stock
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477)): ?>
<?php $attributes = $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477; ?>
<?php unset($__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477)): ?>
<?php $component = $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477; ?>
<?php unset($__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477); ?>
<?php endif; ?>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <th
                                class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">
                                Product</th>
                            <th
                                class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">
                                SKU</th>
                            <th
                                class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">
                                Price</th>
                            <th
                                class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">
                                Stock status</th>
                            <th
                                class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">
                                Qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $this->stockReport; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                                <td class="px-5 py-3">
                                    <a href="<?php echo e(route('admin.catalog.products.edit', $product)); ?>" wire:navigate
                                        class="text-xs font-medium text-zinc-800 dark:text-zinc-200 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                        <?php echo e(Str::limit($product->name, 35)); ?>

                                    </a>
                                </td>
                                <td class="px-5 py-3 text-xs text-zinc-400 font-mono">
                                    <?php echo e($product->sku ?? '—'); ?></td>
                                <td class="px-5 py-3 text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                    <?php echo e(format_currency($product->final_price ?? 0)); ?></td>
                                <td class="px-5 py-3">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->stock_quantity === 0): ?>
                                        <span
                                            class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400">Out
                                            of stock</span>
                                    <?php elseif($product->stock_quantity <= $product->low_stock_threshold): ?>
                                        <span
                                            class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400">Low
                                            stock</span>
                                    <?php else: ?>
                                        <span
                                            class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400">In
                                            stock</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="px-5 py-3 text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                    <?php echo e($product->stock_quantity); ?></td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-zinc-400 text-sm">No
                                    products
                                    with stock management enabled</td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $attributes = $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $component = $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>

        
        <?php if (isset($component)) { $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::card.index','data' => ['class' => 'p-0 flex flex-col']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'p-0 flex flex-col']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <?php if (isset($component)) { $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::heading','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Customer satisfaction <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $attributes = $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $component = $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::link','data' => ['href' => route('admin.coming-soon', ['feature' => 'Customer Satisfaction Report', 'description' =>
                        'Comprehensive customer satisfaction metrics and trends over time.'
                    ]),'wire:navigate' => true,'class' => 'text-xs']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.coming-soon', ['feature' => 'Customer Satisfaction Report', 'description' =>
                        'Comprehensive customer satisfaction metrics and trends over time.'
                    ])),'wire:navigate' => true,'class' => 'text-xs']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Report <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477)): ?>
<?php $attributes = $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477; ?>
<?php unset($__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477)): ?>
<?php $component = $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477; ?>
<?php unset($__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477); ?>
<?php endif; ?>
            </div>

            <div class="p-4 flex flex-col flex-1">
                
                <div id="satisfactionChartData"
                    data-this-series="<?php echo e(json_encode($this->satisfactionStats['this_series'])); ?>"
                    data-last-series="<?php echo e(json_encode($this->satisfactionStats['last_series'])); ?>"
                    data-days="<?php echo e($this->satisfactionStats['days_this_month']); ?>">
                </div>
                
                <div class="flex-1">
                    <div wire:ignore style="position:relative; height:100%; width:100%;">
                        <canvas id="satisfactionChart"></canvas>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 mt-4">
                    <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800/60 p-3">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-1">
                            <?php echo e(format_currency($this->satisfactionStats['this_month'])); ?>

                        </p>
                        <p class="flex items-center gap-1.5 text-[10px] text-zinc-400">
                            <?php if (isset($component)) { $__componentOriginal18ce857dfc449fdd246010f7208cb6d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18ce857dfc449fdd246010f7208cb6d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.arrow-path','data' => ['class' => 'size-3 text-blue-500 shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.arrow-path'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-3 text-blue-500 shrink-0']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18ce857dfc449fdd246010f7208cb6d5)): ?>
<?php $attributes = $__attributesOriginal18ce857dfc449fdd246010f7208cb6d5; ?>
<?php unset($__attributesOriginal18ce857dfc449fdd246010f7208cb6d5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18ce857dfc449fdd246010f7208cb6d5)): ?>
<?php $component = $__componentOriginal18ce857dfc449fdd246010f7208cb6d5; ?>
<?php unset($__componentOriginal18ce857dfc449fdd246010f7208cb6d5); ?>
<?php endif; ?>
                            <?php echo e($this->satisfactionStats['month_label']); ?>

                        </p>
                    </div>
                    <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800/60 p-3">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-1">
                            <?php echo e(format_currency($this->satisfactionStats['last_month'])); ?>

                        </p>
                        <p class="flex items-center gap-1.5 text-[10px] text-zinc-400">
                            <?php if (isset($component)) { $__componentOriginal18ce857dfc449fdd246010f7208cb6d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18ce857dfc449fdd246010f7208cb6d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.arrow-path','data' => ['class' => 'size-3 text-emerald-500 shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.arrow-path'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-3 text-emerald-500 shrink-0']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18ce857dfc449fdd246010f7208cb6d5)): ?>
<?php $attributes = $__attributesOriginal18ce857dfc449fdd246010f7208cb6d5; ?>
<?php unset($__attributesOriginal18ce857dfc449fdd246010f7208cb6d5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18ce857dfc449fdd246010f7208cb6d5)): ?>
<?php $component = $__componentOriginal18ce857dfc449fdd246010f7208cb6d5; ?>
<?php unset($__componentOriginal18ce857dfc449fdd246010f7208cb6d5); ?>
<?php endif; ?>
                            <?php echo e($this->satisfactionStats['last_month_label']); ?>

                        </p>
                    </div>
                </div>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $attributes = $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $component = $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>

    </div>

    
    
    
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        <?php if (isset($component)) { $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::card.index','data' => ['class' => 'p-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'p-0']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <?php if (isset($component)) { $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::heading','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Deliveries <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $attributes = $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $component = $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::link','data' => ['href' => route('admin.orders.index'),'wire:navigate' => true,'class' => 'text-xs']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.orders.index')),'wire:navigate' => true,'class' => 'text-xs']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
View all
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477)): ?>
<?php $attributes = $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477; ?>
<?php unset($__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477)): ?>
<?php $component = $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477; ?>
<?php unset($__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477); ?>
<?php endif; ?>
            </div>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $this->recentDeliveries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $delivery): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <a href="<?php echo e(route('admin.orders.show', $delivery)); ?>" wire:navigate
                        class="flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                        <div
                            class="w-8 h-8 rounded-lg bg-teal-50 dark:bg-teal-950/50 flex items-center justify-center shrink-0">
                            <?php if (isset($component)) { $__componentOriginal7a62c53a9a388e917a2ccf86cb1b44e8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7a62c53a9a388e917a2ccf86cb1b44e8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.truck','data' => ['class' => 'size-3.5 text-teal-600 dark:text-teal-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.truck'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-3.5 text-teal-600 dark:text-teal-400']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7a62c53a9a388e917a2ccf86cb1b44e8)): ?>
<?php $attributes = $__attributesOriginal7a62c53a9a388e917a2ccf86cb1b44e8; ?>
<?php unset($__attributesOriginal7a62c53a9a388e917a2ccf86cb1b44e8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7a62c53a9a388e917a2ccf86cb1b44e8)): ?>
<?php $component = $__componentOriginal7a62c53a9a388e917a2ccf86cb1b44e8; ?>
<?php unset($__componentOriginal7a62c53a9a388e917a2ccf86cb1b44e8); ?>
<?php endif; ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-zinc-800 dark:text-zinc-200 truncate">
                                <?php echo e($delivery->items->first()?->product?->name ?? $delivery->reference); ?>

                            </p>
                            <p class="text-[10px] text-zinc-400 truncate">by
                                <?php echo e($delivery->user?->name ?? '—'); ?></p>
                        </div>
                        <?php if (isset($component)) { $__componentOriginal4cc377eda9b63b796b6668ee7832d023 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4cc377eda9b63b796b6668ee7832d023 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::badge.index','data' => ['size' => 'sm','color' => $delivery->status->color()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'sm','color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($delivery->status->color())]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            <?php echo e($delivery->status->label()); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4cc377eda9b63b796b6668ee7832d023)): ?>
<?php $attributes = $__attributesOriginal4cc377eda9b63b796b6668ee7832d023; ?>
<?php unset($__attributesOriginal4cc377eda9b63b796b6668ee7832d023); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4cc377eda9b63b796b6668ee7832d023)): ?>
<?php $component = $__componentOriginal4cc377eda9b63b796b6668ee7832d023; ?>
<?php unset($__componentOriginal4cc377eda9b63b796b6668ee7832d023); ?>
<?php endif; ?>
                    </a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <div class="px-5 py-8 text-center text-zinc-400 text-xs">No deliveries yet</div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $attributes = $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $component = $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::card.index','data' => ['class' => 'p-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'p-0']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <?php if (isset($component)) { $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::heading','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Top categories <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $attributes = $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $component = $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::link','data' => ['href' => route('admin.coming-soon', ['feature' => 'Category Performance Report', 'description' =>
                        'Detailed breakdown of sales performance by product category.'
                    ]),'wire:navigate' => true,'class' => 'text-xs']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.coming-soon', ['feature' => 'Category Performance Report', 'description' =>
                        'Detailed breakdown of sales performance by product category.'
                    ])),'wire:navigate' => true,'class' => 'text-xs']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Report <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477)): ?>
<?php $attributes = $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477; ?>
<?php unset($__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477)): ?>
<?php $component = $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477; ?>
<?php unset($__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477); ?>
<?php endif; ?>
            </div>

            <div class="p-4 flex flex-col items-center gap-4">

                <?php
                    $cats = $this->categoryStats['categories'];
                    $catTotal = $this->categoryStats['total'];
                    $catColors = ['#3B82F6', '#F43F5E', '#10B981', '#8B5CF6']; // blue, rose, green, purple
                    $radii = [88, 74, 60, 46];
                    $strokeW = 10;
                ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($cats)): ?>

                    
                    <div style="position:relative; width:200px; height:116px;">
                        <svg viewBox="0 0 200 110" width="200" height="110" style="overflow:visible;">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $cats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <?php
                                    $r = $radii[$i] ?? max(32, 46 - ($i - 3) * 14);
                                    $color = $catColors[$i] ?? '#94A3B8';
                                    $pct = $cat['pct'] / 100; // 0.0–1.0
                                    // Circumference of a semicircle = π * r
                                    $circum = M_PI * $r;
                                    // Filled dash = pct * circumference, gap = remainder of full circle
                                    $filled = round($pct * $circum, 2);
                                    $total = round(2 * M_PI * $r, 2); // full circle circumference
                                    $gap = round($total - $filled, 2);
                                ?>

                                
                                <path
                                    d="M <?php echo e(100 - $r); ?>,100 A <?php echo e($r); ?>,<?php echo e($r); ?> 0 0,1 <?php echo e(100 + $r); ?>,100"
                                    fill="none" stroke="rgba(0,0,0,0.07)" stroke-width="<?php echo e($strokeW); ?>"
                                    stroke-linecap="round" class="dark:stroke-white/10" />

                                
                                
                                <path id="arc-<?php echo e($i); ?>"
                                    d="M <?php echo e(100 - $r); ?>,100 A <?php echo e($r); ?>,<?php echo e($r); ?> 0 0,1 <?php echo e(100 + $r); ?>,100"
                                    fill="none" stroke="<?php echo e($color); ?>" stroke-width="<?php echo e($strokeW); ?>"
                                    stroke-linecap="round" pathLength="100"
                                    stroke-dasharray="<?php echo e($cat['pct']); ?> 100" />
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                            
                            <text x="100" y="94" text-anchor="middle" font-size="11" font-weight="600"
                                fill="<?php echo e($catColors[0] ?? '#3B82F6'); ?>">Sales</text>
                            <text x="100" y="108" text-anchor="middle" font-size="14" font-weight="700"
                                style="fill:currentColor;"><?php echo e(number_format($catTotal)); ?></text>
                        </svg>
                    </div>

                    
                    <div class="w-full grid grid-cols-2 gap-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $cats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <div class="rounded-xl border border-zinc-100 dark:border-zinc-800 p-3 text-center">
                                <p class="text-base font-bold text-zinc-900 dark:text-zinc-100 mb-1">
                                    <?php echo e(number_format($cat['units'])); ?>

                                </p>
                                <p
                                    class="flex items-center justify-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    <span class="inline-block w-2.5 h-2.5 rounded-sm shrink-0"
                                        style="background:<?php echo e($catColors[$i] ?? '#94A3B8'); ?>;"></span>
                                    <?php echo e($cat['name']); ?>

                                </p>
                            </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="py-8 text-center text-zinc-400 text-xs">No sales data in this period</div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $attributes = $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $component = $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::card.index','data' => ['class' => 'p-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'p-0']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <?php if (isset($component)) { $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::heading','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
New customers <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $attributes = $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $component = $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::link','data' => ['href' => route('admin.customers.index'),'wire:navigate' => true,'class' => 'text-xs']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.customers.index')),'wire:navigate' => true,'class' => 'text-xs']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
View all
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477)): ?>
<?php $attributes = $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477; ?>
<?php unset($__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477)): ?>
<?php $component = $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477; ?>
<?php unset($__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477); ?>
<?php endif; ?>
            </div>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $this->recentCustomers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <a href="<?php echo e(route('admin.customers.show', $customer)); ?>" wire:navigate
                        class="flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                        <div
                            class="w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center text-[10px] font-semibold text-zinc-500 shrink-0">
                            <?php echo e(strtoupper(substr($customer->name, 0, 2))); ?>

                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-zinc-800 dark:text-zinc-200 truncate">
                                <?php echo e($customer->name); ?></p>
                            <p class="text-[10px] text-zinc-400">
                                <?php echo e($customer->created_at->diffForHumans()); ?></p>
                        </div>
                        <?php if (isset($component)) { $__componentOriginal31cb76c8d087d4f00797aeea7232b4c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal31cb76c8d087d4f00797aeea7232b4c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.chevron-right','data' => ['class' => 'size-3.5 text-zinc-300 shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.chevron-right'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-3.5 text-zinc-300 shrink-0']); ?>
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
                    </a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <div class="px-5 py-8 text-center text-zinc-400 text-xs">No customers yet</div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $attributes = $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $component = $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::card.index','data' => ['class' => 'p-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'p-0']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <?php if (isset($component)) { $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::heading','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Top products <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $attributes = $__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__attributesOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9)): ?>
<?php $component = $__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9; ?>
<?php unset($__componentOriginale0fd5b6a0986beffac17a0a103dfd7b9); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::link','data' => ['href' => route('admin.coming-soon', ['feature' => 'Product Performance Report', 'description' =>
                        'In-depth analysis of best-selling products and revenue trends.'
                    ]),'wire:navigate' => true,'class' => 'text-xs']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.coming-soon', ['feature' => 'Product Performance Report', 'description' =>
                        'In-depth analysis of best-selling products and revenue trends.'
                    ])),'wire:navigate' => true,'class' => 'text-xs']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Report <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477)): ?>
<?php $attributes = $__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477; ?>
<?php unset($__attributesOriginal54ddb5b70b37b1e1cf0f2f95e4c53477); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477)): ?>
<?php $component = $__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477; ?>
<?php unset($__componentOriginal54ddb5b70b37b1e1cf0f2f95e4c53477); ?>
<?php endif; ?>
            </div>
            <div class="p-4 flex flex-col gap-3">
                <?php $prodColors = ['#10B981', '#3B82F6', '#F59E0B', '#8B5CF6', '#F43F5E', '#06B6D4']; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $this->topProductsChartData['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs text-zinc-700 dark:text-zinc-300 truncate flex-1 min-w-0 pr-2"><?php echo e(Str::limit($item['name'], 22)); ?></span>
                            <span
                                class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 shrink-0"><?php echo e($item['pct']); ?>%</span>
                        </div>
                        <div class="h-1 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                            <div class="h-1 rounded-full transition-all duration-500"
                                style="width:<?php echo e($item['pct']); ?>%; background:<?php echo e($prodColors[$i] ?? '#94A3B8'); ?>;">
                            </div>
                        </div>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <div class="py-6 text-center text-zinc-400 text-xs">No sales data in this period</div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $attributes = $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $component = $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>

    </div>



</div>

    <?php
        $__assetKey = '3396320381-0';

        ob_start();
    ?>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <?php
        $__output = ob_get_clean();

        // If the asset has already been loaded anywhere during this request, skip it...
        if (in_array($__assetKey, \Livewire\Features\SupportScriptsAndAssets\SupportScriptsAndAssets::$alreadyRunAssetKeys)) {
            // Skip it...
        } else {
            \Livewire\Features\SupportScriptsAndAssets\SupportScriptsAndAssets::$alreadyRunAssetKeys[] = $__assetKey;

            // Check if we're in a Livewire component or not and store the asset accordingly...
            if (isset($this)) {
                \Livewire\store($this)->push('assets', $__output, $__assetKey);
            } else {
                \Livewire\Features\SupportScriptsAndAssets\SupportScriptsAndAssets::$nonLivewireAssets[$__assetKey] = $__output;
            }
        }
    ?>

    <?php
        $__scriptKey = '3396320381-1';
        ob_start();
    ?>
    <script>
        const chartInstances = {};
        const currencySymbol = '<?php echo e(get_currency_symbol()); ?>';
        const isDark = () => document.documentElement.classList.contains('dark');
        const gridColor = () => isDark() ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
        const textColor = () => isDark() ? '#71717a' : '#a1a1aa';

        function destroyChart(canvasId) {
            const key = canvasId;
            if (chartInstances[key]) {
                chartInstances[key].destroy();
                delete chartInstances[key];
            }
            // Strip all inline styles/attributes Chart.js wrote onto the canvas
            // so the next instantiation starts from a clean slate
            const el = document.getElementById(canvasId);
            if (el) {
                el.removeAttribute('style');
                el.removeAttribute('width');
                el.removeAttribute('height');
            }
        }

        // -----------------------------------------------------------------------
        //  Revenue — reads from #revenueChartData bridge, draws into fixed wrapper
        //  Supports two chart types: 'area' (line+fill) and 'bar'
        // -----------------------------------------------------------------------
        function initRevenueChart() {
            const bridge = document.getElementById('revenueChartData');
            const canvas = document.getElementById('revenueChart');
            if (!bridge || !canvas) return;

            destroyChart('revenueChart');

            const labels = JSON.parse(bridge.dataset.labels || '[]');
            const revenue = JSON.parse(bridge.dataset.revenue || '[]');
            const orders = JSON.parse(bridge.dataset.orders || '[]');
            const refunds = JSON.parse(bridge.dataset.refunds || '[]');
            const chartType = bridge.dataset.chartType || 'area';
            const isBar = chartType === 'bar';

            // Smart y-axis number formatter — no currency symbol, just clean numbers
            const fmtRevenue = v => {
                if (v === 0) return '0';
                if (v >= 1_000_000) return (v / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'M';
                if (v >= 1_000) return (v / 1_000).toFixed(1).replace(/\.0$/, '') + 'k';
                return v.toFixed(0);
            };

            const areaDatasets = [{
                    label: 'Earnings',
                    data: revenue,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16,185,129,0.08)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#10B981',
                    pointHoverBorderWidth: 2,
                    yAxisID: 'yRevenue',
                },
                {
                    label: 'Orders',
                    data: orders,
                    borderColor: '#8B5CF6',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#8B5CF6',
                    pointHoverBorderWidth: 2,
                    yAxisID: 'yCount',
                },
                {
                    label: 'Refunds',
                    data: refunds,
                    borderColor: '#F43F5E',
                    backgroundColor: 'rgba(244,63,94,0.06)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#F43F5E',
                    pointHoverBorderWidth: 2,
                    yAxisID: 'yCount',
                },
            ];

            const barDatasets = [{
                    label: 'Earnings',
                    data: revenue,
                    type: 'bar',
                    backgroundColor: isDark() ? 'rgba(16,185,129,0.65)' : 'rgba(16,185,129,0.80)',
                    borderColor: '#10B981',
                    borderWidth: 0,
                    borderRadius: 4,
                    borderSkipped: false,
                    yAxisID: 'yRevenue',
                    order: 2,
                },
                {
                    label: 'Orders',
                    data: orders,
                    type: 'line',
                    borderColor: '#8B5CF6',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#8B5CF6',
                    pointHoverBorderWidth: 2,
                    yAxisID: 'yCount',
                    order: 1,
                },
            ];

            const sharedOptions = {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        top: 8,
                        bottom: 0
                    },
                },
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: isDark() ? '#18181b' : '#ffffff',
                        borderColor: isDark() ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.08)',
                        borderWidth: 1,
                        titleColor: isDark() ? '#e4e4e7' : '#3f3f46',
                        bodyColor: isDark() ? '#a1a1aa' : '#71717a',
                        padding: 10,
                        boxPadding: 4,
                        usePointStyle: true,
                        callbacks: {
                            label: ctx => {
                                if (ctx.dataset.yAxisID === 'yRevenue') {
                                    return `  ${ctx.dataset.label}: ${currencySymbol} ${ctx.parsed.y.toLocaleString('en-KE', { minimumFractionDigits: 2 })}`;
                                }
                                return `  ${ctx.dataset.label}: ${ctx.parsed.y}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        display: true,
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            color: textColor(),
                            font: {
                                size: 11
                            },
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 12,
                        },
                    },
                    yRevenue: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        grid: {
                            color: gridColor(),
                            drawTicks: false,
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            color: textColor(),
                            font: {
                                size: 11
                            },
                            padding: 8,
                            callback: v => fmtRevenue(v),
                        },
                    },
                    yCount: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            color: textColor(),
                            font: {
                                size: 11
                            },
                            padding: 8,
                            stepSize: 1,
                            callback: v => Number.isInteger(v) ? v : '',
                        },
                    },
                },
            };

            chartInstances['revenueChart'] = new Chart(canvas, {
                type: isBar ? 'bar' : 'line',
                data: {
                    labels,
                    datasets: isBar ? barDatasets : areaDatasets,
                },
                options: sharedOptions,
            });
        }

        // -----------------------------------------------------------------------
        //  Switch Revenue Chart Type — client-side only, no server round-trip
        // -----------------------------------------------------------------------
        window.switchRevenueChartType = function(type) {
            const bridge = document.getElementById('revenueChartData');
            if (!bridge) return;

            bridge.dataset.chartType = type;
            initRevenueChart();
        };

        // -----------------------------------------------------------------------
        //  Satisfaction — reads from #satisfactionChartData bridge
        // -----------------------------------------------------------------------
        function initSatisfactionChart() {
            const bridge = document.getElementById('satisfactionChartData');
            const canvas = document.getElementById('satisfactionChart');
            if (!bridge || !canvas) return;

            destroyChart('satisfactionChart');

            const days = parseInt(bridge.dataset.days || '30');
            const labels = Array.from({
                length: days
            }, (_, i) => i + 1);

            chartInstances['satisfactionChart'] = new Chart(canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                            label: 'This month',
                            data: JSON.parse(bridge.dataset.thisSeries || '[]'),
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgba(59,130,246,0.10)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#3B82F6',
                            pointBorderWidth: 2,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: '#ffffff',
                            pointHoverBorderColor: '#3B82F6',
                            pointHoverBorderWidth: 2,
                        },
                        {
                            label: 'Last month',
                            data: JSON.parse(bridge.dataset.lastSeries || '[]'),
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16,185,129,0.10)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#10B981',
                            pointBorderWidth: 2,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: '#ffffff',
                            pointHoverBorderColor: '#10B981',
                            pointHoverBorderWidth: 2,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: isDark() ? '#18181b' : '#ffffff',
                            borderColor: isDark() ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.08)',
                            borderWidth: 1,
                            titleColor: isDark() ? '#e4e4e7' : '#3f3f46',
                            bodyColor: isDark() ? '#a1a1aa' : '#71717a',
                            padding: 10,
                            boxPadding: 4,
                            usePointStyle: true,
                            callbacks: {
                                title: ctx => `Day ${ctx[0].label}`,
                                label: ctx =>
                                    `  ${ctx.dataset.label}: ${currencySymbol} ${ctx.parsed.y.toLocaleString('en-KE', { minimumFractionDigits: 2 })}`,
                            },
                        },
                    },
                    scales: {
                        x: {
                            display: false
                        }, // ← no x-axis at all
                        y: {
                            display: false
                        }, // ← no y-axis at all
                    },
                },
            });
        }

        function initAllCharts() {
            initRevenueChart();
            initSatisfactionChart();
        }

        function waitForLibraries(callback) {
            if (typeof jQuery !== 'undefined' && typeof moment !== 'undefined' && typeof jQuery.fn.daterangepicker !==
                'undefined' && typeof Chart !== 'undefined') {
                callback();
            } else {
                setTimeout(() => waitForLibraries(callback), 100);
            }
        }

        function initDateRangePicker() {
            const el = $('.dashboard-date-range').first();
            if (!el.length || typeof $.fn.daterangepicker === 'undefined') return;

            // Destroy existing instance if any
            if (el.data('daterangepicker')) {
                el.data('daterangepicker').remove();
            }

            el.daterangepicker({
                startDate: moment($wire.dateFrom),
                endDate: moment($wire.dateTo),
                opens: 'left',
                showDropdowns: true,
                alwaysShowCalendars: false,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month')
                        .endOf('month')
                    ]
                },
                locale: {
                    format: 'MMM DD, YYYY',
                    separator: ' – '
                },
            }, function(start, end, label) {
                console.log('New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format(
                    'YYYY-MM-DD') + ' (predefined range: ' + label + ')');
                const preset = label === 'Custom Range' ? 'custom' : label.toLowerCase().replace(/\s+/g, '_');
                $wire.setDateRange(preset, start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
            });

            // Set initial display value
            updateDateRangeDisplay();
        }

        function updateDateRangeDisplay() {
            const el = $('.dashboard-date-range').first();
            if (!el.length || !el.data('daterangepicker')) return;

            const picker = el.data('daterangepicker');
            const start = moment($wire.dateFrom);
            const end = moment($wire.dateTo);

            picker.setStartDate(start);
            picker.setEndDate(end);
            el.val(start.format('MMM DD, YYYY') + ' – ' + end.format('MMM DD, YYYY'));
        }

        // Boot - wait for libraries to load before initializing
        waitForLibraries(() => {
            initAllCharts();
            initDateRangePicker();
        });

        // Livewire 4 — fires once per component after full DOM morph
        $wire.interceptMessage(({
            onSuccess
        }) => {
            onSuccess(({
                onMorph
            }) => {
                onMorph(async () => {
                    initAllCharts();
                    // Update datepicker display after DOM morph (don't reinitialize since wire:ignore preserves it)
                    waitForLibraries(() => {
                        updateDateRangeDisplay();
                    });
                });
            });
        });
    </script>
    <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?><?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\storage\framework/views/livewire/views/8f3179bd.blade.php ENDPATH**/ ?>