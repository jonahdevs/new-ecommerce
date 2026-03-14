<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title><?php echo e(isset($title) ? $title . ' | ' : ''); ?><?php echo e(config('app.name')); ?></title>



<link rel="icon" type="image/png" href="/favicon.png">
<link rel="canonical" href="<?php echo e(url()->current()); ?>">
<link rel="preconnect" href="https://fonts.bunny.net">




<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>


<link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
<script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>

<script src="https://cdn.tiny.cloud/1/c2ybu5wz30jxny8183sd8d7ne9vuxr5obaqz65xd06ohkqj8/tinymce/6/tinymce.min.js"
    referrerpolicy="origin"></script>


<script src="https://js.stripe.com/v3/"></script>


<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>

<?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
<?php echo app('flux')->fluxAppearance(); ?>

<?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\resources\views/partials/head.blade.php ENDPATH**/ ?>