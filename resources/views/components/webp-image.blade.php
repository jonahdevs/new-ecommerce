@props(['src', 'webp' => null, 'alt' => ''])

@if ($webp)
<picture>
    <source type="image/webp" srcset="{{ $webp }}">
    <img src="{{ $src }}" alt="{{ $alt }}" {{ $attributes }}>
</picture>
@else
<img src="{{ $src }}" alt="{{ $alt }}" {{ $attributes }}>
@endif
