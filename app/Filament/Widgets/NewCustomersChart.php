<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class NewCustomersChart extends ApexChartWidget
{
    protected static ?string $chartId = 'newCustomersChart';
    protected static ?string $heading = 'New Customers per Month';

    protected function getOptions(): array
    {
        $data = Customer::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->pluck('count', 'month');

        return [
            'chart' => ['type' => 'line'],
            'series' => [[
                'name' => 'Customers',
                'data' => array_values($data->toArray()),
            ]],
            'xaxis' => ['categories' => array_keys($data->toArray())],
        ];
    }
}
