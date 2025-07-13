<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RequestsByCityChart extends ApexChartWidget
{
    protected static ?string $chartId = 'requestsByCityChart';
    protected static ?string $heading = 'Requests by City';

    protected function getOptions(): array
    {
        $data = DB::table('maintenance_requests')
            ->join('addresses', 'maintenance_requests.address_id', '=', 'addresses.id')
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
