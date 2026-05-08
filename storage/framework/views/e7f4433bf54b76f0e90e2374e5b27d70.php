<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\{Layout, Title};
use Livewire\Component;
use Artesaos\SEOTools\Facades\SEOMeta;

new #[Layout('layouts.customer-settings'), Title('Privacy & Data')] class extends Component {
    /**
     * Per-flag privacy preferences. Persisted as JSON on users.privacy_preferences.
     *
     * @var array<string, bool>
     */
    public array $prefs = [];

    public string $delete_password = '';

    public bool $confirm_delete = false;

    public static function defaults(): array
    {
        return [
            'profile_public' => true,
            'search_indexable' => false,
            'activity_visible' => true,
        ];
    }

    public function mount(): void
    {
        SEOMeta::setRobots('noindex,nofollow');

        $stored = Auth::user()->privacy_preferences ?? [];
        $this->prefs = array_replace(self::defaults(), $stored);
    }

    public function togglePref(string $key): void
    {
        if (!array_key_exists($key, $this->prefs)) {
            return;
        }

        $this->prefs[$key] = !$this->prefs[$key];

        Auth::user()->update(['privacy_preferences' => $this->prefs]);

        $this->dispatch('toast', message: __('Preference updated'), type: 'success');
    }

    /**
     * Stream a JSON export of the user's personal data on the spot.
     * For larger users this should be queued + emailed; this synchronous version
     * is fine for demo / typical customer-sized data.
     */
    public function downloadData()
    {
        $user = Auth::user()->load(['addresses', 'orders.items', 'reviews', 'wishlistItems']);

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'user' => $user->only(['id', 'name', 'display_name', 'email', 'phone_number', 'date_of_birth', 'created_at', 'newsletter_subscribed']),
            'addresses' => $user->addresses->toArray(),
            'orders' => $user->orders->toArray(),
            'reviews' => $user->reviews->toArray(),
            'wishlist' => $user->wishlistItems->toArray(),
            'preferences' => [
                'notifications' => $user->notification_preferences,
                'privacy' => $user->privacy_preferences,
            ],
        ];

        $filename = "shopsmart-data-export-{$user->id}-" . now()->format('Ymd-His') . '.json';

        return response()->streamDownload(fn() => print json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), $filename, ['Content-Type' => 'application/json']);
    }

    public function deleteAccount()
    {
        $this->validate(
            [
                'delete_password' => ['required', 'string', 'current_password'],
                'confirm_delete' => ['accepted'],
            ],
            [
                'delete_password.current_password' => __('The password you entered is incorrect.'),
                'confirm_delete.accepted' => __('Please confirm that you want to delete your account.'),
            ],
        );

        $user = Auth::user();

        // Detach the avatar file from the public disk so it doesn't dangle.
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        Auth::logout();

        // SoftDeletes trait on the User model preserves orders/reviews
        // for record-keeping while removing the customer's access.
        $user->delete();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return $this->redirect(route('home'), navigate: false);
    }
}; ?>

