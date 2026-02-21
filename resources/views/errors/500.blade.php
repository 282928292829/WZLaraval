<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>خطأ في الخادم — وسيط زون</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans-arabic:400,500,600&display=swap" rel="stylesheet">
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
            font-family: 'IBM Plex Sans Arabic', sans-serif;
            padding: 2rem 1.25rem;
            text-align: right;
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
            color: #dc2626;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 9999px;
            padding: .25rem .75rem;
            margin-bottom: 1.25rem;
        }
        h1 { font-size: 1.5rem; font-weight: 600; margin-bottom: .6rem; line-height: 1.3; }
        p  { font-size: .9375rem; color: #6b7280; line-height: 1.6; margin-bottom: 1.75rem; }
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
        .site-name {
            margin-top: 2.5rem;
            font-size: .8125rem;
            color: #d1d5db;
            text-align: center;
        }
    </style>
</head>
<body>
    {{--
        500 pages must be fully static — no @auth, no DB calls, no lang helpers,
        no Vite, no Livewire. The app is broken; assume nothing works.
    --}}
    <div class="card">
        <span class="badge">500 — خطأ في الخادم</span>
        <h1>حدث خطأ غير متوقع</h1>
        <p>نعمل على إصلاح المشكلة في أقرب وقت. جرّب إعادة تحميل الصفحة، أو عد لاحقاً.</p>
        <a href="/" class="btn-primary">الرئيسية</a>
    </div>
    <p class="site-name">وسيط زون</p>
</body>
</html>
