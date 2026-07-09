<?php

namespace App\Filament\Widgets;

use App\Models\MaintenanceRequest;
use App\Filament\Support\PermissionedApexChartWidget;

class CompletedRequestsMonthlyChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'completedRequestsMonthly';
    protected static ?string $heading = 'Completed Requests per Month';

    protected function getOptions(): array
    {
        $data = $this->applyDateFilter(MaintenanceRequest::where('last_status', 'completed'))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        return [
            'chart' => ['type' => 'area', 'height' => 320],
            'series' => [[
                'name' => 'Completed',
                'data' => array_values($data->toArray()),
            ]],
            'xaxis' => ['categories' => array_keys($data->toArray())],
            'colors' => ['#22c55e'],
            'stroke' => ['curve' => 'smooth', 'width' => 3],
            'dataLabels' => ['enabled' => false],
        ];
    }
}
