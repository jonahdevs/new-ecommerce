<div>
    <fieldset class="fieldset py-0">
        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($label): ?>
            <legend class="fieldset-legend mb-0.5">
                <?php echo e($label); ?>


                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($attributes->get('required')): ?>
                    <span class="text-error">*</span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </legend>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <div
            x-data="
                {
                    editor: null,
                    value: <?php if ((object) ($attributes->wire('model')) instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e($attributes->wire('model')->value()); ?>')<?php echo e($attributes->wire('model')->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e($attributes->wire('model')); ?>')<?php endif; ?>,
                    uploadUrl: '<?php echo e($uploadUrl); ?>?disk=<?php echo e($disk); ?>&folder=<?php echo e($folder); ?>&_token=<?php echo e(csrf_token()); ?>',
                    uploading: false,
                    init() {
                        this.initEditor()

                        // Handles a case where people try to change contents on the fly from Livewire methods
                        this.$watch('value', (newValue) => {
                            if (newValue !== this.editor.value()) {
                                this.value = newValue || ''
                                this.destroyEditor()
                                this.initEditor()
                            }
                        })
                    },
                    destroyEditor() {
                        this.editor.toTextArea();
                        this.editor = null
                    },
                    initEditor() {
                        this.editor = new EasyMDE({
                                <?php echo e($setup()); ?>,
                                element: $refs.markdown<?php echo e($uuid); ?>,
                                initialValue: this.value ?? '',
                                imageUploadFunction: (file, onSuccess, onError) => {
                                    if (file.type.split('/')[0] !== 'image') {
                                        return onError('File must be an image.');
                                    }

                                    var data = new FormData()
                                    data.append('file', file)

                                    this.uploading = true

                                    fetch(this.uploadUrl, { method: 'POST', body: data })
                                       .then(response => response.json())
                                       .then(data => onSuccess(data.location))
                                       .catch((err) => onError('Error uploading image!'))
                                       .finally(() => this.uploading = false)
                                }
                            })

                        this.editor.codemirror.on('change', () => this.value = this.editor.value())
                    }
                }"
            wire:ignore
            x-on:livewire:navigating.window="destroyEditor()"
        >
            <div class="relative disabled text-base" :class="uploading && 'pointer-events-none opacity-50'">
                <textarea id="<?php echo e($uuid); ?>" x-ref="markdown<?php echo e($uuid); ?>"></textarea>

                <div class="absolute top-1/2 start-1/2 !opacity-100 text-center hidden" :class="uploading && '!block'">
                    <div>Uploading</div>
                    <div class="loading loading-dots"></div>
                </div>
            </div>
        </div>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$omitError && $errors->has($errorFieldName())): ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $errors->get($errorFieldName()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = Arr::wrap($message); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                    <div class="<?php echo e($errorClass); ?>" x-class="text-error"><?php echo e($line); ?></div>
                    <?php if($firstErrorOnly) break; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <?php if($firstErrorOnly) break; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hint): ?>
            <div class="<?php echo e($hintClass); ?>" x-classes="fieldset-label"><?php echo e($hint); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </fieldset>
</div><?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\storage\framework\views/75075a44aed346e6868dd03b1952eb8b.blade.php ENDPATH**/ ?>