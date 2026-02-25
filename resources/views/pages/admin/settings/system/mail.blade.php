<?php

use App\Settings\MailSettings;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Mail Settings')] class extends Component {
    public string $driver = 'smtp';
    public ?string $host = null;
    public int $port = 587;
    public ?string $username = null;
    public ?string $password = null;
    public string $encryption = 'tls';
    public ?string $from_address = null;
    public ?string $from_name = '';

    // Test email
    public string $test_email = '';
    public bool $sending_test = false;

    public function mount(MailSettings $settings): void
    {
        $this->driver = $settings->driver;
        $this->host = $settings->host;
        $this->port = $settings->port;
        $this->username = $settings->username;
        $this->password = $settings->password;
        $this->encryption = $settings->encryption;
        $this->from_address = $settings->from_address;
        $this->from_name = $settings->from_name;
    }

    public function rules(): array
    {
        return [
            'driver' => ['required', 'string', 'in:smtp,sendmail,mailgun,ses,postmark,log'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'in:25,465,587,2525'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'encryption' => ['required', 'string', 'in:tls,ssl,none'],
            'from_address' => ['required', 'email'],
            'from_name' => ['required', 'string', 'max:100'],
        ];
    }

    public function save(MailSettings $settings): void
    {
        $this->validate();

        try {
            $settings->driver = $this->driver;
            $settings->host = $this->host;
            $settings->port = $this->port;
            $settings->username = $this->username;
            $settings->password = '';
            $settings->encryption = $this->encryption;
            $settings->from_address = $this->from_address;
            $settings->from_name = $this->from_name;
            $settings->save();

            // Apply to running config immediately
            $this->applyMailConfig();

            $this->dispatch('notify', variant: 'success', message: 'Mail settings saved.');
        } catch (\Throwable $e) {
            logger()->error('Failed to save mail settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    public function sendTestEmail(): void
    {
        $this->validateOnly('test_email', [
            'test_email' => ['required', 'email'],
        ]);

        $this->sending_test = true;

        try {
            $this->applyMailConfig();

            Mail::raw('This is a test email from your Sheffield Africa store.', function ($message) {
                $message->to($this->test_email)->subject('Test Email — Sheffield Africa');
            });

            $this->dispatch('notify', variant: 'success', message: "Test email sent to {$this->test_email}.");
            $this->test_email = '';
        } catch (\Throwable $e) {
            logger()->error('Failed to send test email.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', message: 'Failed to send. Check your mail configuration.');
        } finally {
            $this->sending_test = false;
        }
    }

    // Apply settings to Laravel's running mail config without restarting
    private function applyMailConfig(): void
    {
        config([
            'mail.default' => $this->driver,
            'mail.mailers.smtp.host' => $this->host,
            'mail.mailers.smtp.port' => $this->port,
            'mail.mailers.smtp.username' => $this->username,
            'mail.mailers.smtp.password' => $this->password,
            'mail.mailers.smtp.encryption' => $this->encryption === 'none' ? null : $this->encryption,
            'mail.from.address' => $this->from_address,
            'mail.from.name' => $this->from_name,
        ]);
    }
}; ?>

<div>
    @include('partials.settings-heading')

    <x-pages::admin.settings.layout :heading="__('Mail Settings')" :subheading="__('Configure your outgoing email server')">
        <form wire:submit="save" class="space-y-6">

            {{-- SMTP Configuration --}}
            <div class="space-y-4">
                <flux:subheading class="font-medium">SMTP Configuration</flux:subheading>

                <flux:field>
                    <flux:label>Mail Driver</flux:label>
                    <flux:select wire:model="driver">
                        <flux:select.option value="smtp">SMTP</flux:select.option>
                        <flux:select.option value="mailgun">Mailgun</flux:select.option>
                        <flux:select.option value="ses">Amazon SES</flux:select.option>
                        <flux:select.option value="postmark">Postmark</flux:select.option>
                        <flux:select.option value="sendmail">Sendmail</flux:select.option>
                        <flux:select.option value="log">Log (Testing only)</flux:select.option>
                    </flux:select>
                    <flux:error name="driver" />
                </flux:field>

                <flux:field>
                    <flux:label>SMTP Host</flux:label>
                    <flux:input wire:model="host" placeholder="e.g. smtp.mailgun.org" />
                    <flux:error name="host" />
                </flux:field>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Port</flux:label>
                        <flux:select wire:model="port">
                            <flux:select.option value="25">25</flux:select.option>
                            <flux:select.option value="465">465 (SSL)</flux:select.option>
                            <flux:select.option value="587">587 (TLS)</flux:select.option>
                            <flux:select.option value="2525">2525</flux:select.option>
                        </flux:select>
                        <flux:error name="port" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Encryption</flux:label>
                        <flux:select wire:model="encryption">
                            <flux:select.option value="tls">TLS</flux:select.option>
                            <flux:select.option value="ssl">SSL</flux:select.option>
                            <flux:select.option value="none">None</flux:select.option>
                        </flux:select>
                        <flux:error name="encryption" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Username</flux:label>
                    <flux:input wire:model="username" placeholder="SMTP username" />
                    <flux:error name="username" />
                </flux:field>

                <flux:field>
                    <flux:label>Password</flux:label>
                    <flux:input wire:model="password" type="password" placeholder="SMTP password" />
                    <flux:error name="password" />
                </flux:field>
            </div>

            <flux:separator />

            {{-- Sender Details --}}
            <div class="space-y-4">
                <flux:subheading class="font-medium">Sender Details</flux:subheading>

                <flux:field>
                    <flux:label>From Address</flux:label>
                    <flux:input wire:model="from_address" type="email" placeholder="hello@sheffield.com" />
                    <flux:description>The email address your customers will see emails from.</flux:description>
                    <flux:error name="from_address" />
                </flux:field>

                <flux:field>
                    <flux:label>From Name</flux:label>
                    <flux:input wire:model="from_name" placeholder="Sheffield Africa" />
                    <flux:description>The name your customers will see emails from.</flux:description>
                    <flux:error name="from_name" />
                </flux:field>
            </div>

            <flux:separator />

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">
                    Save Changes
                </flux:button>
            </div>

        </form>

        {{-- Test Email — separate from the save form --}}
        <div class="mt-8 space-y-4 rounded-lg border border-zinc-200 dark:border-zinc-700 p-5">
            <div>
                <flux:subheading class="font-medium">Send Test Email</flux:subheading>
                <flux:text class="text-xs text-zinc-400 mt-1">
                    Verify your mail configuration is working correctly.
                    Make sure you save your settings before sending a test.
                </flux:text>
            </div>

            <div class="flex items-start gap-3">
                <flux:field class="flex-1">
                    <flux:input wire:model="test_email" type="email" placeholder="test@example.com" />
                    <flux:error name="test_email" />
                </flux:field>

                <flux:button wire:click="sendTestEmail" wire:loading.attr="disabled" icon="paper-airplane"
                    variant="ghost">
                    <span wire:loading.remove wire:target="sendTestEmail">Send Test</span>
                    <span wire:loading wire:target="sendTestEmail">Sending...</span>
                </flux:button>
            </div>
        </div>

    </x-pages::admin.settings.layout>
</div>
