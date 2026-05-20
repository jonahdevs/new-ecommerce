<?php

use App\Enums\EmailTemplateType;
use App\Models\EmailTemplate;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_staff' => true, 'email_verified_at' => now()]);

    Permission::firstOrCreate(['name' => 'manage.settings', 'guard_name' => 'web']);
    $this->admin->givePermissionTo('manage.settings');

    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $this->actingAs($this->admin);
});

// ─── Index page ──────────────────────────────────────────────────────────────

it('renders the email templates index page', function () {
    $this->get(route('admin.email-templates.index'))
        ->assertOk()
        ->assertSee('Email Templates')
        ->assertSee('Order Confirmation');
});

it('renders a preview iframe for customized templates on the index', function () {
    EmailTemplate::query()->create([
        'name' => EmailTemplateType::OrderConfirmed->label(),
        'type' => EmailTemplateType::OrderConfirmed->value,
        'subject' => EmailTemplateType::OrderConfirmed->label(),
        'body_html' => '<p>Hello there preview</p>',
        'is_active' => true,
    ]);

    $this->get(route('admin.email-templates.index'))
        ->assertOk()
        ->assertSee('srcdoc', escape: false)
        ->assertSee('Hello there preview', escape: false);
});

it('toggles active state via the index dropdown', function () {
    $template = EmailTemplate::query()->create([
        'name' => EmailTemplateType::OrderConfirmed->label(),
        'type' => EmailTemplateType::OrderConfirmed->value,
        'subject' => EmailTemplateType::OrderConfirmed->label(),
        'body_html' => '<p>body</p>',
        'is_active' => true,
    ]);

    Livewire::test('pages::admin.email-templates.index')
        ->call('toggleActive', EmailTemplateType::OrderConfirmed->value)
        ->assertHasNoErrors();

    expect($template->fresh()->is_active)->toBeFalse();

    Livewire::test('pages::admin.email-templates.index')
        ->call('toggleActive', EmailTemplateType::OrderConfirmed->value)
        ->assertHasNoErrors();

    expect($template->fresh()->is_active)->toBeTrue();
});

it('resets a customized template to default via the index dropdown', function () {
    EmailTemplate::query()->create([
        'name' => EmailTemplateType::PasswordReset->label(),
        'type' => EmailTemplateType::PasswordReset->value,
        'subject' => EmailTemplateType::PasswordReset->label(),
        'body_html' => '<p>custom</p>',
        'is_active' => true,
    ]);

    Livewire::test('pages::admin.email-templates.index')
        ->call('resetToDefault', EmailTemplateType::PasswordReset->value)
        ->assertHasNoErrors();

    expect(EmailTemplate::query()->byType(EmailTemplateType::PasswordReset)->exists())->toBeFalse();
});

// ─── Edit page ───────────────────────────────────────────────────────────────

it('renders the edit page for a valid template type', function () {
    $this->get(route('admin.email-templates.edit', EmailTemplateType::OrderConfirmed->value))
        ->assertOk()
        ->assertSee('Order Confirmation');
});

it('returns 404 for an unknown template type', function () {
    $this->get(route('admin.email-templates.edit', 'not_a_real_type'))
        ->assertNotFound();
});

it('saves a new email template body', function () {
    Livewire::test('pages::admin.email-templates.edit', ['type' => EmailTemplateType::QuoteSent->value])
        ->set('bodyHtml', '<p>Hello {{customer_name}}</p>')
        ->set('bodyJson', '{"foo":"bar"}')
        ->call('save')
        ->assertHasNoErrors();

    $template = EmailTemplate::query()->byType(EmailTemplateType::QuoteSent)->first();

    expect($template)->not->toBeNull()
        ->and($template->body_html)->toBe('<p>Hello {{customer_name}}</p>')
        ->and($template->body_json)->toBe('{"foo":"bar"}')
        ->and($template->is_active)->toBeTrue()
        ->and($template->name)->toBe(EmailTemplateType::QuoteSent->label())
        ->and($template->subject)->toBe(EmailTemplateType::QuoteSent->label());
});

it('updates an existing template body', function () {
    EmailTemplate::query()->create([
        'name' => EmailTemplateType::OrderConfirmed->label(),
        'type' => EmailTemplateType::OrderConfirmed->value,
        'subject' => EmailTemplateType::OrderConfirmed->label(),
        'body_html' => '<p>old body</p>',
        'is_active' => false,
    ]);

    Livewire::test('pages::admin.email-templates.edit', ['type' => EmailTemplateType::OrderConfirmed->value])
        ->assertSet('bodyHtml', '<p>old body</p>')
        ->assertSet('isActive', false)
        ->set('bodyHtml', '<p>new body</p>')
        ->call('save')
        ->assertHasNoErrors();

    $template = EmailTemplate::query()->byType(EmailTemplateType::OrderConfirmed)->first();

    expect($template->body_html)->toBe('<p>new body</p>')
        ->and($template->is_active)->toBeFalse();
});
