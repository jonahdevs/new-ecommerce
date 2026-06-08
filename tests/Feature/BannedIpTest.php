<?php

use App\Models\BannedIp;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

// ---------------------------------------------------------------------------
// Middleware
// ---------------------------------------------------------------------------

it('blocks requests from a permanently banned ip', function () {
    BannedIp::factory()->create(['ip_address' => '1.2.3.4']);

    $this->get(route('home'), ['REMOTE_ADDR' => '1.2.3.4'])
        ->assertForbidden();
});

it('allows requests from an unbanned ip', function () {
    $this->get(route('home'), ['REMOTE_ADDR' => '9.9.9.9'])
        ->assertOk();
});

it('allows requests once an ip ban has expired', function () {
    BannedIp::factory()->create([
        'ip_address' => '5.5.5.5',
        'expires_at' => now()->subMinute(),
    ]);

    $this->get(route('home'), ['REMOTE_ADDR' => '5.5.5.5'])
        ->assertOk();
});

it('blocks requests from an ip with a future expiry', function () {
    BannedIp::factory()->create([
        'ip_address' => '6.6.6.6',
        'expires_at' => now()->addDay(),
    ]);

    $this->get(route('home'), ['REMOTE_ADDR' => '6.6.6.6'])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Settings page
// ---------------------------------------------------------------------------

it('can ban an ip address from the settings page', function () {
    Livewire::test('pages::admin.settings.other')
        ->set('banIp', '10.0.0.1')
        ->set('banComment', 'Spam')
        ->call('banIpAddress')
        ->assertHasNoErrors();

    expect(BannedIp::where('ip_address', '10.0.0.1')->exists())->toBeTrue();
});

it('validates that the ip address is valid', function () {
    Livewire::test('pages::admin.settings.other')
        ->set('banIp', 'not-an-ip')
        ->call('banIpAddress')
        ->assertHasErrors(['banIp' => 'ip']);
});

it('prevents banning the same ip twice', function () {
    BannedIp::factory()->create(['ip_address' => '10.0.0.2']);

    Livewire::test('pages::admin.settings.other')
        ->set('banIp', '10.0.0.2')
        ->call('banIpAddress')
        ->assertHasErrors(['banIp' => 'unique']);
});

it('can unban an ip address', function () {
    $ban = BannedIp::factory()->create(['ip_address' => '10.0.0.3']);

    Livewire::test('pages::admin.settings.other')
        ->call('unbanIpAddress', $ban->id)
        ->assertHasNoErrors();

    expect(BannedIp::find($ban->id))->toBeNull();
});
