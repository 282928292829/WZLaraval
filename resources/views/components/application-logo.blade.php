@php
    $logoUrl = \App\Support\LogoHelper::getLogoUrl();
    $logoText = \App\Support\LogoHelper::getLogoText();
    $logoAlt = \App\Support\LogoHelper::getLogoAlt();
@endphp
@if($logoUrl)
    <img src="{{ $logoUrl }}" alt="{{ $logoAlt }}" {{ $attributes->merge(['class' => 'h-8 w-auto object-contain']) }}>
@else
    <span {{ $attributes->merge(['class' => 'font-bold text-primary-600 tracking-tight']) }}>
        {{ $logoText }}
    </span>
@endif
