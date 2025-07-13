<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class MostActiveDistrictsChart extends ApexChartWidget
{
    protected static ?string $chartId = 'activeDistrictsChart';
    protected static ?string $heading = 'Most Active Districts';

    protected function getOptions(): array
    {
        $data = DB::table('maintenance_requests')
            ->join('addresses', 'maintenance_requests.address_id', '=', 'addresses.id')
            ->join('districts', 'addresses.district_id', '=', 'districts.id')
            ->selectRaw('districts.name_en as district, COUNT(*) as total')
            ->groupBy('districts.name_en')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'chart' => ['type' => 'bar'],
            'series' => [[
                'name' => 'Requests',
                'data' => $data->pluck('total')->toArray(),
            ]],
            'xaxis' => ['categories' => $data->pluck('district')->toArray()],
        ];
        // return [
        //     'chart' => ['type' => 'bar'],
        //     'series' => [['name' => 'Test', 'data' => [10, 20, 30]]],
        //     'xaxis' => ['categories' => ['A', 'B', 'C']],
        // ];
    }
}
