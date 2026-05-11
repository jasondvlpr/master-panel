<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
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
            ->brandName('InfraPanel')
            ->font('Outfit')
            ->font('Outfit')
            ->renderHook(
                'panels::head.end',
                fn (): string => '
                    <style>
                        /* 1. Global Composition (Purple, Blue, White) */
                        :root {
                            --sidebar-bg: #ffffff;
                            --content-bg: #f8faff; /* Very subtle blue tint */
                            --card-bg: #ffffff;
                            --border-subtle: #e2e8f0;
                            --text-soft: #64748b;
                            --theme-gradient: linear-gradient(135deg, #8b5cf6 0%, #3b82f6 100%); /* Violet to Blue */
                        }
                        
                        .dark {
                            --sidebar-bg: #0f172a;
                            --content-bg: #020617;
                            --card-bg: #0f172a;
                            --border-subtle: #1e293b;
                            --text-soft: #94a3b8;
                            --theme-gradient: linear-gradient(135deg, #7c3aed 0%, #2563eb 100%);
                        }

                        .fi-main { 
                            background-color: var(--content-bg) !important; 
                            font-family: "Outfit", sans-serif !important; 
                        }

                        /* Sidebar Refinement */
                        .fi-sidebar { 
                            background-color: var(--sidebar-bg) !important; 
                            border-right: 1px solid var(--border-subtle) !important;
                            box-shadow: 1px 0 10px rgba(0,0,0,0.01) !important;
                        }
                        
                        /* Refine Active Menu Items in Sidebar */
                        .fi-sidebar-item-active > a {
                            background: linear-gradient(90deg, rgba(139, 92, 246, 0.1) 0%, transparent 100%) !important;
                            border-left: 3px solid #8b5cf6 !important;
                            border-radius: 0 8px 8px 0 !important;
                        }
                        
                        .fi-sidebar-item-active > a * {
                            color: #7c3aed !important;
                            font-weight: 700 !important;
                        }


                        /* 2. Modern Clean Tables (SaaS Style) */
                        .fi-ta-ctn { 
                            background: var(--card-bg) !important; 
                            border: 1px solid var(--border-subtle) !important; 
                            border-radius: 16px !important;
                            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.05), 0 4px 6px -2px rgba(59, 130, 246, 0.02) !important; /* Soft blue shadow */
                        }

                        .fi-ta-table { 
                            border-collapse: separate !important; 
                            border-spacing: 0 !important;
                        }

                        .fi-ta-header-cell {
                            background: transparent !important;
                            border-bottom: 1px solid var(--border-subtle) !important;
                            padding: 16px 24px !important;
                            text-transform: uppercase !important;
                            letter-spacing: 0.05em !important;
                            font-size: 0.75rem !important;
                            color: var(--text-soft) !important;
                        }

                        .fi-ta-record { 
                            background: transparent !important;
                            transition: all 0.2s ease !important;
                        }

                        .fi-ta-record:hover { 
                            background: #f8fafc !important;
                        }

                        .fi-ta-cell { 
                            border: none !important;
                            border-bottom: 1px solid var(--border-subtle) !important; 
                            padding: 16px 24px !important; 
                        }
                        
                        /* Remove bottom border on last row */
                        .fi-ta-record:last-child .fi-ta-cell {
                            border-bottom: none !important;
                        }

                        /* 3. Button & Input Polishing */
                        .fi-btn-primary {
                            background: var(--theme-gradient) !important;
                            border: none !important;
                            color: white !important;
                        }
                        
                        .fi-btn-primary:hover {
                            opacity: 0.9 !important;
                        }

                        .fi-btn { 
                            border-radius: 9999px !important; /* Pill shape for modern look */
                            font-weight: 600 !important; 
                            transition: all 0.2s !important;
                        }
                        
                        .fi-btn:active {
                            transform: scale(0.95) !important;
                        }
                        
                        .fi-input, .fi-select {
                            border-radius: 12px !important;
                            border: 1px solid var(--border-subtle) !important;
                            box-shadow: 0 1px 2px rgba(0,0,0,0.01) inset !important;
                        }
                        
                        .fi-input:focus, .fi-select:focus {
                            border-color: #8b5cf6 !important;
                            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1) !important;
                        }
                    </style>
                ',
            )
            ->colors([
                'primary' => Color::Violet,
                'info' => Color::Blue,
                'gray' => Color::Slate,
            ])
            ->navigationGroups([
                'Tenant Management',
                'Network & DNS',
                'Infrastructure',
                'System Control',
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
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
