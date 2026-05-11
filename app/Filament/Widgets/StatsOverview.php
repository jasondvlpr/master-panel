<?php

namespace App\Filament\Widgets;

use App\Models\Tenant;
use App\Models\CloudflareDomain;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Clients', Tenant::count())
                ->description('Active tenants in system')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            Stat::make('Total Domains', CloudflareDomain::count())
                ->description('Synced from Cloudflare')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('info'),
        ];
    }
}
