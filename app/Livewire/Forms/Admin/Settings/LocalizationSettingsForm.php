<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\LocalizationSettings;
use Livewire\Form;

class LocalizationSettingsForm extends Form
{
    public string $currency = 'KES';
    public string $currency_symbol = 'Ksh';
    public string $currency_position = 'before'; // before | after | before_space | after_space
    public string $decimal_separator = '.';
    public string $thousands_separator = ',';
    public int $decimal_places = 2;
    public string $timezone = 'Africa/Nairobi';
    public string $date_format = 'd/m/Y';
    public string $time_format = '12';
    public string $language = 'en';

    public function rules(): array
    {
        return [
            'currency' => ['required', 'string', 'max:10'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'currency_position' => ['required', 'in:before,after,before_space,after_space'],
            'decimal_separator' => ['required', 'in:.,\,'],
            'thousands_separator' => ['required', 'string', 'max:3'],
            'decimal_places' => ['required', 'integer', 'in:0,2'],
            'timezone' => ['required', 'timezone'],
            'date_format' => ['required', 'in:d/m/Y,m/d/Y,Y-m-d'],
            'time_format' => ['required', 'in:12,24'],
            'language' => ['required', 'string', 'max:10'],
        ];
    }

    public function fromSettings(LocalizationSettings $settings): void
    {
        $this->currency = $settings->currency;
        $this->currency_symbol = $settings->currency_symbol;
        $this->currency_position = $settings->currency_position;
        $this->decimal_separator = $settings->decimal_separator;
        $this->thousands_separator = $settings->thousands_separator;
        $this->decimal_places = $settings->decimal_places;
        $this->timezone = $settings->timezone;
        $this->date_format = $settings->date_format;
        $this->time_format = $settings->time_format;
        $this->language = $settings->language;
    }

    public function save(LocalizationSettings $settings): void
    {
        $this->validate();

        $settings->currency = $this->currency;
        $settings->currency_symbol = $this->currency_symbol;
        $settings->currency_position = $this->currency_position;
        $settings->decimal_separator = $this->decimal_separator;
        $settings->thousands_separator = $this->thousands_separator;
        $settings->decimal_places = $this->decimal_places;
        $settings->timezone = $this->timezone;
        $settings->date_format = $this->date_format;
        $settings->time_format = $this->time_format;
        $settings->language = $this->language;

        $settings->save();
    }

    /**
     * Returns a live preview of the currency format e.g. "Ksh 1,250.00"
     */
    public function preview(): string
    {
        $amount = number_format(1250, $this->decimal_places, $this->decimal_separator, $this->thousands_separator);
        $symbol = $this->currency_symbol;

        return match ($this->currency_position) {
            'before' => "{$symbol}{$amount}",
            'after' => "{$amount}{$symbol}",
            'before_space' => "{$symbol} {$amount}",
            'after_space' => "{$amount} {$symbol}",
            default => "{$symbol}{$amount}",
        };
    }
}
