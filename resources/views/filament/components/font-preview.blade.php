@props(['family', 'url', 'source'])

@if($source === 'google' && $url)
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ $url }}" rel="stylesheet">
@endif

<div class="rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-3 dark:border-gray-700 dark:bg-gray-800" style="font-family: '{{ $family }}', ui-sans-serif, system-ui, sans-serif;">
    <p class="text-sm text-gray-600 dark:text-gray-400" dir="rtl">{{ __('font.preview_arabic') }}</p>
    <p class="text-sm text-gray-600 dark:text-gray-400" dir="ltr">{{ __('font.preview_english') }}</p>
</div>
