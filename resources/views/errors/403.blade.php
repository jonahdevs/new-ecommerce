@php
    $isAdmin = auth()->check() && auth()->user()->is_staff;
    $customMessage = $exception?->getMessage();
    $errorData = [
        'code' => '403',
        'title' => 'Access denied',
        'message' =>
            $customMessage ?:
            ($isAdmin
                ? 'You do not have permission to access this page. If you believe this is a mistake, please contact your administrator.'
                : 'You do not have permission to access this page. If you believe this is a mistake, please get in touch with our support team.'),
        'isAdmin' => $isAdmin,
    ];
@endphp

@if ($isAdmin)
    <x-layouts::app title="403 — Access Denied">
        @include('errors._error_body', $errorData)
    </x-layouts::app>
@else
    <x-layouts::guest>
        @include('errors._error_body', $errorData)
    </x-layouts::guest>
@endif
