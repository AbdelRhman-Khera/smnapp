<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RequestsPerSlotChart extends ApexChartWidget
{
    protected static ?string $chartId = 'requestsPerSlotChart';
    protected static ?string $heading = 'Requests per Slot';

    protected function getOptions(): array
    {
        $data = DB::table('maintenance_requests')
            ->join('slots', 'maintenance_requests.slot_id', '=', 'slots.id')
            ->selectRaw("CONCAT(slots.date, ' ', slots.time) as slot_time, COUNT(*) as total")
            ->groupBy('slot_time')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'chart' => ['type' => 'bar'],
            'series' => [[
                'name' => 'Requests',
                'data' => $data->pluck('total')->toArray(),
            ]],
            'xaxis' => ['categories' => $data->pluck('slot_time')->toArray()],
        ];
    }
}
