@php
    $isAdmin = auth()->check() && auth()->user()->is_staff;
    $errorData = [
        'code' => '419',
        'title' => 'Session expired',
        'message' =>
            'Your session timed out for security purposes. Please refresh the page to continue where you left off.',
        'isAdmin' => $isAdmin,
    ];
@endphp

@if ($isAdmin)
    <x-layouts::app title="419 — Session Expired">
        @include('errors._error_body', $errorData)
    </x-layouts::app>
@else
    <x-layouts::guest>
        @include('errors._error_body', $errorData)
    </x-layouts::guest>
@endif
