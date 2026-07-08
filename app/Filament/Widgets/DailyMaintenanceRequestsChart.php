<?php

namespace App\Filament\Widgets;

use App\Models\MaintenanceRequest;
use App\Filament\Support\PermissionedApexChartWidget;

class DailyMaintenanceRequestsChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'dailyRequestsChart';
    protected static ?string $heading = 'Daily Maintenance Requests';

    protected int|string|array $columnSpan = 'full';

    protected function getOptions(): array
    {
        $query = MaintenanceRequest::selectRaw('DATE(created_at) as date, COUNT(*) as count');

        if (blank($this->filterFormData['date_start'] ?? null) && blank($this->filterFormData['date_end'] ?? null)) {
            $query->whereDate('created_at', '>=', now()->subDays(30));
        }

        $data = $this->applyDateFilter($query)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        return [
            'chart' => ['type' => 'area', 'height' => 320],
            'series' => [[
                'name' => 'Requests',
                'data' => array_values($data->toArray()),
            ]],
            'xaxis' => ['categories' => array_keys($data->toArray())],
            'colors' => ['#1C4199'],
            'stroke' => ['curve' => 'smooth', 'width' => 3],
            'dataLabels' => ['enabled' => false],
        ];
    }
}
