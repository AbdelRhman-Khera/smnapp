<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use App\Models\Product;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TopProductsChart extends ApexChartWidget
{
    protected static ?string $chartId = 'topProductsChart';
    protected static ?string $heading = 'Top Products';

    protected function getOptions(): array
    {
        $data = DB::table('maintenance_request_product')
            ->select('product_id', DB::raw('COUNT(*) as count'))
            ->groupBy('product_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $labels = Product::whereIn('id', $data->pluck('product_id'))->pluck('name_en', 'id');

        return [
            'chart' => ['type' => 'bar'],
            'series' => [[
                'name' => 'Requests',
                'data' => $data->pluck('count')->toArray(),
            ]],
            'xaxis' => ['categories' => $data->pluck('product_id')->map(fn($id) => $labels[$id])->toArray()],
        ];
    }
}
