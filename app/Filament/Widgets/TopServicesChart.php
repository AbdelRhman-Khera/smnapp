<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use App\Models\Service;
use App\Filament\Support\PermissionedApexChartWidget;

class TopServicesChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'topServicesChart';
    protected static ?string $heading = 'Top Services';

    protected function getOptions(): array
    {
        $data = $this->applyDateFilter(
            DB::table('invoice_service')
                ->join('invoices', 'invoice_service.invoice_id', '=', 'invoices.id'),
            'invoices.created_at'
        )
            ->select('invoice_service.service_id', DB::raw('COUNT(*) as count'))
            ->groupBy('invoice_service.service_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $labels = Service::whereIn('id', $data->pluck('service_id'))->pluck('name_en', 'id');

        return [
            'chart' => ['type' => 'bar', 'height' => 320],
            'series' => [[
                'name' => 'Services',
                'data' => $data->pluck('count')->toArray(),
            ]],
            'xaxis' => ['categories' => $data->pluck('service_id')->map(fn ($id) => $labels[$id] ?? ('#' . $id))->toArray()],
            'colors' => ['#8b5cf6'],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'horizontal' => true]],
            'dataLabels' => ['enabled' => false],
        ];
    }
}
