<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locales = config('app.available_locales', ['ar', 'en']);

        // Priority: ?lang= (for hreflang crawlable URLs) → session → user preference → default
        $locale = $request->query('lang')
            ?? session('locale')
            ?? auth()->user()?->locale
            ?? config('app.locale');

        if (! in_array($locale, $locales)) {
            $locale = config('app.locale');
        }

        if ($request->has('lang')) {
            session(['locale' => $locale]);
            if (auth()->check()) {
                auth()->user()->update(['locale' => $locale]);
            }
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
