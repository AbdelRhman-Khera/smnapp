<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Filament\Support\PermissionedApexChartWidget;

class TopProductsChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'topProductsChart';
    protected static ?string $heading = 'Top Products';

    protected function getOptions(): array
    {
        $data = $this->applyDateFilter(
            DB::table('maintenance_request_product'),
            'maintenance_request_product.created_at'
        )
            ->select('product_id', DB::raw('COUNT(*) as count'))
            ->groupBy('product_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $labels = Product::whereIn('id', $data->pluck('product_id'))->pluck('name_en', 'id');

        return [
            'chart' => ['type' => 'bar', 'height' => 320],
            'series' => [[
                'name' => 'Requests',
                'data' => $data->pluck('count')->toArray(),
            ]],
            'xaxis' => ['categories' => $data->pluck('product_id')->map(fn ($id) => $labels[$id] ?? ('#' . $id))->toArray()],
            'colors' => ['#f59e0b'],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'horizontal' => true]],
            'dataLabels' => ['enabled' => false],
        ];
    }
}
