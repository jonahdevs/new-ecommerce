<?php

use App\Models\Page;
use App\Settings\AnalyticsSettings;
use App\Settings\BusinessSettings;
use App\Settings\LegalSettings;
use App\Settings\SeoSettings;
use App\Settings\SocialSettings;

it('renders configured social links and contact details in the footer', function () {
    $social = app(SocialSettings::class);
    $social->facebook_url = 'https://facebook.com/sheffield';
    $social->whatsapp_number = '+254 712 000 000';
    $social->save();

    $business = app(BusinessSettings::class);
    $business->contact_email = 'sales@sheffield.test';
    $business->save();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('https://facebook.com/sheffield')
        ->assertSee('wa.me/254712000000')
        ->assertSee('sales@sheffield.test');
});

it('injects the GA4 tag only when a measurement id is set', function () {
    $this->get(route('home'))->assertOk()->assertDontSee('googletagmanager.com/gtag');

    $analytics = app(AnalyticsSettings::class);
    $analytics->ga4_id = 'G-TEST12345';
    $analytics->save();

    $this->get(route('home'))->assertOk()->assertSee('G-TEST12345');
});

it('shows the cookie banner only when enabled', function () {
    $this->get(route('home'))->assertOk()->assertDontSee('cookie-consent');

    $legal = app(LegalSettings::class);
    $legal->cookie_consent_enabled = true;
    $legal->save();

    $this->get(route('home'))->assertOk()->assertSee('cookie-consent');
});

it('uses the default meta description as a fallback', function () {
    $seo = app(SeoSettings::class);
    $seo->default_meta_description = 'Commercial kitchen equipment across East Africa.';
    $seo->save();

    $page = Page::factory()->create(['is_published' => true, 'meta_description' => null]);

    $this->get(route('page.show', $page->slug))
        ->assertOk()
        ->assertSee('Commercial kitchen equipment across East Africa.', false);
});

it('writes published page URLs to the sitemap file when enabled', function () {
    // The sitemap is a static artefact: the sitemap:generate command (scheduled
    // daily) writes public/sitemap.xml, which the web server serves directly.
    $path = public_path('sitemap.xml');
    $original = file_exists($path) ? file_get_contents($path) : null;

    try {
        app(SeoSettings::class)->fill(['generate_sitemap' => true])->save();
        Page::factory()->create(['slug' => 'privacy-policy', 'is_published' => true]);

        $this->artisan('sitemap:generate')->assertSuccessful();

        expect(file_get_contents($path))->toContain(route('page.show', 'privacy-policy'));
    } finally {
        if ($original !== null) {
            file_put_contents($path, $original);
        } elseif (file_exists($path)) {
            unlink($path);
        }
    }
});

it('skips sitemap generation when disabled', function () {
    app(SeoSettings::class)->fill(['generate_sitemap' => false])->save();

    $this->artisan('sitemap:generate')
        ->expectsOutputToContain('disabled')
        ->assertSuccessful();
});

it('adds a site-wide noindex directive when indexing is turned off', function () {
    // index_site defaults to true → pages advertise as indexable.
    $this->get(route('home'))->assertOk()->assertSee('content="index,follow"', false);

    app(SeoSettings::class)->fill(['index_site' => false])->save();

    $this->get(route('home'))->assertOk()->assertSee('noindex, nofollow', false);
});
