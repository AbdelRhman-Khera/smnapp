<?php

namespace App\Filament\Widgets;

use App\Models\MaintenanceRequest;
use App\Filament\Support\PermissionedApexChartWidget;

class WarrantyRateChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'warrantyRateChart';
    protected static ?string $heading = 'Warranty Requests & Warranty Rate';

    protected function getOptions(): array
    {
        $totals = $this->applyDateFilter(MaintenanceRequest::query())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $warranty = $this->applyDateFilter(MaintenanceRequest::where('type', 'warranty'))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $months = $totals->keys();

        $warrantyCounts = $months->map(fn (string $month): int => (int) ($warranty[$month] ?? 0));
        $rates = $months->map(fn (string $month): float => $totals[$month] > 0
            ? round((($warranty[$month] ?? 0) / $totals[$month]) * 100, 1)
            : 0);

        return [
            'chart' => ['type' => 'line', 'height' => 320],
            'series' => [
                [
                    'name' => 'Warranty Requests',
                    'type' => 'column',
                    'data' => $warrantyCounts->values()->toArray(),
                ],
                [
                    'name' => 'Warranty Rate %',
                    'type' => 'line',
                    'data' => $rates->values()->toArray(),
                ],
            ],
            'xaxis' => ['categories' => $months->values()->toArray()],
            'yaxis' => [
                ['title' => ['text' => 'Warranty']],
                ['opposite' => true, 'title' => ['text' => 'Rate %'], 'max' => 100],
            ],
            'colors' => ['#8b5cf6', '#f97316'],
            'stroke' => ['curve' => 'smooth', 'width' => [0, 3]],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '55%']],
            'dataLabels' => ['enabled' => false],
        ];
    }
}
