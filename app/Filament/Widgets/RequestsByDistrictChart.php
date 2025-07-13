<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RequestsByDistrictChart extends ApexChartWidget
{
    protected static ?string $chartId = 'requestsByDistrictChart';
    protected static ?string $heading = 'Requests by District';

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
    }
}
