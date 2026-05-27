<?php

it('renders the storefront home page', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Shop by category');
    $response->assertSee('Featured equipment');
    $response->assertSee('New arrivals');
    $response->assertSee('Visit a Sheffield showroom');
    $response->assertSee('Brands we carry');
    $response->assertSee('The Sheffield Quarterly');
});

it('serves the hero banner images from public/images/banners', function () {
    foreach (['topline', 'coffee-machines', 'refrigeration', 'bakery-prep', 'clearance-sale', 'thin-banner'] as $name) {
        expect(file_exists(public_path("images/banners/{$name}.webp")))
            ->toBeTrue("Missing /images/banners/{$name}.webp");
    }
});
