<?php

namespace App\Filament\Widgets;

use App\Models\City;
use App\Models\MaintenanceRequest;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class MostActiveCitiesChart extends ApexChartWidget
{
    protected static ?string $chartId = 'activeCitiesChart';
    protected static ?string $heading = 'Most Active Cities';

    protected function getOptions(): array
    {
        // Join to get city_id from addresses table
        $data = MaintenanceRequest::join('addresses', 'maintenance_requests.address_id', '=', 'addresses.id')
            ->join('cities', 'addresses.city_id', '=', 'cities.id')
            ->selectRaw('cities.name_en as city, COUNT(*) as total')
            ->groupBy('cities.name_en')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'chart' => ['type' => 'bar'],
            'series' => [[
                'name' => 'Requests',
                'data' => $data->pluck('total')->toArray(),
            ]],
            'xaxis' => ['categories' => $data->pluck('city')->toArray()],
        ];
    }
}
