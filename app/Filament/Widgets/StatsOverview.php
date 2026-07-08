<?php

namespace App\Filament\Widgets;

use App\Models\Feedback;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\TechnicianEarning;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = -2;

    public static function canView(): bool
    {
        return auth()->user()?->can('widget_' . class_basename(static::class)) ?? false;
    }

    protected function getStats(): array
    {
        $monthRevenue = (float) Invoice::where('status', 'completed')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('total');

        $lastMonthRevenue = (float) Invoice::where('status', 'completed')
            ->whereYear('created_at', now()->subMonthNoOverflow()->year)
            ->whereMonth('created_at', now()->subMonthNoOverflow()->month)
            ->sum('total');

        $requestsToday = MaintenanceRequest::whereDate('created_at', today())->count();

        $openRequests = MaintenanceRequest::whereNotIn('last_status', ['completed', 'canceled'])->count();

        $avgRating = (float) Feedback::avg('rating');

        $pendingDues = (float) TechnicianEarning::whereIn('status', ['pending', 'requested'])->sum('amount');

        return [
            Stat::make('Revenue This Month', number_format($monthRevenue, 2) . ' SAR')
                ->description($lastMonthRevenue > 0
                    ? round((($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1) . '% vs last month'
                    : 'No revenue last month')
                ->descriptionIcon($monthRevenue >= $lastMonthRevenue ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($monthRevenue >= $lastMonthRevenue ? 'success' : 'danger'),

            Stat::make('Requests Today', $requestsToday)
                ->description('Created today')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('primary'),

            Stat::make('Open Requests', $openRequests)
                ->description('Not completed or canceled')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Average Rating', $avgRating ? number_format($avgRating, 1) . ' / 5' : '-')
                ->description('All customer feedback')
                ->descriptionIcon('heroicon-m-star')
                ->color($avgRating >= 4 ? 'success' : ($avgRating >= 3 ? 'warning' : 'danger')),

            Stat::make('Pending Technician Dues', number_format($pendingDues, 2) . ' SAR')
                ->description('Unpaid freelancer earnings')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
        ];
    }
}
