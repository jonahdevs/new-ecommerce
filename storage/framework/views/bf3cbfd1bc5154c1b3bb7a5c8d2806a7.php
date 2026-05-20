

<div class="flex items-center justify-center min-h-[60vh] px-4 py-16">
    <div class="text-center max-w-lg w-full">

        
        <p class="font-serif font-black text-8xl leading-none <?php echo e($isAdmin ? 'text-secondary dark:text-secondary-hover' : 'text-primary'); ?>">
            <?php echo e($code); ?>

        </p>

        
        <div class="w-12 h-1 mx-auto my-5 <?php echo e($isAdmin ? 'bg-secondary dark:bg-secondary-hover' : 'bg-primary'); ?>"></div>

        
        <h1 class="font-serif font-extrabold uppercase tracking-tight text-xl text-zinc-950 dark:text-zinc-100 mb-3">
            <?php echo e($title); ?>

        </h1>

        
        <p class="text-[13px] text-zinc-500 dark:text-zinc-400 font-medium leading-relaxed mb-8">
            <?php echo e($message); ?>

        </p>

        
        <div class="flex flex-wrap items-center justify-center gap-3">

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($code === '419'): ?>
                
                <button onclick="window.location.reload()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-white text-[13px] font-extrabold uppercase tracking-wider transition-colors cursor-pointer <?php echo e($isAdmin ? 'bg-secondary hover:bg-secondary-hover' : 'bg-primary hover:bg-[#e03d00]'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    Refresh page
                </button>
            <?php elseif($code === '503'): ?>
                
                <a href="#"
                    class="inline-flex items-center gap-2 px-5 py-2.5 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-[13px] font-extrabold uppercase tracking-wider transition-colors">
                    Contact support
                </a>
            <?php else: ?>
                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isAdmin): ?>
                    <a href="<?php echo e(route('admin.dashboard')); ?>"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-secondary hover:bg-secondary-hover text-white text-[13px] font-extrabold uppercase tracking-wider transition-colors">
                        Back to dashboard
                    </a>
                <?php else: ?>
                    <a href="<?php echo e(url('/')); ?>"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-[#e03d00] text-white text-[13px] font-extrabold uppercase tracking-wider transition-colors">
                        Back to homepage
                    </a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <a href="#"
                    class="inline-flex items-center gap-2 px-5 py-2.5 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-[13px] font-extrabold uppercase tracking-wider transition-colors">
                    Contact support
                </a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        </div>

    </div>
</div>
<?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\resources\views/errors/_error_body.blade.php ENDPATH**/ ?>