<div class="flex flex-col gap-5">
    
    <?php if (isset($component)) { $__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.settings-card','data' => ['title' => 'Privacy','titleEm' => 'Settings']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.settings-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Privacy','titleEm' => 'Settings']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('icon', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginalf870514c33bb1b53395ba02235f60146 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf870514c33bb1b53395ba02235f60146 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.shield-check','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.shield-check'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf870514c33bb1b53395ba02235f60146)): ?>
<?php $attributes = $__attributesOriginalf870514c33bb1b53395ba02235f60146; ?>
<?php unset($__attributesOriginalf870514c33bb1b53395ba02235f60146); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf870514c33bb1b53395ba02235f60146)): ?>
<?php $component = $__componentOriginalf870514c33bb1b53395ba02235f60146; ?>
<?php unset($__componentOriginalf870514c33bb1b53395ba02235f60146); ?>
<?php endif; ?>
         <?php $__env->endSlot(); ?>

        <?php
            $rows = [
                [
                    'key' => 'profile_public',
                    'title' => 'Profile Visibility',
                    'description' => 'Control who can see your profile information and reviews',
                    'on' => 'Public',
                    'off' => 'Private',
                ],
                [
                    'key' => 'search_indexable',
                    'title' => 'Search Engine Indexing',
                    'description' => 'Allow search engines to index your public profile and reviews',
                    'on' => 'Indexable',
                    'off' => 'Hidden',
                ],
                [
                    'key' => 'activity_visible',
                    'title' => 'Activity Status',
                    'description' => 'Show when you\'re active on the platform',
                    'on' => 'Visible',
                    'off' => 'Hidden',
                ],
            ];
        ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <?php $on = $prefs[$row['key']] ?? false; ?>
            <?php if (isset($component)) { $__componentOriginalb2f93bb703dcfebf8daccb3ec58a8406 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb2f93bb703dcfebf8daccb3ec58a8406 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.privacy-row','data' => ['title' => $row['title'],'description' => $row['description'],'lastItem' => $i === count($rows) - 1]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.privacy-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($row['title']),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($row['description']),'lastItem' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($i === count($rows) - 1)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    'text-[10px] font-bold px-2 py-0.5 border tracking-wider uppercase',
                    'bg-green-100 text-green-700 border-green-200' => $on,
                    'bg-zinc-100 text-zinc-500 border-zinc-200' => !$on,
                ]); ?>"><?php echo e($on ? $row['on'] : $row['off']); ?></span>
                <button type="button" wire:click="togglePref('<?php echo e($row['key']); ?>')"
                    class="border-[1.5px] border-zinc-200 px-3 py-1 font-serif text-[11px] font-extrabold tracking-wider uppercase transition-all hover:border-zinc-950 hover:bg-zinc-950 hover:text-white cursor-pointer">
                    <?php echo e($on ? 'Disable' : 'Enable'); ?>

                </button>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb2f93bb703dcfebf8daccb3ec58a8406)): ?>
<?php $attributes = $__attributesOriginalb2f93bb703dcfebf8daccb3ec58a8406; ?>
<?php unset($__attributesOriginalb2f93bb703dcfebf8daccb3ec58a8406); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb2f93bb703dcfebf8daccb3ec58a8406)): ?>
<?php $component = $__componentOriginalb2f93bb703dcfebf8daccb3ec58a8406; ?>
<?php unset($__componentOriginalb2f93bb703dcfebf8daccb3ec58a8406); ?>
<?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.settings-card','data' => ['title' => 'Data','titleEm' => 'Management']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.settings-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Data','titleEm' => 'Management']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('icon', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginald5dcfefa1bd397da70b3e8652c3e12ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald5dcfefa1bd397da70b3e8652c3e12ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.cube','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.cube'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
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
         <?php $__env->endSlot(); ?>

        <?php if (isset($component)) { $__componentOriginalb2f93bb703dcfebf8daccb3ec58a8406 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb2f93bb703dcfebf8daccb3ec58a8406 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.privacy-row','data' => ['title' => 'Download Your Data','description' => 'Download a JSON export of your personal data, orders, addresses, reviews and preferences.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.privacy-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Download Your Data','description' => 'Download a JSON export of your personal data, orders, addresses, reviews and preferences.']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <button type="button" wire:click="downloadData"
                class="border-[1.5px] border-zinc-950 px-3.5 py-1.5 font-serif text-[11px] font-extrabold tracking-wider uppercase transition-all hover:bg-zinc-950 hover:text-white cursor-pointer">
                <span wire:loading.remove wire:target="downloadData">Download</span>
                <span wire:loading wire:target="downloadData">Preparing...</span>
            </button>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb2f93bb703dcfebf8daccb3ec58a8406)): ?>
