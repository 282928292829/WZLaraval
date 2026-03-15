<?php

namespace App\Providers\Filament;

use App\Filament\Pages\GeneralSettingsPage;
use App\Filament\Pages\TranslationsPage;
use App\Http\Middleware\SetLocale;
use App\Models\Setting;
use App\Support\LogoHelper;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
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
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(fn (): string => __('Orders')),
                NavigationGroup::make()
                    ->label(fn (): string => __('Order Setup')),
                NavigationGroup::make()
                    ->label(fn (): string => __('Content')),
                NavigationGroup::make()
                    ->label(fn (): string => __('Settings')),
                NavigationGroup::make()
                    ->label(fn (): string => __('Users')),
            ])
            ->favicon(fn () => Setting::faviconUrl('admin'))
            ->brandName(fn () => LogoHelper::getAdminLogoText())
            ->brandLogo(fn () => LogoHelper::getAdminLogoUrl())
            ->font(fn () => app()->getLocale() === 'ar' ? 'IBM Plex Sans Arabic' : 'Inter')
            ->colors([
                'primary' => Color::hex(Setting::get('primary_color', '#f97316')),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                GeneralSettingsPage::class,
                TranslationsPage::class,
            ])
            ->homeUrl('/admin')
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
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn (): string => view('components.admin-topbar-homepage')->render())
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, fn (): string => view('components.admin-login-language-switch')->render())
            ->userMenuItems([
                Action::make('language')
                    ->label(fn (): string => app()->getLocale() === 'ar' ? 'English' : 'العربية')
                    ->icon('heroicon-o-globe-alt')
                    ->url(fn (): string => route('language.switch', app()->getLocale() === 'ar' ? 'en' : 'ar'))
                    ->openUrlInNewTab(false),
            ])
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, app()->environment('local')
                ? fn () => view('components.admin-login-dev-buttons')
                : fn () => '');
    }
}
