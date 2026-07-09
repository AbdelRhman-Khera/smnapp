<?php

namespace App\Filament\Widgets;

use App\Models\MaintenanceRequest;
use App\Filament\Support\PermissionedApexChartWidget;

class FreelancerClaimChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'freelancerClaimChart';
    protected static ?string $heading = 'Freelancer Requests: Opened vs Claimed';

    protected function getOptions(): array
    {
        $opened = $this->applyDateFilter(
            MaintenanceRequest::whereNotNull('opened_for_freelancers_at'),
            'opened_for_freelancers_at'
        )
            ->selectRaw("DATE_FORMAT(opened_for_freelancers_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $claimed = $this->applyDateFilter(
            MaintenanceRequest::whereNotNull('opened_for_freelancers_at')->whereNotNull('technician_id'),
            'opened_for_freelancers_at'
        )
            ->selectRaw("DATE_FORMAT(opened_for_freelancers_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $months = $opened->keys();

        return [
            'chart' => ['type' => 'bar', 'height' => 320],
            'series' => [
                [
                    'name' => 'Opened',
                    'data' => $months->map(fn (string $month): int => (int) $opened[$month])->values()->toArray(),
                ],
                [
                    'name' => 'Claimed',
                    'data' => $months->map(fn (string $month): int => (int) ($claimed[$month] ?? 0))->values()->toArray(),
                ],
            ],
            'xaxis' => ['categories' => $months->values()->toArray()],
            'colors' => ['#64748b', '#22c55e'],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '60%']],
            'dataLabels' => ['enabled' => false],
        ];
    }
}
