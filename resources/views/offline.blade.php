<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ trim((string) \App\Models\Setting::get('primary_color', '#f97316')) }}">
    <title>{{ __('You\'re Offline') }} — {{ \App\Models\Setting::get('site_name') ?: __('app.name') }}</title>
    <link rel="icon" href="{{ \App\Models\Setting::faviconUrl('site') }}">
    <link rel="manifest" href="/manifest.json">
    <style>
        *{box-sizing:border-box}body{margin:0;font-family:system-ui,-apple-system,sans-serif;background:#f9fafb;color:#111827;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:1.5rem;text-align:center}
        .icon{font-size:3rem;margin-bottom:1rem}
        h1{font-size:1.25rem;font-weight:700;color:#1f2937;margin:0 0 .5rem}
        p{color:#6b7280;font-size:.875rem;margin:0 0 1.5rem}
        a{display:inline-flex;align-items:center;justify-content:center;padding:.75rem 1.5rem;border-radius:.75rem;font-weight:600;color:#fff;text-decoration:none}
        a:hover{opacity:.9}
    </style>
</head>
<body>
    <div class="icon">🔌</div>
    <h1>{{ __('You\'re Offline') }}</h1>
    <p>{{ __('pwa.offline_hint') }}</p>
    <a href="/" style="background:{{ trim((string) \App\Models\Setting::get('primary_color', '#f97316')) }}">{{ __('pwa.offline_back') }}</a>
</body>
</html>
