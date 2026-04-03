<?php

namespace App\View\Composers;

use App\Enums\CategorySection;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class FooterComposer
{
    public function compose(View $view): void
    {
        $view->with(
            'footerCategories',
            Cache::tags(['footer', 'categories'])->remember('footer:categories', 60 * 60 * 12, function () {
                return Category::inSection(CategorySection::FOOTER)->take(5)->get();
            })
        );
    }
}
