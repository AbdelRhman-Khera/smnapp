<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Filament\Support\PermissionedApexChartWidget;

class NewCustomersChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'newCustomersChart';
    protected static ?string $heading = 'New Customers per Month';

    protected function getOptions(): array
    {
        $data = $this->applyDateFilter(Customer::query())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        return [
            'chart' => ['type' => 'line', 'height' => 320],
            'series' => [[
                'name' => 'Customers',
                'data' => array_values($data->toArray()),
            ]],
            'xaxis' => ['categories' => array_keys($data->toArray())],
            'colors' => ['#ec4899'],
            'stroke' => ['curve' => 'smooth', 'width' => 3],
            'dataLabels' => ['enabled' => false],
        ];
    }
}
