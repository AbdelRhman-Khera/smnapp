<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
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
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;
use Filament\Navigation\MenuItem;
use Joaopaulolndev\FilamentEditProfile\Pages\EditProfilePage;
use Awcodes\Overlook\OverlookPlugin;
use Awcodes\Overlook\Widgets\OverlookWidget;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;
use Rmsramos\Activitylog\ActivitylogPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::hex('#1C4199'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->widgets([
                // OverlookWidget::class,
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
                \App\Filament\Widgets\DailyMaintenanceRequestsChart::class,
                \App\Filament\Widgets\RequestsByCityChart::class,
                \App\Filament\Widgets\RequestsByDistrictChart::class,
                \App\Filament\Widgets\MaintenanceRequestTypesChart::class,
                \App\Filament\Widgets\CompletedPendingRequestsChart::class,
                \App\Filament\Widgets\MonthlyInvoicesChart::class,
                \App\Filament\Widgets\MonthlyRevenueChart::class,
                \App\Filament\Widgets\TopProductsChart::class,
                \App\Filament\Widgets\TopServicesChart::class,
                \App\Filament\Widgets\SparePartUsageChart::class,
                \App\Filament\Widgets\AvgCompletionTimeChart::class,
                \App\Filament\Widgets\TechnicianRequestCountChart::class,
                \App\Filament\Widgets\FeedbackRatingChart::class,
                \App\Filament\Widgets\ServiceFrequencyChart::class,
                \App\Filament\Widgets\NewCustomersChart::class,
                \App\Filament\Widgets\RequestsPerSlotChart::class,
                \App\Filament\Widgets\MostActiveDistrictsChart::class,
                \App\Filament\Widgets\MostActiveCitiesChart::class,
                \App\Filament\Widgets\SupportFormsPlatformChart::class,
                \App\Filament\Widgets\CompletedRequestsMonthlyChart::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                \FilipFonal\FilamentLogManager\FilamentLogManager::make(),
                FilamentApexChartsPlugin::make(),
                FilamentEditProfilePlugin::make()
                    ->slug('my-profile')
                    ->setTitle('My Profile')
                    ->setNavigationLabel('My Profile')
                    ->setNavigationGroup('Group Profile')
                    ->setIcon('heroicon-o-user')
                    ->setSort(10)
                    // ->canAccess(fn() => auth()->user()->id === 1)
                    ->shouldRegisterNavigation(false)
                    ->shouldShowDeleteAccountForm(false)
                    ->shouldShowSanctumTokens(false)
                    ->shouldShowBrowserSessionsForm()
                    ->shouldShowAvatarForm(),
                OverlookPlugin::make()
                    ->sort(1)
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                        'md' => 3,
                        'lg' => 4,
                        'xl' => 4,
                        '2xl' => null,
                    ]),
                ActivitylogPlugin::make()
                 ->navigationGroup('System'),

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
            ])
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label(fn() => auth()->user()->name)
                    ->url(fn(): string => EditProfilePage::getUrl())
                    ->icon('heroicon-m-user-circle')


            ]);
    }
}
