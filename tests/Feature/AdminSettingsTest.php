<?php

use App\Models\TaxClass;
use App\Models\User;
use App\Settings\BrandingSettings;
use App\Settings\BusinessSettings;
use App\Settings\CheckoutSettings;
use App\Settings\IntegrationSettings;
use App\Settings\InventorySettings;
use App\Settings\LocalizationSettings;
use App\Settings\MaintenanceSettings;
use App\Settings\PaymentSettings;
use App\Settings\SecuritySettings;
use App\Settings\SeoSettings;
use App\Settings\SocialSettings;
use App\Settings\TaxSettings;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

// ==================================================
// SETTINGS SHELL & GENERAL TAB
// ==================================================

test('admin can view each settings tab', function (string $route) {
    $this->actingAs($this->admin)
        ->get(route($route))
        ->assertOk();
})->with([
    'admin.settings.general',
    'admin.settings.website',
    'admin.settings.app',
    'admin.settings.financial',
    'admin.settings.system',
    'admin.settings.other',
]);

test('the settings index redirects to general', function () {
    $this->actingAs($this->admin)
        ->get('/admin/settings')
        ->assertRedirect('/admin/settings/general');
});

test('admin can save business info including branding', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.website')
        ->set('legal_name', 'Acme Trading Ltd')
        ->set('contact_email', 'test@example.com')
        ->set('store_name', 'Acme Store')
        ->call('saveBusiness')
        ->assertHasNoErrors();

    expect(app(BusinessSettings::class)->legal_name)->toBe('Acme Trading Ltd')
        ->and(app(BusinessSettings::class)->contact_email)->toBe('test@example.com')
        ->and(app(BrandingSettings::class)->store_name)->toBe('Acme Store');
});

test('business info validates required legal name', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.website')
        ->set('legal_name', '')
        ->call('saveBusiness')
        ->assertHasErrors(['legal_name' => 'required']);
});

test('admin can save localization', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.website')
        ->set('currency', 'USD')
        ->call('saveLocalization')
        ->assertHasNoErrors();

    expect(app(LocalizationSettings::class)->currency)->toBe('USD');
});

test('the general tab renders the embedded personal sections', function (string $section) {
    $this->actingAs($this->admin)
        ->get(route('admin.settings.general', ['section' => $section]))
        ->assertOk()
        ->assertSeeLivewire('pages::account.settings.'.$section);
})->with(['profile', 'security', 'appearance']);

test('the embedded profile section does not render the standalone settings nav', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.settings.general', ['section' => 'profile']))
        ->assertOk()
        ->assertDontSee('Manage your profile and account settings');
});

test('an embedded personal component hides the standalone settings chrome', function (string $component) {
    $this->actingAs($this->admin);

    Livewire::test($component, ['embedded' => true])
        ->assertDontSee('Manage your profile and account settings');
})->with([
    'pages::account.settings.profile',
    'pages::account.settings.security',
    'pages::account.settings.appearance',
]);

test('the standalone personal component still shows the settings chrome for admins', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::account.settings.appearance')
        ->assertSee('Manage your profile and account settings');
});

// ==================================================
// WEBSITE TAB
// ==================================================

test('admin can save seo settings', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.website')
        ->set('meta_title_pattern', '{page} | {site}')
        ->set('index_site', false)
        ->call('saveSeo')
        ->assertHasNoErrors();

    expect(app(SeoSettings::class)->meta_title_pattern)->toBe('{page} | {site}')
        ->and(app(SeoSettings::class)->index_site)->toBeFalse();
});

test('admin can save social links and the handle is normalized', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.website')
        ->set('twitter_handle', '@acme')
        ->set('facebook_url', 'https://facebook.com/acme')
        ->call('saveSocial')
        ->assertHasNoErrors();

    expect(app(SocialSettings::class)->twitter_handle)->toBe('acme')
        ->and(app(SocialSettings::class)->facebook_url)->toBe('https://facebook.com/acme');
});

test('social links reject an invalid url', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.website')
        ->set('facebook_url', 'not-a-url')
        ->call('saveSocial')
        ->assertHasErrors(['facebook_url']);
});

// ==================================================
// FINANCIAL TAB
// ==================================================

test('the tax section offers existing classes as the default', function () {
    TaxClass::create(['name' => 'Standard rated', 'slug' => 'standard-rated', 'rate' => 16, 'is_active' => true]);

    $this->actingAs($this->admin)
        ->get(route('admin.settings.financial', ['section' => 'tax']))
        ->assertOk()
        ->assertSee('Default tax class')
        ->assertSee('Standard rated');
});

test('admin can save payment settings', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.financial')
        ->set('mpesa_enabled', true)
        ->set('mpesa_shortcode', '174379')
        ->set('cash_on_delivery_enabled', true)
        ->call('savePayments')
        ->assertHasNoErrors();

    expect(app(PaymentSettings::class)->mpesa_shortcode)->toBe('174379')
        ->and(app(PaymentSettings::class)->cash_on_delivery_enabled)->toBeTrue();
});

test('admin can choose the default tax class', function () {
    $class = TaxClass::create(['name' => 'Standard rated', 'slug' => 'standard-rated', 'rate' => 16, 'is_active' => true]);

    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.financial')
        ->set('default_tax_class_id', $class->id)
        ->call('saveTax')
        ->assertHasNoErrors();

    expect(app(TaxSettings::class)->default_tax_class_id)->toBe($class->id);
});

