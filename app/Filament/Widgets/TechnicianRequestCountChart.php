<?php

namespace App\Filament\Widgets;

use App\Models\Technician;
use App\Filament\Support\PermissionedApexChartWidget;

class TechnicianRequestCountChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'technicianRequestCountChart';
    protected static ?string $heading = 'Requests per Technician';

    protected int|string|array $columnSpan = 'full';

    protected function getOptions(): array
    {
        $data = Technician::withCount([
            'maintenanceRequests' => fn ($query) => $this->applyDateFilter($query, 'maintenance_requests.created_at'),
        ])
            ->orderByDesc('maintenance_requests_count')
            ->limit(10)
            ->get();

        return [
            'chart' => ['type' => 'bar', 'height' => 320],
            'series' => [[
                'name' => 'Requests',
                'data' => $data->pluck('maintenance_requests_count')->toArray(),
            ]],
            'xaxis' => ['categories' => $data->map(fn (Technician $technician): string => trim($technician->first_name . ' ' . $technician->last_name))->toArray()],
            'colors' => ['#0d9488'],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '55%']],
            'dataLabels' => ['enabled' => false],
        ];
    }
}
