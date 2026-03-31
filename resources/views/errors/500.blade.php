@php
    $isAdmin = auth()->check() && auth()->user()->is_staff;
    $errorData = [
        'code' => '500',
        'title' => 'Something went wrong',
        'message' =>
            'We ran into an unexpected error on our end. Our team has been notified and is working on a fix. Please try again in a few moments.',
        'isAdmin' => $isAdmin,
    ];
@endphp

@if ($isAdmin)
    <x-layouts::app title="500 — Server Error">
        @include('errors._error_body', $errorData)
    </x-layouts::app>
@else
    <x-layouts::guest>
        @include('errors._error_body', $errorData)
    </x-layouts::guest>
@endif
