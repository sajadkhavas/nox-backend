<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\LbbCommerceOverview;
use App\Http\Middleware\BlockUnsafeAdminToolsInProduction;
use App\Http\Middleware\PreventAdminIndexing;
use Awcodes\Curator\CuratorPlugin;
use Awcodes\Curator\Resources\MediaResource;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackupPlugin;
use Tapp\FilamentAuthenticationLog\FilamentAuthenticationLogPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch): void {
            $switch->locales(['fa', 'en']);
        });
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName(config('lbb.brand.name', 'LBB'))
            ->favicon(asset('favicon.png').'?v=lbb-20260926')
            ->colors(['primary' => Color::Emerald])
            ->font('Vazirmatn')
            ->renderHook('panels::body.end', fn () => view('filament.media-upload-optimizer'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([Pages\Dashboard::class])
            ->widgets([
                LbbCommerceOverview::class,
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                PreventAdminIndexing::class,
                BlockUnsafeAdminToolsInProduction::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                FilamentSpatieLaravelBackupPlugin::make(),
                FilamentAuthenticationLogPlugin::make(),
                CuratorPlugin::make()
                    ->label('رسانه')
                    ->pluralLabel('مدیریت رسانه')
                    ->navigationIcon('heroicon-o-photo')
                    ->navigationGroup('محتوا')
                    ->navigationSort(5)
                    ->resource(MediaResource::class),
            ])
            ->authMiddleware([Authenticate::class])
            ->navigationGroups([
                'فروشگاه LBB',
                'محتوا',
                'فروش',
                'تنظیمات',
                'سیستم',
            ])
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->sidebarCollapsibleOnDesktop();
    }
}
