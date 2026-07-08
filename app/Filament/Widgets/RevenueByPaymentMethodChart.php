<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Filament\Support\PermissionedApexChartWidget;

class RevenueByPaymentMethodChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'revenueByPaymentMethodChart';
    protected static ?string $heading = 'Revenue by Payment Method';

    protected function getOptions(): array
    {
        $data = $this->applyDateFilter(Invoice::query())
            ->where('status', 'completed')
            ->selectRaw("COALESCE(payment_method, 'unknown') as method, SUM(total) as total")
            ->groupBy('method')
            ->orderByDesc('total')
            ->pluck('total', 'method');

        return [
            'chart' => ['type' => 'donut', 'height' => 320],
            'series' => $data->values()->map(fn ($total): float => round((float) $total, 2))->toArray(),
            'labels' => $data->keys()->map(fn (string $method): string => ucwords(str_replace('_', ' ', $method)))->toArray(),
            'colors' => ['#1C4199', '#22c55e', '#f59e0b', '#8b5cf6', '#ef4444', '#64748b'],
            'legend' => ['position' => 'bottom'],
        ];
    }
}
