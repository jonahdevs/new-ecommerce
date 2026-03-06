<?php
// Extract directive's "with" parameter (overrides component properties)
$__islandScope = (function($name = null, $token = null, $lazy = false, $defer = false, $always = false, $skip = false, $with = []) {
    return $with;
})(name: 'products', defer: true);
if (!empty($__islandScope)) {
    extract($__islandScope, EXTR_OVERWRITE);
}

// Extract runtime "with" parameter if provided (overrides everything)
if (isset($__runtimeWith) && is_array($__runtimeWith) && !empty($__runtimeWith)) {
    extract($__runtimeWith, EXTR_OVERWRITE);
}
?>

        <?php if (isset($__placeholder)) { ob_start(); } if (isset($__placeholder)): ?>
            <div class="container mx-auto px-4">

                <section class="flex items-center justify-between py-4 ">
                    <h2 class="font-semibold text-xl text-zinc-800">You May Also Like</h2>
                </section>

                <div class=" grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 pb-5">
                    @for ($i = 0; $i < 12; $i++)
                        <x-product-card-placeholder />
                    @endfor
                </div>
            </div>
        <?php endif; if (isset($__placeholder)) { echo ob_get_clean(); return; } ?>

        @include('pages.home.products')
    