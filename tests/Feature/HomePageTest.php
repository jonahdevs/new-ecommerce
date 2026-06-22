<?php

it('renders the storefront home page', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Shop by category');
    $response->assertSee('Featured equipment');
    $response->assertSee('Just In');
    $response->assertSee('professionals trust');
    $response->assertSee('The Sheffield Quarterly');
});

it('renders the responsive header chrome', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    // Mobile menu trigger + slide-over drawer
    $response->assertSee('aria-label="Open menu"', false);
    $response->assertSee('aria-modal="true"', false);
    $response->assertSee('drawerOpen', false);
    // Primary nav links rendered (desktop bar + drawer)
    $response->assertSee('Request quote');
    $response->assertSee('Contact');
});

it('wires each hero slide to a working destination', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('href="'.route('catalog').'"', false);
    $response->assertSee('href="'.route('category.show', 'coffee-machines').'"', false);
    $response->assertSee('href="'.route('category.show', 'refrigeration').'"', false);
    $response->assertSee('href="'.route('category.show', 'bakery-preparation').'"', false);
    $response->assertSee('href="'.e(route('catalog', ['tag' => 'On Sale'])).'"', false);
});

it('serves the hero banner images from public/images/banners', function () {
    foreach (['topline', 'coffee-machines', 'refrigeration', 'bakery-prep', 'clearance-sale', 'thin-banner'] as $name) {
        expect(file_exists(public_path("images/banners/{$name}.webp")))
            ->toBeTrue("Missing /images/banners/{$name}.webp");
    }
});
