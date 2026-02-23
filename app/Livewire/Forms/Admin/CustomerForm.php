<?php

namespace App\Livewire\Forms\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Form;
use Livewire\WithFileUploads;

class CustomerForm extends Form
{
    use WithFileUploads;

    public ?User $customer = null;

    public $name         = '';
    public $email        = '';
    public $phone_number = '';
    public $country      = '';
    public $state        = '';
    public $city         = '';
    public $address      = '';
    public $zip_code     = '';
    public $verify_email = true;
    public $banned       = false;
    public $avatar       = null;

    public function rules(): array
    {
        return [
            'name'         => 'required|min:2|max:255',
            'email'        => [
                'required',
                'email',
                $this->customer
                    ? 'unique:users,email,' . $this->customer->id
                    : 'unique:users,email',
            ],
            'phone_number' => 'nullable|max:20',
            'country'      => 'nullable|max:100',
            'state'        => 'nullable|max:100',
            'city'         => 'nullable|max:100',
            'address'      => 'nullable|max:255',
            'zip_code'     => 'nullable|max:20',
            'verify_email' => 'boolean',
            'banned'       => 'boolean',
            'avatar'       => 'nullable|image|max:3072',
        ];
    }

    public function setCustomer(User $customer): void
    {
        $this->customer = $customer;
        $this->fill($customer->only([
            'name',
            'email',
            'phone_number',
            'country',
            'state',
            'city',
            'address',
            'zip_code',
        ]));
        $this->verify_email = !is_null($customer->email_verified_at);
        $this->banned       = $customer->status === 'banned';
    }

    public function store(): User
    {
        $this->validate();

        $customer = User::create([
            'name'              => $this->name,
            'email'             => $this->email,
            'password'          => Hash::make(str()->random(16)),
            'phone_number'      => $this->phone_number,
            'country'           => $this->country,
            'state'             => $this->state,
            'city'              => $this->city,
            'address'           => $this->address,
            'zip_code'          => $this->zip_code,
            'email_verified_at' => $this->verify_email ? now() : null,
            'status'            => 'active',
        ]);

        $customer->assignRole('customer');

        if ($this->avatar) {
            $customer->update(['avatar' => $this->avatar->store('avatars', 'public')]);
        }

        return $customer;
    }

    public function update(): void
    {
        $this->validate();

        $this->customer->update([
            'name'              => $this->name,
            'email'             => $this->email,
            'phone_number'      => $this->phone_number,
            'country'           => $this->country,
            'state'             => $this->state,
            'city'              => $this->city,
            'address'           => $this->address,
            'zip_code'          => $this->zip_code,
            'email_verified_at' => $this->verify_email ? ($this->customer->email_verified_at ?? now()) : null,
            'status'            => $this->banned ? 'banned' : 'active',
        ]);

        if ($this->avatar) {
            $this->customer->update(['avatar' => $this->avatar->store('avatars', 'public')]);
        }
    }
}
