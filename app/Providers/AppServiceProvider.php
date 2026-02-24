<?php

namespace App\Providers;

use App\Auth\WpCompatUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
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
