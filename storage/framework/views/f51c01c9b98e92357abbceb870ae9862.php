<?php
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\User;
?>

<div class="flex flex-col gap-5">
    
    <?php if (isset($component)) { $__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.settings-card','data' => ['title' => 'Profile','titleEm' => 'Photo']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.settings-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Profile','titleEm' => 'Photo']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('icon', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginalcbe89caa4ae8c58f7efd0ed6343c35ff = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcbe89caa4ae8c58f7efd0ed6343c35ff = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.user','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.user'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
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
         <?php $__env->endSlot(); ?>

        <div class="flex items-center gap-6 px-5 py-5">
            <label for="avatarInput"
                class="relative size-20 rounded-full  text-white font-sherif text-[26px] font-black flex items-center justify-center shrink-0 cursor-pointer hover:brightness-75 transition-all">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->avatar): ?>
                    <?php if (isset($component)) { $__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::avatar.index','data' => ['circle' => true,'class' => 'w-full h-full shrink-0','src' => ''.e($user->avatar).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['circle' => true,'class' => 'w-full h-full shrink-0','src' => ''.e($user->avatar).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690)): ?>
<?php $attributes = $__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690; ?>
<?php unset($__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690)): ?>
<?php $component = $__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690; ?>
<?php unset($__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690); ?>
<?php endif; ?>
                <?php else: ?>
                    <?php if (isset($component)) { $__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::avatar.index','data' => ['circle' => true,'class' => 'w-full h-full shrink-0','name' => ''.e($user->name).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['circle' => true,'class' => 'w-full h-full shrink-0','name' => ''.e($user->name).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690)): ?>
<?php $attributes = $__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690; ?>
<?php unset($__attributesOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690)): ?>
<?php $component = $__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690; ?>
<?php unset($__componentOriginal4dcb6e757bd07b9aa3bf7ee84cfc8690); ?>
<?php endif; ?>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <div
                    class="absolute bottom-0 right-0 w-6 h-6 rounded-full bg-primary flex items-center justify-center border-2 border-white">
                    <?php if (isset($component)) { $__componentOriginal736a3246944d2d8ec1919ce8cba6f0a6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal736a3246944d2d8ec1919ce8cba6f0a6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.pencil-square','data' => ['class' => 'w-2.75 h-2.75 text-white']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.pencil-square'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-2.75 h-2.75 text-white']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal736a3246944d2d8ec1919ce8cba6f0a6)): ?>
<?php $attributes = $__attributesOriginal736a3246944d2d8ec1919ce8cba6f0a6; ?>
<?php unset($__attributesOriginal736a3246944d2d8ec1919ce8cba6f0a6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal736a3246944d2d8ec1919ce8cba6f0a6)): ?>
<?php $component = $__componentOriginal736a3246944d2d8ec1919ce8cba6f0a6; ?>
<?php unset($__componentOriginal736a3246944d2d8ec1919ce8cba6f0a6); ?>
<?php endif; ?>
                </div>
            </label>

            <div class="flex-1">
                <div class="text-[16px] font-bold text-zinc-950 mb-0.5"><?php echo e(auth()->user()->name); ?></div>
                <div class="text-[12px] text-zinc-500 mb-2.5"><?php echo e(auth()->user()->email); ?></div>

                <div class="flex items-center gap-2">
                    <?php if (isset($component)) { $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button.index','data' => ['tag' => 'label','for' => 'avatarInput','variant' => 'customer-outline','size' => 'customer','icon' => 'arrow-up-tray']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tag' => 'label','for' => 'avatarInput','variant' => 'customer-outline','size' => 'customer','icon' => 'arrow-up-tray']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                        <span wire:loading.remove wire:target="avatar">Upload Photo</span>
                        <span wire:loading wire:target="avatar">Uploading...</span>
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580)): ?>
<?php $attributes = $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580; ?>
<?php unset($__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc04b147acd0e65cc1a77f86fb0e81580)): ?>
<?php $component = $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580; ?>
<?php unset($__componentOriginalc04b147acd0e65cc1a77f86fb0e81580); ?>
<?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->avatar): ?>
                        <?php if (isset($component)) { $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button.index','data' => ['type' => 'button','wire:click' => 'removeAvatar','variant' => 'customer-danger','size' => 'customer']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','wire:click' => 'removeAvatar','variant' => 'customer-danger','size' => 'customer']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            Remove
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580)): ?>
<?php $attributes = $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580; ?>
<?php unset($__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc04b147acd0e65cc1a77f86fb0e81580)): ?>
<?php $component = $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580; ?>
<?php unset($__componentOriginalc04b147acd0e65cc1a77f86fb0e81580); ?>
<?php endif; ?>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <input type="file" id="avatarInput" wire:model="avatar" accept="image/*" class="hidden">
                <div class="text-[11px] text-zinc-500 mt-2">JPG, PNG, GIF or WEBP. Max 2MB.</div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['avatar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="text-[11px] text-red-500 font-semibold mt-1 block"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb)): ?>
<?php $attributes = $__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb; ?>
<?php unset($__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb)): ?>
<?php $component = $__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb; ?>
<?php unset($__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb); ?>
<?php endif; ?>

    
    <?php if (isset($component)) { $__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.settings-card','data' => ['title' => 'Personal','titleEm' => 'Information']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.settings-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Personal','titleEm' => 'Information']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('icon', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginal736a3246944d2d8ec1919ce8cba6f0a6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal736a3246944d2d8ec1919ce8cba6f0a6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.pencil-square','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.pencil-square'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal736a3246944d2d8ec1919ce8cba6f0a6)): ?>
<?php $attributes = $__attributesOriginal736a3246944d2d8ec1919ce8cba6f0a6; ?>
<?php unset($__attributesOriginal736a3246944d2d8ec1919ce8cba6f0a6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal736a3246944d2d8ec1919ce8cba6f0a6)): ?>
<?php $component = $__componentOriginal736a3246944d2d8ec1919ce8cba6f0a6; ?>
<?php unset($__componentOriginal736a3246944d2d8ec1919ce8cba6f0a6); ?>
<?php endif; ?>
         <?php $__env->endSlot(); ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->hasUnverifiedEmail): ?>
            <div class="flex items-start gap-3 mx-5 mt-5 p-3 bg-amber-50 border border-amber-200 rounded-sm">
                <?php if (isset($component)) { $__componentOriginal7f0e8d69add49581695c1337b3f85fff = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7f0e8d69add49581695c1337b3f85fff = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.exclamation-triangle','data' => ['class' => 'size-4 shrink-0 mt-0.5 text-amber-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.exclamation-triangle'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4 shrink-0 mt-0.5 text-amber-500']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7f0e8d69add49581695c1337b3f85fff)): ?>
