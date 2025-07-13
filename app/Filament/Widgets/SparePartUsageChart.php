<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use App\Models\SparePart;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SparePartUsageChart extends ApexChartWidget
{
    protected static ?string $chartId = 'sparePartsChart';
    protected static ?string $heading = 'Spare Part Usage';

    protected function getOptions(): array
    {
        $data = DB::table('invoice_spare_part')
            ->select('spare_part_id', DB::raw('SUM(quantity) as qty'))
            ->groupBy('spare_part_id')
            ->orderByDesc('qty')
            ->limit(10)
            ->get();

        $labels = SparePart::whereIn('id', $data->pluck('spare_part_id'))->pluck('name_en', 'id');

        return [
            'chart' => ['type' => 'bar'],
            'series' => [[
                'name' => 'Qty Used',
                'data' => $data->pluck('qty')->toArray(),
            ]],
            'xaxis' => ['categories' => $data->pluck('spare_part_id')->map(fn($id) => $labels[$id])->toArray()],
        ];
    }
}
