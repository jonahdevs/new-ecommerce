<?php

use App\Models\Showroom;
use App\Notifications\ContactEnquiryReceived;
use App\Settings\IntegrationSettings;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    Showroom::factory()->headquarters()->create(['city' => 'Nairobi', 'country' => 'Kenya']);
    Showroom::factory()->create(['city' => 'Mombasa', 'country' => 'Kenya', 'sort_order' => 1]);
});

it('renders the contact page with its sections', function () {
    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('Talk to an')
        ->assertSee('Send us a message')
        ->assertSee('Visit a Sheffield showroom');
});

it('renders the showroom map and honours the configured provider', function () {
    // Default provider is Leaflet — CartoDB tiles + the showroomMap component.
    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('showroomMap', false)
        ->assertSee('basemaps.cartocdn.com', false);

    // Switching the global setting to Google (with a key) still renders.
    app(IntegrationSettings::class)->fill([
        'map_provider' => 'google',
        'google_maps_api_key' => 'test-key',
    ])->save();

    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('showroomMap', false);
});

it('defaults the nearest-showroom selection to the HQ', function () {
    $hq = Showroom::query()->where('is_hq', true)->first();

    Livewire::test('pages::storefront.contact')
        ->assertSet('location', $hq->id);
});

it('prefills the inquiry type from the query string', function () {
    Livewire::withQueryParams(['inquiry' => 'Service & spares'])
        ->test('pages::storefront.contact')
        ->assertSet('inquiry', 'Service & spares');
});

it('emails the contact inbox on a valid submission', function () {
    Livewire::test('pages::storefront.contact')
        ->set('name', 'Jane Mwangi')
        ->set('business', 'Artcaffé Group')
        ->set('email', 'jane@example.com')
        ->set('message', 'Need a combi oven quote for a 200-cover kitchen.')
        ->set('consent', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('sent', true)
        ->assertSet('reference', fn ($ref) => str_starts_with($ref, 'SHF-'));

    Notification::assertSentOnDemand(
        ContactEnquiryReceived::class,
        fn ($notification) => $notification->enquiry['name'] === 'Jane Mwangi'
            && $notification->enquiry['email'] === 'jane@example.com'
    );
});

it('requires name, email, message and consent', function () {
    Livewire::test('pages::storefront.contact')
        ->call('submit')
        ->assertHasErrors(['name', 'email', 'message', 'consent']);

    Notification::assertNothingSent();
});

it('rejects an invalid email', function () {
    Livewire::test('pages::storefront.contact')
        ->set('name', 'Jane')
        ->set('email', 'not-an-email')
        ->set('message', 'Hi')
        ->set('consent', true)
        ->call('submit')
        ->assertHasErrors(['email']);
});

it('lets the visitor reset the form to send another', function () {
    Livewire::test('pages::storefront.contact')
        ->set('name', 'Jane Mwangi')
        ->set('email', 'jane@example.com')
        ->set('message', 'First message.')
        ->set('consent', true)
        ->call('submit')
        ->assertSet('sent', true)
        ->call('sendAnother')
        ->assertSet('sent', false)
        ->assertSet('message', '')
        ->assertSet('consent', false);
});