<?php $attributes = $__attributesOriginal7f0e8d69add49581695c1337b3f85fff; ?>
<?php unset($__attributesOriginal7f0e8d69add49581695c1337b3f85fff); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7f0e8d69add49581695c1337b3f85fff)): ?>
<?php $component = $__componentOriginal7f0e8d69add49581695c1337b3f85fff; ?>
<?php unset($__componentOriginal7f0e8d69add49581695c1337b3f85fff); ?>
<?php endif; ?>
                <div>
                    <div class="text-[13px] font-bold text-amber-900"><?php echo e(__('Email not verified')); ?></div>
                    <div class="text-[12px] text-amber-800">
                        <?php echo e(__('Your email address is not verified.')); ?>

                        <button type="button" wire:click="resendVerificationEmail"
                            class="underline font-bold hover:no-underline cursor-pointer"><?php echo e(__('Resend verification email')); ?></button>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status') === 'verification-link-sent'): ?>
                        <div class="text-[12px] text-green-700 font-bold mt-1">
                            <?php echo e(__('A new verification link has been sent.')); ?>

                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <form wire:submit="save" class="px-5 py-5 flex flex-col gap-3.5">
            <?php if (isset($component)) { $__componentOriginal071cba40201c8f65242f69b169ef9aaa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal071cba40201c8f65242f69b169ef9aaa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.form-field','data' => ['label' => ''.e(__('Full Name')).'','name' => 'name','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.form-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => ''.e(__('Full Name')).'','name' => 'name','required' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <input type="text" wire:model="name" class="customer-input" placeholder="John Doe" required>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal071cba40201c8f65242f69b169ef9aaa)): ?>
