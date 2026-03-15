<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locales = config('app.available_locales', ['ar', 'en']);

        // Priority: ?lang= (for hreflang crawlable URLs) → session → user preference → setting → config
        $defaultFromSetting = Setting::get('default_language', null);
        $configLocale = config('app.locale');

        $locale = $request->query('lang')
            ?? session('locale')
            ?? auth()->user()?->locale
            ?? (in_array($defaultFromSetting, $locales) ? $defaultFromSetting : null)
            ?? $configLocale;

        if (! in_array($locale, $locales)) {
            $locale = $configLocale;
        }

        // Persist locale so it survives across requests (session + user when authenticated)
        if ($request->has('lang')) {
            session(['locale' => $locale]);
            if (auth()->check()) {
                auth()->user()->update(['locale' => $locale]);
            }
        } elseif (auth()->check() && $locale === auth()->user()->locale) {
            // Sync session when we used user's stored locale (e.g. after session expiry)
            session(['locale' => $locale]);
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
