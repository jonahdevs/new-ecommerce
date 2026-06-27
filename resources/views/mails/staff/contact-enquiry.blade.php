<x-mail::message>
# New contact enquiry

**{{ $inquiry }}** — {{ $reference }}

<x-mail::table>
| | |
|:--|:--|
| **Name** | {{ $name }}{{ $business ? ' — '.$business : '' }} |
| **Email** | {{ $email }} |
@if($phone)
| **Phone** | {{ $phone }} |
@endif
</x-mail::table>

<x-mail::panel>
{{ $message }}
</x-mail::panel>

Reference: `{{ $reference }}`
</x-mail::message>