<?php $attributes = $__attributesOriginal071cba40201c8f65242f69b169ef9aaa; ?>
<?php unset($__attributesOriginal071cba40201c8f65242f69b169ef9aaa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal071cba40201c8f65242f69b169ef9aaa)): ?>
<?php $component = $__componentOriginal071cba40201c8f65242f69b169ef9aaa; ?>
<?php unset($__componentOriginal071cba40201c8f65242f69b169ef9aaa); ?>
<?php endif; ?>

            <?php if (isset($component)) { $__componentOriginal071cba40201c8f65242f69b169ef9aaa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal071cba40201c8f65242f69b169ef9aaa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.form-field','data' => ['label' => ''.e(__('Display Name')).'','name' => 'display_name','hint' => ''.e(__('How your name appears on reviews. Defaults to your full name when blank.')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.form-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => ''.e(__('Display Name')).'','name' => 'display_name','hint' => ''.e(__('How your name appears on reviews. Defaults to your full name when blank.')).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <input type="text" wire:model="display_name" class="customer-input" placeholder="Optional">
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal071cba40201c8f65242f69b169ef9aaa)): ?>
<?php $attributes = $__attributesOriginal071cba40201c8f65242f69b169ef9aaa; ?>
<?php unset($__attributesOriginal071cba40201c8f65242f69b169ef9aaa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal071cba40201c8f65242f69b169ef9aaa)): ?>
<?php $component = $__componentOriginal071cba40201c8f65242f69b169ef9aaa; ?>
<?php unset($__componentOriginal071cba40201c8f65242f69b169ef9aaa); ?>
<?php endif; ?>

            <?php if (isset($component)) { $__componentOriginal071cba40201c8f65242f69b169ef9aaa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal071cba40201c8f65242f69b169ef9aaa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.form-field','data' => ['label' => ''.e(__('Email Address')).'','name' => 'email','required' => true,'hint' => ''.e(__('A verification email will be sent if you change this.')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.form-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => ''.e(__('Email Address')).'','name' => 'email','required' => true,'hint' => ''.e(__('A verification email will be sent if you change this.')).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <input type="email" wire:model="email" class="customer-input" required>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal071cba40201c8f65242f69b169ef9aaa)): ?>
<?php $attributes = $__attributesOriginal071cba40201c8f65242f69b169ef9aaa; ?>
<?php unset($__attributesOriginal071cba40201c8f65242f69b169ef9aaa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal071cba40201c8f65242f69b169ef9aaa)): ?>
<?php $component = $__componentOriginal071cba40201c8f65242f69b169ef9aaa; ?>
<?php unset($__componentOriginal071cba40201c8f65242f69b169ef9aaa); ?>
<?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                <?php if (isset($component)) { $__componentOriginal071cba40201c8f65242f69b169ef9aaa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal071cba40201c8f65242f69b169ef9aaa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.form-field','data' => ['label' => ''.e(__('Phone Number')).'','name' => 'phone_number']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.form-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => ''.e(__('Phone Number')).'','name' => 'phone_number']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    <input type="tel" wire:model="phone_number" class="customer-input"
                        placeholder="+254 712 345 678">
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal071cba40201c8f65242f69b169ef9aaa)): ?>
<?php $attributes = $__attributesOriginal071cba40201c8f65242f69b169ef9aaa; ?>
<?php unset($__attributesOriginal071cba40201c8f65242f69b169ef9aaa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal071cba40201c8f65242f69b169ef9aaa)): ?>
<?php $component = $__componentOriginal071cba40201c8f65242f69b169ef9aaa; ?>
<?php unset($__componentOriginal071cba40201c8f65242f69b169ef9aaa); ?>
<?php endif; ?>

                <?php if (isset($component)) { $__componentOriginal071cba40201c8f65242f69b169ef9aaa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal071cba40201c8f65242f69b169ef9aaa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.form-field','data' => ['label' => ''.e(__('Date of Birth')).'','name' => 'date_of_birth']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.form-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => ''.e(__('Date of Birth')).'','name' => 'date_of_birth']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    <input type="date" wire:model="date_of_birth" class="customer-input"
                        max="<?php echo e(now()->format('Y-m-d')); ?>">
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal071cba40201c8f65242f69b169ef9aaa)): ?>
<?php $attributes = $__attributesOriginal071cba40201c8f65242f69b169ef9aaa; ?>
<?php unset($__attributesOriginal071cba40201c8f65242f69b169ef9aaa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal071cba40201c8f65242f69b169ef9aaa)): ?>
<?php $component = $__componentOriginal071cba40201c8f65242f69b169ef9aaa; ?>
<?php unset($__componentOriginal071cba40201c8f65242f69b169ef9aaa); ?>
<?php endif; ?>
            </div>

            <div class="flex items-center gap-2.5 mt-5 pt-4 border-t border-zinc-200">
                <?php if (isset($component)) { $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button.index','data' => ['type' => 'submit','variant' => 'customer-primary','size' => 'customer-lg']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'customer-primary','size' => 'customer-lg']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    <span wire:loading.remove wire:target="save"><?php echo e(__('Save Changes')); ?></span>
                    <span wire:loading wire:target="save"><?php echo e(__('Saving...')); ?></span>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580)): ?>
<?php $attributes = $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580; ?>
<?php unset($__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc04b147acd0e65cc1a77f86fb0e81580)): ?>
<?php $component = $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580; ?>
<?php unset($__componentOriginalc04b147acd0e65cc1a77f86fb0e81580); ?>
<?php endif; ?>
            </div>
        </form>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb)): ?>
<?php $attributes = $__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb; ?>
<?php unset($__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb)): ?>
<?php $component = $__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb; ?>
<?php unset($__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb); ?>
<?php endif; ?>

</div><?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\storage\framework/views/livewire/views/2a3924c0.blade.php ENDPATH**/ ?>