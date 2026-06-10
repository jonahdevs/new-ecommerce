<?php

use App\Models\Subscriber;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('loads the subscribers admin index', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.subscribers.index'))
        ->assertOk();
});

it('lists subscribers in the table', function () {
    Subscriber::factory()->confirmed()->create(['email' => 'alice@example.com']);
    Subscriber::factory()->pending()->create(['email' => 'bob@example.com']);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.subscribers.index')
        ->assertSee('alice@example.com')
        ->assertSee('bob@example.com');
});

it('filters by confirmed status', function () {
    Subscriber::factory()->confirmed()->create(['email' => 'confirmed@example.com']);
    Subscriber::factory()->pending()->create(['email' => 'pending@example.com']);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.subscribers.index')
        ->set('filterStatus', 'confirmed')
        ->assertSee('confirmed@example.com')
        ->assertDontSee('pending@example.com');
});

it('filters by pending status', function () {
    Subscriber::factory()->confirmed()->create(['email' => 'confirmed@example.com']);
    Subscriber::factory()->pending()->create(['email' => 'pending@example.com']);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.subscribers.index')
        ->set('filterStatus', 'pending')
        ->assertSee('pending@example.com')
        ->assertDontSee('confirmed@example.com');
});

it('filters by unsubscribed status', function () {
    Subscriber::factory()->confirmed()->create(['email' => 'active@example.com']);
    Subscriber::factory()->unsubscribed()->create(['email' => 'gone@example.com']);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.subscribers.index')
        ->set('filterStatus', 'unsubscribed')
        ->assertSee('gone@example.com')
        ->assertDontSee('active@example.com');
});

it('filters by interest', function () {
    Subscriber::factory()->confirmed()->create(['email' => 'trade@example.com', 'interests' => ['trade-pricing']]);
    Subscriber::factory()->confirmed()->create(['email' => 'news@example.com', 'interests' => ['new-products']]);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.subscribers.index')
        ->set('filterInterest', 'trade-pricing')
        ->assertSee('trade@example.com')
        ->assertDontSee('news@example.com');
});

it('searches by email', function () {
    Subscriber::factory()->confirmed()->create(['email' => 'findme@example.com']);
    Subscriber::factory()->confirmed()->create(['email' => 'other@example.com']);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.subscribers.index')
        ->set('search', 'findme')
        ->assertSee('findme@example.com')
        ->assertDontSee('other@example.com');
});

it('shows correct stats counts', function () {
    Subscriber::factory()->confirmed()->count(3)->create();
    Subscriber::factory()->pending()->count(2)->create();
    Subscriber::factory()->unsubscribed()->count(1)->create();

    Livewire::actingAs($this->admin)
        ->test('pages::admin.subscribers.index')
        ->assertSet('stats', [
            'total' => 6,
            'confirmed' => 3,
            'pending' => 2,
            'unsubscribed' => 1,
        ]);
});

it('exports subscribers as xlsx', function () {
    Subscriber::factory()->confirmed()->create(['email' => 'export@example.com']);

    $this->actingAs($this->admin)
        ->get(route('admin.subscribers.export'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('exports subscribers as csv', function () {
    Subscriber::factory()->confirmed()->create(['email' => 'export@example.com']);

    $this->actingAs($this->admin)
        ->get(route('admin.subscribers.export', ['format' => 'csv']))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('redirects unauthenticated users away from subscribers admin', function () {
    $this->get(route('admin.subscribers.index'))->assertRedirect(route('login'));
});
