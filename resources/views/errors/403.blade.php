<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('errors.page_title_403', ['site_name' => $site_name ?? \App\Models\Setting::get('site_name') ?? config('app.name')]) }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600|ibm-plex-sans-arabic:400,500,600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #fff;
            color: #111827;
            font-family: {{ app()->getLocale() === 'ar' ? "'IBM Plex Sans Arabic', sans-serif" : "'Inter', sans-serif" }};
            padding: 2rem 1.25rem;
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
        }
        .card {
            width: 100%;
            max-width: 420px;
            border: 1px solid #f3f4f6;
            border-radius: 1.25rem;
            padding: 2.5rem 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
        }
        .badge {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #f97316;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 9999px;
            padding: .25rem .75rem;
            margin-bottom: 1.25rem;
        }
        h1 { font-size: 1.5rem; font-weight: 600; margin-bottom: .6rem; line-height: 1.3; }
        p  { font-size: .9375rem; color: #6b7280; line-height: 1.6; margin-bottom: 1.75rem; }
        .actions { display: flex; flex-wrap: wrap; gap: .75rem; }
        a.btn-primary {
            display: inline-block;
            padding: .625rem 1.25rem;
            background: #f97316;
            color: #fff;
            border-radius: .625rem;
            font-size: .9375rem;
            font-weight: 500;
            text-decoration: none;
            transition: background .15s;
        }
        a.btn-primary:hover { background: #ea6d10; }
        a.btn-ghost {
            display: inline-block;
            padding: .625rem 1.25rem;
            color: #6b7280;
            border: 1px solid #e5e7eb;
            border-radius: .625rem;
            font-size: .9375rem;
            font-weight: 500;
            text-decoration: none;
            transition: border-color .15s, color .15s;
        }
        a.btn-ghost:hover { border-color: #9ca3af; color: #374151; }
        .site-name {
            margin-top: 2.5rem;
            font-size: .8125rem;
            color: #d1d5db;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">{{ __('errors.403.badge') }}</span>
        <h1>{{ __('errors.403.title') }}</h1>
        <p>{{ __('errors.403.paragraph') }}</p>
        <div class="actions">
            <a href="{{ url('/') }}" class="btn-primary">{{ __('errors.403.home') }}</a>
            @guest
                <a href="{{ route('login') }}" class="btn-ghost">{{ __('errors.403.sign_in') }}</a>
            @endguest
        </div>
    </div>
    <p class="site-name">{{ config('app.name', 'Wasetzon') }}</p>
</body>
</html>
