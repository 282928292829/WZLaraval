@php
    $availableLocales = config('app.available_locales', ['ar', 'en']);
@endphp
@if(count($availableLocales) > 1)
@foreach($availableLocales as $loc)
@php
    $query = array_merge(request()->query() ?? [], ['lang' => $loc]);
    $altUrl = url()->current() . '?' . http_build_query($query);
@endphp
<link rel="alternate" hreflang="{{ $loc }}" href="{{ $altUrl }}">
@endforeach
@php
    $defaultQuery = array_merge(request()->query() ?? [], ['lang' => config('app.locale')]);
    $defaultUrl = url()->current() . '?' . http_build_query($defaultQuery);
@endphp
<link rel="alternate" hreflang="x-default" href="{{ $defaultUrl }}">
@endif
