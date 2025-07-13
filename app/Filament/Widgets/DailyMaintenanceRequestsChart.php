<?php

namespace App\Filament\Widgets;

use App\Models\MaintenanceRequest;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class DailyMaintenanceRequestsChart extends ApexChartWidget
{
    protected static ?string $chartId = 'dailyRequestsChart';
    protected static ?string $heading = 'Daily Maintenance Requests';

    protected function getOptions(): array
    {
        $data = MaintenanceRequest::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->pluck('count', 'date');

        return [
            'chart' => ['type' => 'line'],
            'series' => [[
                'name' => 'Requests',
                'data' => array_values($data->toArray()),
            ]],
            'xaxis' => ['categories' => array_keys($data->toArray())],
        ];
    }
}
