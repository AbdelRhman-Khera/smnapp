<?php

namespace App\Filament\Widgets;

use App\Models\MaintenanceRequest;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class MaintenanceRequestTypesChart extends ApexChartWidget
{
    protected static ?string $chartId = 'requestTypesChart';
    protected static ?string $heading = 'Request Types';

    protected function getOptions(): array
    {
        $types = ['new_installation', 'regular_maintenance', 'emergency_maintenance'];
        $series = collect($types)->map(fn($type) => MaintenanceRequest::where('type', $type)->count());

        return [
            'chart' => ['type' => 'pie'],
            'series' => $series->toArray(),
            'labels' => $types,
        ];
    }
}
