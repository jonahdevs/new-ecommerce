<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\MailSettings;
use Livewire\Form;

class MailSettingsForm extends Form
{
    public string $mailer = 'smtp';

    public string $host = '';

    public int $port = 587;

    public string $username = '';

    public string $encryption = 'tls';

    public ?string $from_address = null;

    public ?string $from_name = null;

    public string $reply_to_address = '';

    // Encrypted — user must re-enter to change
    public string $password = '';

    public bool $has_password = false;

    public function rules(): array
    {
        return [
            'mailer' => ['required', 'in:smtp,ses,mailgun,sendgrid,log,array'],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'encryption' => ['nullable', 'in:tls,ssl,'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:100'],
            'reply_to_address' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function fromSettings(MailSettings $settings): void
    {
        $this->mailer = $settings->mailer;
        $this->host = $settings->host ?? '';
        $this->port = $settings->port ?? 587;
        $this->username = $settings->username ?? '';
        $this->encryption = $settings->encryption ?? 'tls';
        $this->from_address = $settings->from_address ?? '';
        $this->from_name = $settings->from_name ?? '';
        $this->reply_to_address = $settings->reply_to_address ?? '';

        $this->has_password = ! empty($settings->password);
    }

    public function save(MailSettings $settings): void
    {
        $this->validate();

        $settings->mailer = $this->mailer;
        $settings->host = $this->host ?: null;
        $settings->port = $this->port;
        $settings->username = $this->username ?: null;
        $settings->encryption = $this->encryption ?: null;
        $settings->from_address = $this->from_address ?: null;
        $settings->from_name = $this->from_name ?: null;
        $settings->reply_to_address = $this->reply_to_address ?: null;

        if ($this->password) {
            $settings->password = $this->password;
        }

        $settings->save();

        $this->has_password = ! empty($settings->password);
        $this->password = '';
    }
}
