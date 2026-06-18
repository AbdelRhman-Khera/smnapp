<?php

namespace App\Filament\Widgets;

use App\Models\MaintenanceRequest;
use App\Filament\Support\PermissionedApexChartWidget;

class MaintenanceRequestTypesChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'requestTypesChart';
    protected static ?string $heading = 'Request Types';

    protected function getOptions(): array
    {
        $types = ['new_installation', 'regular_maintenance', 'emergency_maintenance', 'warranty'];
        $series = collect($types)->map(fn($type) => MaintenanceRequest::where('type', $type)->count());

        return [
            'chart' => ['type' => 'pie'],
            'series' => $series->toArray(),
            'labels' => $types,
        ];
    }
}
