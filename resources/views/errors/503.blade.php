@php
    $isAdmin = auth()->check() && auth()->user()->is_staff;
    $errorData = [
        'code' => '503',
        'title' => 'Under maintenance',
        'message' =>
            $maintenanceMessage ?? 'We are performing scheduled maintenance to improve your experience. We will be back shortly — thank you for your patience.',
        'isAdmin' => $isAdmin,
    ];
@endphp

@if ($isAdmin)
    <x-layouts::app title="503 — Maintenance">
        @include('errors._error_body', $errorData)
    </x-layouts::app>
@else
    <x-layouts::guest>
        @include('errors._error_body', $errorData)
    </x-layouts::guest>
@endif
