<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\AbsenteeismChart;
use App\Filament\Widgets\DepartmentDistributionChart;
use App\Filament\Widgets\LeaveStatusChart;
use App\Filament\Widgets\OnboardingWidget;
use App\Filament\Widgets\OperationalAlertsWidget;
use App\Filament\Widgets\PayrollMassChart;
use App\Filament\Widgets\PayslipStatusChart;
use App\Filament\Widgets\RecentActivityWidget;
use App\Filament\Widgets\SmartRhStatsOverview;
use App\Filament\Widgets\SupportTicketsOverview;
use App\Filament\Widgets\SubscriptionStatusWidget;
use App\Filament\Widgets\SubscriptionUsageChart;
use App\Filament\Widgets\WorkflowAlertsWidget;
use App\Filament\Pages\Auth\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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
            ->login(Login::class)
            ->brandName(config('smartrh.product_name', 'SmartRH Maroc'))
            ->brandLogo(asset('images/branding/smartrh-logo.png'))
            ->darkModeBrandLogo(asset('images/branding/smartrh-icon.png'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('images/branding/smartrh-icon.ico'))
            ->darkMode(true, true)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => Color::Teal,
                'success' => Color::Emerald,
                'info' => Color::Teal,
                'warning' => Color::Amber,
                'danger' => Color::Red,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                OnboardingWidget::class,
                SmartRhStatsOverview::class,
                SupportTicketsOverview::class,
                SubscriptionStatusWidget::class,
                PayrollMassChart::class,
                DepartmentDistributionChart::class,
                LeaveStatusChart::class,
                PayslipStatusChart::class,
                AbsenteeismChart::class,
                SubscriptionUsageChart::class,
                OperationalAlertsWidget::class,
                WorkflowAlertsWidget::class,
                RecentActivityWidget::class,
                AccountWidget::class,
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
