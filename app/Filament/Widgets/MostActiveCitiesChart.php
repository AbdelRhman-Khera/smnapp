<?php

namespace App\Filament\Widgets;

use App\Models\MaintenanceRequest;
use App\Filament\Support\PermissionedApexChartWidget;

class MostActiveCitiesChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'activeCitiesChart';
    protected static ?string $heading = 'Most Active Cities';

    protected function getOptions(): array
    {
        $data = $this->applyDateFilter(
            MaintenanceRequest::join('addresses', 'maintenance_requests.address_id', '=', 'addresses.id')
                ->join('cities', 'addresses.city_id', '=', 'cities.id'),
            'maintenance_requests.created_at'
        )
            ->selectRaw('cities.name_en as city, COUNT(*) as total')
            ->groupBy('cities.name_en')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'chart' => ['type' => 'bar', 'height' => 320],
            'series' => [[
                'name' => 'Requests',
                'data' => $data->pluck('total')->toArray(),
            ]],
            'xaxis' => ['categories' => $data->pluck('city')->toArray()],
            'colors' => ['#3b82f6'],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '55%']],
            'dataLabels' => ['enabled' => false],
        ];
    }
}
