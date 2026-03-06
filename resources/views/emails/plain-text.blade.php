@php
    $locale = $locale ?? app()->getLocale();
    $dir = $locale === 'ar' ? 'rtl' : 'ltr';
    $siteName = $site_name ?? \App\Models\Setting::get('site_name') ?? config('app.name');
@endphp
<x-emails.layout :subject="$subject ?? ''" :site_name="$siteName">
    <div style="font-size:14px;color:#374151;line-height:1.8;white-space:pre-wrap;">{!! nl2br(e($body ?? '')) !!}</div>
</x-emails.layout>
