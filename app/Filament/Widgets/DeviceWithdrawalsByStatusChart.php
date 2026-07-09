<?php

namespace App\Filament\Widgets;

use App\Models\DeviceWithdrawalRequest;
use App\Filament\Support\PermissionedApexChartWidget;

class DeviceWithdrawalsByStatusChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'deviceWithdrawalsByStatusChart';
    protected static ?string $heading = 'Device Withdrawals by Status';

    protected function getOptions(): array
    {
        $data = $this->applyDateFilter(DeviceWithdrawalRequest::query())
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->orderByDesc('count')
            ->pluck('count', 'status');

        return [
            'chart' => ['type' => 'donut', 'height' => 320],
            'series' => array_values($data->toArray()),
            'labels' => $data->keys()
                ->map(fn (string $status): string => ucwords(str_replace('_', ' ', $status)))
                ->toArray(),
            'colors' => [
                '#1C4199', '#0ea5e9', '#f59e0b', '#8b5cf6', '#22c55e',
                '#ef4444', '#14b8a6', '#ec4899', '#eab308', '#64748b',
            ],
            'legend' => ['position' => 'bottom'],
        ];
    }
}
