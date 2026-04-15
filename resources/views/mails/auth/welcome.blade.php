{{-- resources/views/mails/auth/welcome.blade.php --}}
<x-mail::message>

    # Welcome, {{ $user->name }}

    Thank you for creating an account with **{{ config('app.name') }}**. We're glad to have you.

    Your account is ready — browse our catalog or manage your profile anytime.

    <x-mail::button :url="$shopUrl">Browse the Shop</x-mail::button>

    [My Account]({{ $accountUrl }})

    If you have any questions, feel free to reply to this email.

    Thanks,
    **{{ config('app.name') }} Team**

    <x-mail::subcopy>You're receiving this because you created an account at {{ config('app.name') }}. If this wasn't
        you, you can safely ignore this message.</x-mail::subcopy>

</x-mail::message>
