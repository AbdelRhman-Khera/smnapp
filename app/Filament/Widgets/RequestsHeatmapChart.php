<?php

namespace App\Filament\Widgets;

use App\Models\MaintenanceRequest;
use App\Filament\Support\PermissionedApexChartWidget;

class RequestsHeatmapChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'requestsHeatmapChart';
    protected static ?string $heading = 'Requests Heatmap (Day / Hour)';

    protected int|string|array $columnSpan = 'full';

    protected function getOptions(): array
    {
        // DAYOFWEEK(): 1 = Sunday ... 7 = Saturday
        $days = [1 => 'Sunday', 2 => 'Monday', 3 => 'Tuesday', 4 => 'Wednesday', 5 => 'Thursday', 6 => 'Friday', 7 => 'Saturday'];

        $counts = $this->applyDateFilter(MaintenanceRequest::query())
            ->selectRaw('DAYOFWEEK(created_at) as day, HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('day', 'hour')
            ->get()
            ->groupBy('day');

        $series = collect($days)->map(function (string $dayName, int $dayNumber) use ($counts): array {
            $hourly = ($counts[$dayNumber] ?? collect())->keyBy('hour');

            return [
                'name' => $dayName,
                'data' => collect(range(0, 23))->map(fn (int $hour): array => [
                    'x' => sprintf('%02d:00', $hour),
                    'y' => (int) ($hourly[$hour]->count ?? 0),
                ])->toArray(),
            ];
        })->values()->toArray();

        return [
            'chart' => ['type' => 'heatmap', 'height' => 380],
            'series' => $series,
            'colors' => ['#1C4199'],
            'dataLabels' => ['enabled' => false],
            'plotOptions' => [
                'heatmap' => [
                    'shadeIntensity' => 0.6,
                    'radius' => 3,
                ],
            ],
        ];
    }
}
