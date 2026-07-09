<?php

namespace App\Filament\Widgets;

use App\Models\MaintenanceRequest;
use App\Filament\Support\PermissionedApexChartWidget;

class CompletedPendingRequestsChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'completedPendingRequests';
    protected static ?string $heading = 'Requests by Status';

    protected function getOptions(): array
    {
        $data = $this->applyDateFilter(MaintenanceRequest::query())
            ->selectRaw('last_status, COUNT(*) as count')
            ->whereNotNull('last_status')
            ->groupBy('last_status')
            ->orderByDesc('count')
            ->pluck('count', 'last_status');

        return [
            'chart' => ['type' => 'donut', 'height' => 320],
            'series' => array_values($data->toArray()),
            'labels' => $data->keys()
                ->map(fn (string $status): string => ucwords(str_replace('_', ' ', $status)))
                ->toArray(),
            'colors' => [
                '#22c55e', '#1C4199', '#f59e0b', '#ef4444', '#8b5cf6',
                '#0ea5e9', '#ec4899', '#14b8a6', '#eab308', '#6366f1',
                '#f97316', '#64748b',
            ],
            'legend' => ['position' => 'bottom'],
        ];
    }
}
