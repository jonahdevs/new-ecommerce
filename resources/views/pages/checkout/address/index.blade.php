<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

new #[Layout('layouts.guest')] class extends Component {
    public function mount()
    {
        $user = auth()->user();

        // If no address exists → go to create address
        if ($user->defaultAddress()->doesntExist()) {
            return redirect()->route('checkout.addresses.create');
        }
    }
};
?>

<div>
    {{-- It always seems impossible until it is done. - Nelson Mandela --}}
</div>
