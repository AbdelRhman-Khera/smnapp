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
        $types = [
            'new_installation' => 'New Installation',
            'regular_maintenance' => 'Regular Maintenance',
            'emergency_maintenance' => 'Emergency Maintenance',
            'warranty' => 'Warranty',
        ];

        $series = collect($types)
            ->map(fn (string $label, string $type): int => $this->applyDateFilter(MaintenanceRequest::where('type', $type))->count());

        return [
            'chart' => ['type' => 'pie', 'height' => 320],
            'series' => $series->values()->toArray(),
            'labels' => array_values($types),
            'colors' => ['#1C4199', '#22c55e', '#ef4444', '#f59e0b'],
            'legend' => ['position' => 'bottom'],
        ];
    }
}
