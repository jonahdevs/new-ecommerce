@php
    $isAdmin = auth()->check() && auth()->user()->is_staff;
    $errorData = [
        'code' => '429',
        'title' => 'Too many requests',
        'message' => 'You\'ve made too many requests. Please wait a moment and try again.',
        'isAdmin' => $isAdmin,
    ];
@endphp

@if ($isAdmin)
    <x-layouts::app title="429 — Too Many Requests">
        @include('errors._error_body', $errorData)
    </x-layouts::app>
@else
    <x-layouts::guest>
        @include('errors._error_body', $errorData)
    </x-layouts::guest>
@endif
