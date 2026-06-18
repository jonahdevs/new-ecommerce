<?php

use App\Models\User;
use App\Settings\SecuritySettings;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => PermissionSeeder::class]);
});

function setSessionLimit(int $limit): void
{
    app(SecuritySettings::class)->fill(['max_concurrent_sessions' => $limit])->save();
}

function insertSession(User $user, string $sessionId, int $lastActivity): void
{
    DB::table('sessions')->insert([
        'id' => $sessionId,
        'user_id' => $user->id,
        'ip_address' => '192.168.1.10',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0',
        'payload' => base64_encode(serialize([])),
        'last_activity' => $lastActivity,
    ]);
}

it('renders nothing for guests', function () {
    Livewire::test('concurrent-session-guard')
        ->assertDontSee('New device sign-in');
});

it('does not block when limit is 0 (unlimited)', function () {
    setSessionLimit(0);
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test('concurrent-session-guard');

    expect($component->get('isBlocked'))->toBeFalse();
});

it('does not block when session count is within limit', function () {
    setSessionLimit(2);
    $user = User::factory()->create();

    insertSession($user, 'other-session-1', now()->timestamp);

    $component = Livewire::actingAs($user)->test('concurrent-session-guard');

    expect($component->get('isBlocked'))->toBeFalse();
});

it('blocks when other sessions meet or exceed the limit', function () {
    setSessionLimit(1);
    $user = User::factory()->create();

    insertSession($user, 'other-session-abc', now()->timestamp);

    $component = Livewire::actingAs($user)->test('concurrent-session-guard');

    expect($component->get('isBlocked'))->toBeTrue();
    $component->assertSee('New device sign-in');
});

it('revokeAll removes all other sessions', function () {
    setSessionLimit(1);
    $user = User::factory()->create();
    insertSession($user, 'other-session-x', now()->timestamp);
    insertSession($user, 'other-session-y', now()->timestamp - 60);

    Livewire::actingAs($user)
        ->test('concurrent-session-guard')
        ->call('revokeAll');

    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
});

it('revoke removes only selected sessions', function () {
    setSessionLimit(1);
    $user = User::factory()->create();
    insertSession($user, 'other-session-keep', now()->timestamp);
    insertSession($user, 'other-session-kill', now()->timestamp - 60);

    Livewire::actingAs($user)
        ->test('concurrent-session-guard')
        ->set('selected', ['other-session-kill'])
        ->call('revoke');

    expect(DB::table('sessions')->where('id', 'other-session-keep')->exists())->toBeTrue()
        ->and(DB::table('sessions')->where('id', 'other-session-kill')->exists())->toBeFalse();
});

it('cannot revoke sessions belonging to another user', function () {
    setSessionLimit(1);
    $attacker = User::factory()->create();
    $victim = User::factory()->create();
    insertSession($victim, 'victim-session', now()->timestamp);

    Livewire::actingAs($attacker)
        ->test('concurrent-session-guard')
        ->set('selected', ['victim-session'])
        ->call('revoke');

    expect(DB::table('sessions')->where('id', 'victim-session')->exists())->toBeTrue();
});
