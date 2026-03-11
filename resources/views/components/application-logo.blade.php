@php
    $logoUrl = \App\Support\LogoHelper::getLogoUrl();
    $logoText = \App\Support\LogoHelper::getLogoText();
    $logoAlt = \App\Support\LogoHelper::getLogoAlt();
    $primaryColor = trim((string) \App\Models\Setting::get('primary_color', '#f97316')) ?: '#f97316';
    $primaryColor = str_starts_with($primaryColor, '#') ? $primaryColor : '#' . $primaryColor;
@endphp
@if($logoUrl)
    <img src="{{ $logoUrl }}" alt="{{ $logoAlt }}" {{ $attributes->merge(['class' => 'h-8 w-auto object-contain']) }}>
@else
    <span {{ $attributes->merge(['class' => 'font-bold tracking-tight']) }} style="color: {{ $primaryColor }} !important">
        {{ $logoText }}
    </span>
@endif
