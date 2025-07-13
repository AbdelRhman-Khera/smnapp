<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use App\Models\Service;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TopServicesChart extends ApexChartWidget
{
    protected static ?string $chartId = 'topServicesChart';
    protected static ?string $heading = 'Top Services';

    protected function getOptions(): array
    {
        $data = DB::table('invoice_service')
            ->select('service_id', DB::raw('COUNT(*) as count'))
            ->groupBy('service_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $labels = Service::whereIn('id', $data->pluck('service_id'))->pluck('name_en', 'id');

        return [
            'chart' => ['type' => 'bar'],
            'series' => [[
                'name' => 'Services',
                'data' => $data->pluck('count')->toArray(),
            ]],
            'xaxis' => ['categories' => $data->pluck('service_id')->map(fn($id) => $labels[$id])->toArray()],
        ];
    }
}
