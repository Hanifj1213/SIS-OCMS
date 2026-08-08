@props(['size' => 36, 'variant' => 'full'])

@if($variant === 'icon')
    <img
        src="{{ asset('images/brand/alamtri-icon.png') }}"
        alt="AlamTri"
        width="{{ $size }}"
        height="{{ $size }}"
        {{ $attributes->merge(['class' => 'alamtri-logo alamtri-logo-icon']) }}
    >
@else
    <img
        src="{{ asset('images/brand/alamtri-logo-full.png') }}"
        alt="AlamTri geo"
        {{ $attributes->merge(['class' => 'alamtri-logo alamtri-logo-full']) }}
        style="height: {{ $size }}px; width: auto;"
    >
@endif
