@php
    $isAdmin = auth()->check() && auth()->user()->is_staff;
    $errorData = [
        'code' => '404',
        'title' => 'Page not found',
        'message' => 'The page you are looking for might have been moved, renamed, or no longer exists.',
        'isAdmin' => $isAdmin,
    ];
@endphp

@if ($isAdmin)
    <x-layouts::app title="404 — Not Found">
        @include('errors._error_body', $errorData)
    </x-layouts::app>
@else
    <x-layouts::guest>
        @include('errors._error_body', $errorData)
    </x-layouts::guest>
@endif
