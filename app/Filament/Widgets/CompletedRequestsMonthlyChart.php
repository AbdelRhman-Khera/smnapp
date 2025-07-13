<?php

namespace App\Filament\Widgets;

use App\Models\MaintenanceRequest;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CompletedRequestsMonthlyChart extends ApexChartWidget
{
    protected static ?string $chartId = 'completedRequestsMonthly';
    protected static ?string $heading = 'Completed Requests per Month';

    protected function getOptions(): array
    {
        $data = MaintenanceRequest::where('last_status', 'completed')
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->pluck('count', 'month');

        return [
            'chart' => ['type' => 'area'],
            'series' => [[
                'name' => 'Completed',
                'data' => array_values($data->toArray()),
            ]],
            'xaxis' => ['categories' => array_keys($data->toArray())],
        ];
    }
}
