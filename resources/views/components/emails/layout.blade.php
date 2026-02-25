@props(['locale' => null])
@php
    $locale = $locale ?? app()->getLocale();
    $dir = $locale === 'ar' ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject ?? config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'IBM Plex Sans Arabic', 'Segoe UI', Tahoma, Arial, sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            direction: {{ $dir }};
            text-align: {{ $dir === 'rtl' ? 'right' : 'left' }};
        }
        .wrapper {
            max-width: 600px;
            margin: 32px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .header {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            padding: 28px 32px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .header p {
            color: rgba(255,255,255,0.85);
            font-size: 13px;
            margin-top: 6px;
        }
        .body {
            padding: 32px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 12px;
        }
        .intro {
            font-size: 14px;
            color: #4b5563;
            line-height: 1.7;
            margin-bottom: 24px;
        }
        .card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px 24px;
            margin-bottom: 20px;
        }
        .card-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 14px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
            gap: 12px;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #6b7280; }
        .info-value { font-weight: 600; color: #111827; }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-orange { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
        .badge-green  { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-blue   { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .btn {
            display: inline-block;
            background: #f97316;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            margin: 8px 0;
        }
        .btn:hover { background: #ea580c; }
        .divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 24px 0;
        }
        .footer {
            background: #f9fafb;
            padding: 20px 32px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
        }
        .footer a { color: #f97316; text-decoration: none; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { background: #f3f4f6; padding: 10px 12px; text-align: {{ $dir === 'rtl' ? 'right' : 'left' }}; color: #6b7280; font-size: 12px; font-weight: 600; }
        td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; vertical-align: top; color: #374151; }
        tr:last-child td { border-bottom: none; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>{{ $site_name ?? \App\Models\Setting::get('site_name') ?? config('app.name') }}</h1>
        <p>{{ __('emails.layout.tagline', ['site_name' => $site_name ?? \App\Models\Setting::get('site_name') ?? config('app.name')]) }}</p>
    </div>
    <div class="body">
        {{ $slot }}
    </div>
    <div class="footer">
        <p>
            {{ __('emails.layout.footer', ['site_name' => $site_name ?? \App\Models\Setting::get('site_name') ?? config('app.name')]) }}
        </p>
        <p style="margin-top:8px;">
            <a href="{{ config('app.url') }}">{{ parse_url(config('app.url'), PHP_URL_HOST) ?? 'wasetzon.com' }}</a>
        </p>
    </div>
</div>
</body>
</html>
