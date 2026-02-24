<?php

namespace App\Livewire\Forms\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use App\Models\Address;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Livewire\Form;
use Livewire\WithFileUploads;

class CustomerForm extends Form
{
    use WithFileUploads;

    public ?User $customer = null;

    // Personal
    public string $name         = '';
    public string $email        = '';
    public string $phone_number = '';
    public bool   $verify_email = true;
    public $avatar              = null;

    // Default Address
    public string $address_first_name       = '';
    public string $address_last_name        = '';
    public string $address_phone            = '';
    public ?int   $county_id                = null;
    public ?int   $area_id                  = null;
    public string $address_line             = '';
    public string $additional_information   = '';

    // Status (edit only)
    public string  $status           = 'active';
    public string  $status_reason    = '';
    public ?string $suspended_until  = null;

    public function rules(): array
    {
        $isEdit = (bool) $this->customer;

        return [
            // Personal
            'name'         => ['required', 'string', 'min:2', 'max:255'],
            'email'        => ['required', 'email', Rule::unique('users', 'email')->ignore($this->customer?->id)],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'verify_email' => ['boolean'],
            'avatar'       => ['nullable', 'image', 'max:3072'],

            // Address
            'address_first_name'     => ['required', 'string', 'max:100'],
            'address_last_name'      => ['required', 'string', 'max:100'],
            'address_phone'          => ['required', 'string', 'max:20'],
            'county_id'              => ['required', 'exists:counties,id'],
            'area_id'                => ['required', 'exists:areas,id'],
            'address_line'           => ['required', 'string', 'max:255'],
            'additional_information' => ['nullable', 'string', 'max:500'],

            // Status — only validated on edit
            'status'          => [$isEdit ? 'required' : 'sometimes', Rule::enum(UserStatus::class)],
            'status_reason'   => ['nullable', 'string', 'max:500'],
            'suspended_until' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function setCustomer(User $customer): void
    {
        $this->customer     = $customer;
        $this->name         = $customer->name;
        $this->email        = $customer->email;
        $this->phone_number = $customer->phone_number ?? '';
        $this->verify_email = !is_null($customer->email_verified_at);
        $this->status       = $customer->status->value;
        $this->status_reason   = $customer->status_reason ?? '';
        $this->suspended_until = $customer->suspended_until?->format('Y-m-d');

        // Load default address if exists
        $address = $customer->defaultAddress;
        if ($address) {
            $this->address_first_name     = $address->first_name;
            $this->address_last_name      = $address->last_name;
            $this->address_phone          = $address->phone_number ?? '';
            $this->county_id              = $address->county_id;
            $this->area_id                = $address->area_id;
            $this->address_line           = $address->address ?? '';
            $this->additional_information = $address->additional_information ?? '';
        }
    }

    public function store(): User
    {
        $this->validate();

        $customer = User::create([
            'name'              => $this->name,
            'email'             => $this->email,
            'password'          => bcrypt(str()->random(16)),
            'phone_number'      => $this->phone_number ?: null,
            'is_staff'          => false,
            'status'            => UserStatus::ACTIVE,
            'email_verified_at' => $this->verify_email ? now() : null,
        ]);

        $this->saveAddress($customer);

        if ($this->avatar) {
            $customer->update([
                'avatar' => $this->avatar->store('avatars', 'public')
            ]);
        }

        // Send password reset link so customer can set their own password
        Password::sendResetLink(['email' => $customer->email]);

        return $customer;
    }

    public function update(): void
    {
        $this->validate();

        $this->customer->update([
            'name'              => $this->name,
            'email'             => $this->email,
            'phone_number'      => $this->phone_number ?: null,
            'status'            => $this->status,
            'status_reason'     => in_array($this->status, ['banned', 'suspended']) ? $this->status_reason : null,
            'suspended_until'   => $this->status === 'suspended' ? $this->suspended_until : null,
            'email_verified_at' => $this->verify_email
                ? ($this->customer->email_verified_at ?? now())
                : null,
        ]);

        $this->saveAddress($this->customer);

        if ($this->avatar) {
            $this->customer->update([
                'avatar' => $this->avatar->store('avatars', 'public')
            ]);
        }
    }

    public function sendPasswordResetLink(): void
    {
        Password::sendResetLink(['email' => $this->customer->email]);
    }

    private function saveAddress(User $user): void
    {
        $area = \App\Models\Area::with('county')->find($this->area_id);

        // Area is more precise, fall back to county if area has no zone
        $shippingZoneId = $area?->shipping_zone_id ?? $area?->county?->shipping_zone_id;

        $user->addresses()->updateOrCreate(
            ['is_default' => true],
            [
                'first_name'             => $this->address_first_name,
                'last_name'              => $this->address_last_name,
                'phone_number'           => $this->address_phone,
                'county_id'              => $this->county_id,
                'area_id'                => $this->area_id,
                'address'                => $this->address_line,
                'shipping_zone_id'       => $shippingZoneId,
                'additional_information' => $this->additional_information ?: null,
                'is_default'             => true,
            ]
        );
    }
}
