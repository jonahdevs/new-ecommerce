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

it('serves a sitemap when enabled and 404s when disabled', function () {
    Page::factory()->create(['slug' => 'privacy-policy', 'is_published' => true]);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee(route('page.show', 'privacy-policy'), false);

    $seo = app(SeoSettings::class);
    $seo->generate_sitemap = false;
    $seo->save();

    $this->get('/sitemap.xml')->assertNotFound();
});

it('reflects the index setting in robots.txt', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Allow: /')
        ->assertSee('Sitemap:');

    $seo = app(SeoSettings::class);
    $seo->index_site = false;
    $seo->save();

    $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');
});