<?php $attributes = $__attributesOriginalb2f93bb703dcfebf8daccb3ec58a8406; ?>
<?php unset($__attributesOriginalb2f93bb703dcfebf8daccb3ec58a8406); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb2f93bb703dcfebf8daccb3ec58a8406)): ?>
<?php $component = $__componentOriginalb2f93bb703dcfebf8daccb3ec58a8406; ?>
<?php unset($__componentOriginalb2f93bb703dcfebf8daccb3ec58a8406); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginalb2f93bb703dcfebf8daccb3ec58a8406 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb2f93bb703dcfebf8daccb3ec58a8406 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.privacy-row','data' => ['title' => 'Data Retention','description' => 'Your data is retained for 7 years after account closure as required by law','lastItem' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.privacy-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Data Retention','description' => 'Your data is retained for 7 years after account closure as required by law','lastItem' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <span
                class="text-[10px] font-bold px-2 py-0.5 bg-zinc-100 text-zinc-500 border border-zinc-200 tracking-wider uppercase">7
                Years</span>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb2f93bb703dcfebf8daccb3ec58a8406)): ?>
<?php $attributes = $__attributesOriginalb2f93bb703dcfebf8daccb3ec58a8406; ?>
<?php unset($__attributesOriginalb2f93bb703dcfebf8daccb3ec58a8406); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb2f93bb703dcfebf8daccb3ec58a8406)): ?>
<?php $component = $__componentOriginalb2f93bb703dcfebf8daccb3ec58a8406; ?>
<?php unset($__componentOriginalb2f93bb703dcfebf8daccb3ec58a8406); ?>
<?php endif; ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.settings-card','data' => ['title' => 'Delete','titleEm' => 'Account','danger' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.settings-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Delete','titleEm' => 'Account','danger' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('icon', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginal1f8061448e375a811323d4736f7bf58b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1f8061448e375a811323d4736f7bf58b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.information-circle','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.information-circle'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
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
         <?php $__env->endSlot(); ?>

        <div class="px-5 py-5">
            <p class="text-[13px] text-zinc-700 mb-4">
                <?php echo e(__('Deleting your account signs you out and removes your access. Orders and reviews are retained in line with the data-retention policy above for legal/accounting purposes, then anonymised.')); ?>

            </p>

            <form wire:submit="deleteAccount" class="space-y-3">
                <div>
                    <label
                        class="block text-[10px] font-bold tracking-[0.1em] uppercase text-zinc-500 mb-1.5"><?php echo e(__('Confirm your password')); ?></label>
                    <input type="password" wire:model="delete_password" placeholder="••••••••"
                        class="w-full max-w-md border-[1.5px] border-red-300 px-3 py-2.5 text-[13px] font-medium outline-none transition-all focus:border-red-500 focus:ring-[3px] focus:ring-red-500/8">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['delete_password'];
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

                <label class="flex items-start gap-2 text-[12px] text-zinc-700 cursor-pointer">
                    <input type="checkbox" wire:model="confirm_delete" class="mt-0.5 accent-red-500">
                    <span><?php echo e(__('I understand this action will sign me out and disable my account.')); ?></span>
                </label>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['confirm_delete'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="text-[11px] text-red-500 font-semibold block"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <button type="submit" wire:confirm="This will sign you out and disable your account. Are you sure?"
                    class="border-[1.5px] border-red-500 bg-red-500 text-white px-4 py-2 font-serif text-[12px] font-extrabold tracking-wider uppercase transition-all hover:bg-red-600 hover:border-red-600 cursor-pointer">
                    <span wire:loading.remove wire:target="deleteAccount">Delete My Account</span>
                    <span wire:loading wire:target="deleteAccount">Deleting...</span>
                </button>
            </form>
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
</div>
<?php /**PATH C:\Users\jonah\Herd\sheffield_ecommerce\resources\views\pages\customer\settings\privacy.blade.php ENDPATH**/ ?>