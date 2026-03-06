<?php
// Extract directive's "with" parameter (overrides component properties)
$__islandScope = (function($name = null, $token = null, $lazy = false, $defer = false, $always = false, $skip = false, $with = []) {
    return $with;
})('top-categories');
if (!empty($__islandScope)) {
    extract($__islandScope, EXTR_OVERWRITE);
}

// Extract runtime "with" parameter if provided (overrides everything)
if (isset($__runtimeWith) && is_array($__runtimeWith) && !empty($__runtimeWith)) {
    extract($__runtimeWith, EXTR_OVERWRITE);
}
?>

        <?php if (isset($__placeholder)) { ob_start(); } if (isset($__placeholder)): ?>
            <div class="">
                <div class="py-4">
                    <!-- Responsive Heading -->
                    <h2 class="font-semibold text-xl text-zinc-800 ">
                        Top Categories
                    </h2>
                </div>
                <div
                    class="py-3 pb-5 grid grid-cols-2 xs:grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7 gap-3">
                    @for ($i = 0; $i < 14; $i++)
                        <div class="animate-pulse">
                            <div class="w-full aspect-4/3 bg-zinc-200 rounded-md"></div>
                            <div class="w-3/4 h-3 sm:h-4 mt-2 bg-zinc-200 mx-auto rounded"></div>
                        </div>
                    @endfor
                </div>
            </div>
        <?php endif; if (isset($__placeholder)) { echo ob_get_clean(); return; } ?>

        @include('pages.home.top-categories')
    