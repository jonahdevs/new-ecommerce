<?php

use App\Models\User;
use Livewire\Livewire;
use Spatie\Tags\Tag;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('loads the tags admin index', function () {
    $this->get(route('admin.tags.index'))->assertOk();
});

it('creates a tag with a type and generates a slug', function () {
    Livewire::test('pages::admin.tags.index')
        ->call('openCreate')
        ->set('name', 'Energy Efficient')
        ->set('type', 'feature')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $tag = Tag::findFromString('Energy Efficient', 'feature');

    expect($tag)->not->toBeNull()
        ->and($tag->type)->toBe('feature')
        ->and($tag->slug)->toBe('energy-efficient');
});

it('rejects a duplicate tag name within the same type', function () {
    Tag::findOrCreate('Premium', 'feature');

    Livewire::test('pages::admin.tags.index')
        ->call('openCreate')
        ->set('name', 'Premium')
        ->set('type', 'feature')
        ->call('save')
        ->assertHasErrors('name');

    expect(Tag::query()->where('type', 'feature')->count())->toBe(1);
});

it('updates a tag name and regenerates its slug', function () {
    $tag = Tag::findOrCreate('Old Name');

    Livewire::test('pages::admin.tags.index')
        ->call('openEdit', $tag->id)
        ->assertSet('name', 'Old Name')
        ->set('name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    $tag->refresh();

    expect($tag->name)->toBe('New Name')
        ->and($tag->slug)->toBe('new-name');
});

it('searches tags by name', function () {
    Tag::findOrCreate('Stainless Steel');
    Tag::findOrCreate('Cast Iron');

    Livewire::test('pages::admin.tags.index')
        ->set('search', 'stainless')
        ->assertSee('Stainless Steel')
        ->assertDontSee('Cast Iron');
});

it('deletes a tag', function () {
    $tag = Tag::findOrCreate('Disposable');

    Livewire::test('pages::admin.tags.index')
        ->call('delete', $tag->id);

    expect(Tag::find($tag->id))->toBeNull();
});

it('requires a name', function () {
    Livewire::test('pages::admin.tags.index')
        ->call('openCreate')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors('name');
});
