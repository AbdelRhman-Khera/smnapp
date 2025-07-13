<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class MonthlyInvoicesChart extends ApexChartWidget
{
    protected static ?string $chartId = 'monthlyInvoicesChart';
    protected static ?string $heading = 'Invoices per Month';

    protected function getOptions(): array
    {
        $data = Invoice::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->pluck('count', 'month');

        return [
            'chart' => ['type' => 'bar'],
            'series' => [[
                'name' => 'Invoices',
                'data' => array_values($data->toArray()),
            ]],
            'xaxis' => ['categories' => array_keys($data->toArray())],
        ];
    }
}
