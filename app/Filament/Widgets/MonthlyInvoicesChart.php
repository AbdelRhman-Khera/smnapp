<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Filament\Support\PermissionedApexChartWidget;

class MonthlyInvoicesChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'monthlyInvoicesChart';
    protected static ?string $heading = 'Invoices per Month';

    protected function getOptions(): array
    {
        $data = $this->applyDateFilter(Invoice::query())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        return [
            'chart' => ['type' => 'bar', 'height' => 320],
            'series' => [[
                'name' => 'Invoices',
                'data' => array_values($data->toArray()),
            ]],
            'xaxis' => ['categories' => array_keys($data->toArray())],
            'colors' => ['#0ea5e9'],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '55%']],
            'dataLabels' => ['enabled' => false],
        ];
    }
}
