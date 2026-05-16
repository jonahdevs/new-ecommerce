<?php

namespace App\View\Composers;

use App\Enums\CategorySection;
use App\Models\Category;
use App\Settings\GeneralSettings;
use App\Settings\SocialSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class FooterComposer
{
    public function __construct(
        private readonly GeneralSettings $general,
        private readonly SocialSettings $social,
    ) {}

    public function compose(View $view): void
    {
        $view->with('footerCategories', Cache::tags(['footer', 'categories'])->remember('footer:categories', 60 * 60 * 12, function () {
            return Category::inSection(CategorySection::FOOTER)->take(5)->get(['id', 'name', 'slug']);
        }));

        $view->with('general', $this->general);
        $view->with('social', $this->social);
    }
}
