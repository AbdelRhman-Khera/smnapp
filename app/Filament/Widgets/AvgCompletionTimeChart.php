<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class AvgCompletionTimeChart extends ApexChartWidget
{
    protected static ?string $chartId = 'avgCompletionTimeChart';
    protected static ?string $heading = 'Average Completion Time (Hours)';

    protected function getOptions(): array
    {
        $data = DB::table('maintenance_requests')
            ->selectRaw('DATE(created_at) as date, AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours')
            ->groupBy('date')
            ->limit(30)
            ->pluck('avg_hours', 'date');

        return [
            'chart' => ['type' => 'line'],
            'series' => [[
                'name' => 'Avg Hours',
                'data' => array_values($data->toArray()),
            ]],
            'xaxis' => ['categories' => array_keys($data->toArray())],
        ];
    }
}
