<?php

namespace App\Filament\Widgets;

use App\Models\MaintenanceRequest;
use App\Filament\Support\PermissionedApexChartWidget;

class CompletedPendingRequestsChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'completedPendingRequests';
    protected static ?string $heading = 'Completed vs Pending Requests';

    protected function getOptions(): array
    {
        $completed = MaintenanceRequest::where('last_status', 'completed')->count();
        $pending = MaintenanceRequest::where('last_status', '!=', 'completed')->count();

        return [
            'chart' => ['type' => 'donut'],
            'series' => [$completed, $pending],
            'labels' => ['Completed', 'Pending'],
        ];
    }
}
