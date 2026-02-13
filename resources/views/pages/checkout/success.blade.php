<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Log;

new #[Layout('layouts.guest')] class extends Component {
    public function mount()
    {
        // Log all incoming request data
        Log::info(
            'Payment Success Callback Data: ' .
                json_encode(
                    [
                        'query_params' => request()->query(),
                        'all_params' => request()->all(),
                        'headers' => request()->headers->all(),
                    ],
                    JSON_PRETTY_PRINT,
                ),
        );
    }
};
?>
<div class="container mx-auto px-4">
    <p>Success {{ request()->notificationId }}</p>

    @if (config('app.debug'))
        <div class="mt-4 p-4 bg-gray-100 rounded">
            <h3 class="font-bold">Debug Info:</h3>
            <pre>{{ json_encode(request()->all(), JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endif
</div>
