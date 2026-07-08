<?php

namespace App\Filament\Widgets;

use App\Models\MaintenanceRequest;
use App\Filament\Support\PermissionedApexChartWidget;

class CancelledRequestsChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'cancelledRequestsChart';
    protected static ?string $heading = 'Cancelled Requests & Cancellation Rate';

    protected function getOptions(): array
    {
        $totals = $this->applyDateFilter(MaintenanceRequest::query())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $cancelled = $this->applyDateFilter(MaintenanceRequest::where('last_status', 'canceled'))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $months = $totals->keys();

        $cancelledCounts = $months->map(fn (string $month): int => (int) ($cancelled[$month] ?? 0));
        $rates = $months->map(fn (string $month): float => $totals[$month] > 0
            ? round((($cancelled[$month] ?? 0) / $totals[$month]) * 100, 1)
            : 0);

        return [
            'chart' => ['type' => 'line', 'height' => 320],
            'series' => [
                [
                    'name' => 'Cancelled',
                    'type' => 'column',
                    'data' => $cancelledCounts->values()->toArray(),
                ],
                [
                    'name' => 'Cancellation Rate %',
                    'type' => 'line',
                    'data' => $rates->values()->toArray(),
                ],
            ],
            'xaxis' => ['categories' => $months->values()->toArray()],
            'yaxis' => [
                ['title' => ['text' => 'Cancelled']],
                ['opposite' => true, 'title' => ['text' => 'Rate %'], 'max' => 100],
            ],
            'colors' => ['#ef4444', '#f59e0b'],
            'stroke' => ['curve' => 'smooth', 'width' => [0, 3]],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '55%']],
            'dataLabels' => ['enabled' => false],
        ];
    }
}
