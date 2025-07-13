<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class MonthlyRevenueChart extends ApexChartWidget
{
    protected static ?string $chartId = 'monthlyRevenueChart';
    protected static ?string $heading = 'Monthly Revenue';

    protected function getOptions(): array
    {
        $data = Invoice::selectRaw('MONTH(created_at) as month, SUM(total) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        return [
            'chart' => ['type' => 'area'],
            'series' => [[
                'name' => 'Revenue',
                'data' => array_values($data->toArray()),
            ]],
            'xaxis' => ['categories' => array_keys($data->toArray())],
        ];
    }
}
