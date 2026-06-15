<?php

use App\Models\Order;
use App\Models\User;

it('redirects staff away from the customer account area to the admin dashboard', function () {
    actingAsAdmin();

    $this->get(route('account.dashboard'))
        ->assertRedirect(route('admin.dashboard'));
});

it('redirects staff away from customer account settings', function () {
    actingAsAdmin();

    $this->get(route('profile.edit'))
        ->assertRedirect(route('admin.dashboard'));
});

it('lets a customer use their own account area', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('account.dashboard'))
        ->assertOk();
});

it('redirects staff away from checkout so admins cannot place orders', function () {
    actingAsAdmin();

    $this->get(route('checkout'))
        ->assertRedirect(route('admin.dashboard'));
});

it('redirects staff away from the payment page', function () {
    actingAsAdmin();

    $order = Order::factory()->create();

    $this->get(route('payment.page', $order))
        ->assertRedirect(route('admin.dashboard'));
});
