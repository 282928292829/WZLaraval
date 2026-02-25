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
