<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\LimbahTotals;
use App\Filament\Widgets\Totaltransaksi;
use App\Filament\Widgets\TotalPendapatan;
use App\Filament\Resources\tb_tokos\tb_tokoResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Livewire\GlobalSearch;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
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
            ->spa()
            ->globalSearch(true)
            
            ->globalSearchDebounce(300)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->brandName('Sistem pengelolaan limbah')
            ->resources([
                tb_tokoResource::class,

            ])
            
            ->navigationGroups([
                'Transaksi',
                'Data Master',
                'Laporan',
                'Akun',
                NavigationGroup::make('Lainnya')->icon('heroicon-o-ellipsis-horizontal')->collapsed(true),
            ])
            
            ->widgets([
                Totaltransaksi::class,
                TotalPendapatan::class,
                LimbahTotals::class,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            
            ->pages([
                Dashboard::class,
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
