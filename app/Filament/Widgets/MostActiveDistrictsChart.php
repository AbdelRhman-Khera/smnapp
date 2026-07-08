<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use App\Filament\Support\PermissionedApexChartWidget;

class MostActiveDistrictsChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'activeDistrictsChart';
    protected static ?string $heading = 'Most Active Districts';

    protected function getOptions(): array
    {
        $data = $this->applyDateFilter(
            DB::table('maintenance_requests')
                ->join('addresses', 'maintenance_requests.address_id', '=', 'addresses.id')
                ->join('districts', 'addresses.district_id', '=', 'districts.id'),
            'maintenance_requests.created_at'
        )
            ->selectRaw('districts.name_en as district, COUNT(*) as total')
            ->groupBy('districts.name_en')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'chart' => ['type' => 'bar', 'height' => 320],
            'series' => [[
                'name' => 'Requests',
                'data' => $data->pluck('total')->toArray(),
            ]],
            'xaxis' => ['categories' => $data->pluck('district')->toArray()],
            'colors' => ['#6366f1'],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '55%']],
            'dataLabels' => ['enabled' => false],
        ];
    }
}
