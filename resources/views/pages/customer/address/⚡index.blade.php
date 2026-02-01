<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

new #[Layout('layouts.customer')] class extends Component {
    #[Computed]
    public function defaultAddress()
    {
        return auth()->user()->defaultAddress;
    }
};
?>

<div>

</div>
