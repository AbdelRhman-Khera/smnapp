<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Filament\Support\PermissionedApexChartWidget;

class AvgInvoiceValueChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'avgInvoiceValueChart';
    protected static ?string $heading = 'Average Invoice Value per Month';

    protected function getOptions(): array
    {
        $data = $this->applyDateFilter(Invoice::query())
            ->where('status', 'completed')
            ->where('total', '>', 0)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, AVG(total) as avg_total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('avg_total', 'month')
            ->map(fn ($avg): float => round((float) $avg, 2));

        return [
            'chart' => ['type' => 'line', 'height' => 320],
            'series' => [[
                'name' => 'Avg Invoice (SAR)',
                'data' => array_values($data->toArray()),
            ]],
            'xaxis' => ['categories' => array_keys($data->toArray())],
            'colors' => ['#0891b2'],
            'stroke' => ['curve' => 'smooth', 'width' => 3],
            'dataLabels' => ['enabled' => false],
        ];
    }
}