test('the default tax class must reference an existing class', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.financial')
        ->set('default_tax_class_id', 99999)
        ->call('saveTax')
        ->assertHasErrors(['default_tax_class_id']);
});

// ==================================================
// APP TAB
// ==================================================

test('admin can save inventory settings', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.app')
        ->set('low_stock_threshold', 8)
        ->set('out_of_stock_behavior', 'hide')
        ->call('saveInventory')
        ->assertHasNoErrors();

    expect(app(InventorySettings::class)->low_stock_threshold)->toBe(8)
        ->and(app(InventorySettings::class)->out_of_stock_behavior)->toBe('hide');
});

test('admin can save checkout settings', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.app')
        ->set('min_order_value', 500)
        ->set('order_prefix', 'ORD-')
        ->call('saveCheckout')
        ->assertHasNoErrors();

    expect(app(CheckoutSettings::class)->min_order_value)->toBe(500)
        ->and(app(CheckoutSettings::class)->order_prefix)->toBe('ORD-');
});

test('inventory rejects an invalid out-of-stock behavior', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.app')
        ->set('out_of_stock_behavior', 'bogus')
        ->call('saveInventory')
        ->assertHasErrors(['out_of_stock_behavior']);
});

// ==================================================
// SYSTEM TAB
// ==================================================

test('admin can save security settings', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.system')
        ->set('min_password_length', 12)
        ->set('require_two_factor', true)
        ->call('saveSecurity')
        ->assertHasNoErrors();

    expect(app(SecuritySettings::class)->min_password_length)->toBe(12)
        ->and(app(SecuritySettings::class)->require_two_factor)->toBeTrue();
});

test('admin can toggle maintenance mode', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.system')
        ->set('maintenance_mode', true)
        ->set('maintenance_message', 'Back soon.')
        ->call('saveMaintenance')
        ->assertHasNoErrors();

    expect(app(MaintenanceSettings::class)->maintenance_mode)->toBeTrue();
});

test('security rejects too short a minimum password length', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.system')
        ->set('min_password_length', 3)
        ->call('saveSecurity')
        ->assertHasErrors(['min_password_length']);
});

test('admin can toggle SAP sync and permissions', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.system')
        ->set('sap_enabled', true)
        ->set('sap_sync_price', false)
        ->set('sap_sync_quantity', true)
        ->call('saveSapConfig')
        ->assertHasNoErrors();

    $settings = app(IntegrationSettings::class);
    expect($settings->sap_enabled)->toBeTrue()
        ->and($settings->sap_sync_price)->toBeFalse()
        ->and($settings->sap_sync_quantity)->toBeTrue();
});

// ==================================================
// GENERAL TAB: PERSONAL NOTIFICATIONS
// ==================================================

test('admin can save personal notification preferences', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.general', ['section' => 'notifications'])
        ->set('notifications.new_order.email', false)
        ->set('notifications.low_stock.inapp', false)
        ->call('saveNotifications')
        ->assertHasNoErrors();

    $prefs = $this->admin->fresh()->staff_preferences;

    expect($prefs['notifications']['new_order']['email'])->toBeFalse()
        ->and($prefs['notifications']['low_stock']['inapp'])->toBeFalse();
});

// ==================================================
// OTHER TAB: MAINTENANCE (BACKUP & CACHE)
// ==================================================

test('admin can clear all caches', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.other')
        ->call('clearCache', 'all')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.settings.other', ['section' => 'cache']));
});

test('admin can trigger a database backup', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('backup:run', ['--only-db' => true])
        ->andReturn(0);

    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.other')
        ->set('backupTab', 'database')
        ->call('generateBackup')
        ->assertHasNoErrors();
});

// ==================================================
// STAFF MANAGEMENT
// ==================================================

test('admin can view staff page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.staff.index'))
        ->assertOk();
});

test('admin can invite a new staff member', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.staff.index')
        ->call('openCreate')
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password123')
        ->set('role', 'staff')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'jane@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('staff'))->toBeTrue();
});

test('invite validates unique email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($this->admin);

    Livewire::test('pages::admin.staff.index')
        ->call('openCreate')
        ->set('name', 'Test')
        ->set('email', 'taken@example.com')
        ->set('password', 'password123')
        ->call('save')
        ->assertHasErrors(['email' => 'unique']);
});

test('admin can edit a staff member role', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $this->actingAs($this->admin);

    Livewire::test('pages::admin.staff.index')
        ->call('openEdit', $staff->id)
        ->set('role', 'admin')
        ->call('save')
        ->assertHasNoErrors();

    expect($staff->fresh()->hasRole('admin'))->toBeTrue();
});

test('admin cannot remove themselves', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.staff.index')
        ->call('remove', $this->admin->id);

    expect($this->admin->fresh()->hasRole('admin'))->toBeTrue();
});

test('admin can revoke staff access', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $this->actingAs($this->admin);

    Livewire::test('pages::admin.staff.index')
        ->call('remove', $staff->id);

    expect($staff->fresh()->hasAnyRole(['admin', 'staff']))->toBeFalse();
});
