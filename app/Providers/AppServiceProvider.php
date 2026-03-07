<?php

namespace App\Providers;

use App\Auth\WpCompatUserProvider;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event): void {
            $event->extendSocialite('apple', \SocialiteProviders\Apple\Provider::class);
        });

        // Share site_name for multi-site string replacements in views
        try {
            View::share('site_name', Setting::get('site_name') ?: config('app.name'));
        } catch (\Throwable $e) {
            View::share('site_name', config('app.name'));
        }

        // Custom user provider that transparently verifies WordPress phpass hashes
        // and upgrades them to bcrypt on first successful login.
        Auth::provider('wp-compat', function ($app, array $config) {
            return new WpCompatUserProvider(
                $app['hash'],
                $config['model']
            );
        });
    }
}
