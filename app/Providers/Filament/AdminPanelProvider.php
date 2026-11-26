<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\MadrasahQuickActions;
use App\Filament\Widgets\MadrasahStatsOverview;
use App\Filament\Widgets\TeacherAttendanceChart;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Widgets\StudentsByDepartmentChart;
use Filament\Navigation\NavigationGroup;


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
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
            NavigationGroup::make('শিক্ষার্থী ও ভর্তি'),
            NavigationGroup::make('একাডেমিক ও পাঠদান'),
            NavigationGroup::make('হাজিরা ব্যবস্থাপনা'),
            NavigationGroup::make('পরীক্ষা ও ফলাফল'),
            NavigationGroup::make('হিসাব ও ফাইন্যান্স'),
            NavigationGroup::make('ব্যবহারকারী ও নিরাপত্তা'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
           ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
           ->widgets([
            MadrasahQuickActions::class,
            MadrasahStatsOverview::class,
            TeacherAttendanceChart::class,
            StudentsByDepartmentChart::class,
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
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}