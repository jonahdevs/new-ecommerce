<?php

use App\Models\Page;
use App\Models\User;
use Livewire\Livewire;

it('does not shadow the login route', function () {
    // The root-level /{page:slug} route must not intercept explicit routes.
    $this->get('/login')->assertOk();
});

it('renders a published page at its slug', function () {
    Page::factory()->create([
        'slug' => 'privacy-policy',
        'title' => 'Privacy Policy',
        'body' => 'We respect your privacy.',
        'is_published' => true,
    ]);

    $this->get('/privacy-policy')
        ->assertOk()
        ->assertSee('Privacy Policy')
        ->assertSee('We respect your privacy.');
});

it('404s on a draft page', function () {
    Page::factory()->draft()->create(['slug' => 'secret']);

    $this->get('/secret')->assertNotFound();
});

it('404s on an unknown slug', function () {
    $this->get('/no-such-page')->assertNotFound();
});

it('loads the admin pages index', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.pages.index'))->assertOk();
});

it('creates a page and auto-generates the slug from the title', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.pages.form')
        ->set('title', 'Warranty Information')
        ->set('body', 'Our warranty terms.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.pages.index'));

    $page = Page::firstWhere('slug', 'warranty-information');

    expect($page)->not->toBeNull()
        ->and($page->title)->toBe('Warranty Information')
        ->and($page->is_published)->toBeTrue();
});

it('rejects a duplicate slug', function () {
    $this->actingAs(User::factory()->create());
    Page::factory()->create(['slug' => 'about-us']);

    Livewire::test('pages::admin.pages.form')
        ->set('title', 'About Us')
        ->set('slug', 'about-us')
        ->call('save')
        ->assertHasErrors('slug');
});

it('edits a page from its form and deletes it from the index', function () {
    $this->actingAs(User::factory()->create());
    $page = Page::factory()->create(['title' => 'Old', 'slug' => 'old']);

    Livewire::test('pages::admin.pages.form', ['page' => $page])
        ->set('title', 'New Title')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.pages.index'));
    expect($page->fresh()->title)->toBe('New Title');

    Livewire::test('pages::admin.pages.index')->call('delete', $page->id);
    expect(Page::find($page->id))->toBeNull();
});
