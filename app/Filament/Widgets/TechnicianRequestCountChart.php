<?php

namespace App\Filament\Widgets;

use App\Models\Technician;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TechnicianRequestCountChart extends ApexChartWidget
{
    protected static ?string $chartId = 'technicianRequestCountChart';
    protected static ?string $heading = 'Requests per Technician';

    protected function getOptions(): array
    {
        $data = Technician::withCount('maintenanceRequests')
            ->orderByDesc('maintenance_requests_count')
            ->limit(10)
            ->get();

        return [
            'chart' => ['type' => 'bar'],
            'series' => [[
                'name' => 'Requests',
                'data' => $data->pluck('maintenance_requests_count')->toArray(),
            ]],
            'xaxis' => ['categories' => $data->pluck('first_name')->toArray()],
        ];
    }
}
