<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;

beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
});

it('renders a label when provided', function () {
    $html = Blade::render('<x-customer.form-field label="Full Name" />');

    expect($html)->toContain('Full Name');
});

it('appends an asterisk when required', function () {
    $html = Blade::render('<x-customer.form-field label="Email" :required="true" />');

    expect($html)->toContain('Email *');
});

it('omits the label element when label is not provided', function () {
    $html = Blade::render('<x-customer.form-field><input type="text"></x-customer.form-field>');

    expect($html)->not->toContain('<label');
});

it('renders a hint when provided', function () {
    $html = Blade::render('<x-customer.form-field hint="Some helpful text" />');

    expect($html)->toContain('Some helpful text');
});

it('renders slot content as the control', function () {
    $html = Blade::render(
        '<x-customer.form-field><input type="text" class="customer-input" placeholder="Test"></x-customer.form-field>'
    );

    expect($html)->toContain('placeholder="Test"');
});

it('wraps in a flex row when prefix slot is provided', function () {
    $html = Blade::render(
        '<x-customer.form-field label="Phone"><x-slot:prefix>+254</x-slot:prefix><input type="text"></x-customer.form-field>'
    );

    expect($html)
        ->toContain('+254')
        ->toContain('class="flex"');
});

it('wraps in a flex row when append slot is provided', function () {
    $html = Blade::render(
        '<x-customer.form-field><x-slot:append><button>Search</button></x-slot:append><input type="text"></x-customer.form-field>'
    );

    expect($html)
        ->toContain('Search')
        ->toContain('class="flex"');
});

it('wraps in a relative div when suffix slot is provided', function () {
    $html = Blade::render(
        '<x-customer.form-field><x-slot:suffix><button>Eye</button></x-slot:suffix><input type="text"></x-customer.form-field>'
    );

    expect($html)
        ->toContain('class="relative"')
        ->toContain('Eye');
});

it('does not render error markup when name is not provided', function () {
    $html = Blade::render('<x-customer.form-field label="Search" />');

    expect($html)->not->toContain('text-red-500');
});

it('renders append slot after input in a flex row (logout password pattern)', function () {
    $html = Blade::render(
        '<x-customer.form-field name="logout_password"><x-slot:append><button type="submit">Sign Out</button></x-slot:append><input type="password" class="customer-input flex-1"></x-customer.form-field>'
    );

    expect($html)
        ->toContain('Sign Out')
        ->toContain('class="flex"')
        ->toContain('type="password"');
});

it('allows overriding the base input class for danger inputs', function () {
    $html = Blade::render(
        '<x-customer.form-field label="Password" name="delete_password"><input type="password" class="customer-input border-red-300"></x-customer.form-field>'
    );

    expect($html)
        ->toContain('border-red-300')
        ->toContain('type="password"');
});
