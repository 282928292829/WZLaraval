<?php

namespace App\Providers\Filament;

use App\Filament\Pages\FontSettingsPage;
use App\Filament\Pages\SettingsPage;
use App\Filament\Pages\TranslationsPage;
use App\Http\Middleware\SetLocale;
use App\Models\Setting;
use App\Support\LogoHelper;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->favicon(fn () => Setting::faviconUrl('admin'))
            ->brandName(fn () => LogoHelper::getLogoText())
            ->brandLogo(fn () => LogoHelper::getLogoUrl())
            ->font(config('app.locale') === 'ar' ? 'IBM Plex Sans Arabic' : 'Inter')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                SettingsPage::class,
                FontSettingsPage::class,
                TranslationsPage::class,
            ])
            ->homeUrl(fn () => route('filament.admin.resources.orders.index'))
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->userMenuItems([
                Action::make('language')
                    ->label(fn (): string => __('Switch language text'))
                    ->icon('heroicon-o-language')
                    ->url(fn (): string => route('language.switch', app()->getLocale() === 'ar' ? 'en' : 'ar'))
                    ->openUrlInNewTab(false),
            ])
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, fn () => view('components.dev-toolbar'));
    }
}
