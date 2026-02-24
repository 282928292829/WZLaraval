@php
    $seoTitle = $page->getSeoTitle();
    $seoDesc = $page->getSeoDescription();
    $canonicalUrl = $page->canonical_url ?: url('/pages/' . $page->slug);
    $ogImage = $page->getOgImageUrl();
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => $seoTitle,
        'description' => $seoDesc,
        'url' => $canonicalUrl,
    ];
@endphp
<x-slot name="title">{{ $seoTitle }}</x-slot>
<x-slot name="description">{{ $seoDesc }}</x-slot>
@if($page->robots)<x-slot name="robots">{{ $page->robots }}</x-slot>@endif
<x-slot name="canonicalUrl">{{ $canonicalUrl }}</x-slot>
@if($ogImage)
<x-slot name="ogImage">{{ $ogImage }}</x-slot>
<x-slot name="ogImageAlt">{{ $seoTitle }}</x-slot>
@endif
<x-slot name="schema">@json($schema)</x-slot>
