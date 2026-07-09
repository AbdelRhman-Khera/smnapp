<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Filament\Support\PermissionedApexChartWidget;

class MonthlyRevenueChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'monthlyRevenueChart';
    protected static ?string $heading = 'Monthly Revenue';

    protected int|string|array $columnSpan = 'full';

    protected function getOptions(): array
    {
        $data = $this->applyDateFilter(Invoice::query())
            ->where('status', 'completed')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->map(fn ($total): float => round((float) $total, 2));

        return [
            'chart' => ['type' => 'area', 'height' => 320],
            'series' => [[
                'name' => 'Revenue (SAR)',
                'data' => array_values($data->toArray()),
            ]],
            'xaxis' => ['categories' => array_keys($data->toArray())],
            'colors' => ['#16a34a'],
            'stroke' => ['curve' => 'smooth', 'width' => 3],
            'dataLabels' => ['enabled' => false],
        ];
    }
}
