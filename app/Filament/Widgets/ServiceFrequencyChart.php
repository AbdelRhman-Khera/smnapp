<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use App\Models\Service;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ServiceFrequencyChart extends ApexChartWidget
{
    protected static ?string $chartId = 'serviceFrequencyChart';
    protected static ?string $heading = 'Service Frequency';

    protected function getOptions(): array
    {
        $data = DB::table('invoice_service')
            ->select('service_id', DB::raw('COUNT(*) as count'))
            ->groupBy('service_id')
            ->orderByDesc('count')
            ->limit(10)->get();

        $labels = Service::whereIn('id', $data->pluck('service_id'))->pluck('name_en', 'id');

        return [
            'chart' => ['type' => 'radar'],
            'series' => [[
                'name' => 'Frequency',
                'data' => $data->pluck('count')->toArray(),
            ]],
            'labels' => $data->pluck('service_id')->map(fn($id) => $labels[$id])->toArray(),
        ];
    }
}
