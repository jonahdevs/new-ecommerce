<?php $seoSettings = app('App\Settings\SeoSettings'); ?>
<?php $generalSettings = app('App\Settings\GeneralSettings'); ?>

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />


<?php echo SEO::generate(); ?>



<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!View::hasSection('seo')): ?>
    <title><?php echo e(isset($title) ? $title . ' | ' : ''); ?><?php echo e($generalSettings->store_name ?: config('app.name')); ?></title>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($generalSettings->store_favicon): ?>
    <link rel="icon" type="image/png" href="<?php echo e(asset('storage/' . $generalSettings->store_favicon)); ?>">
<?php else: ?>
    <link rel="icon" type="image/png" href="/favicon.png">
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($seoSettings->google_site_verification): ?>
    <meta name="google-site-verification" content="<?php echo e($seoSettings->google_site_verification); ?>" />
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


<link rel="preconnect" href="https://fonts.bunny.net">

<meta name="color-scheme" content="light only">


<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js" defer></script>

<?php echo $__env->yieldPushContent('head-scripts'); ?>

<?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
<?php echo app('flux')->fluxAppearance(); ?>

<?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\resources\views/partials/head.blade.php ENDPATH**/ ?>