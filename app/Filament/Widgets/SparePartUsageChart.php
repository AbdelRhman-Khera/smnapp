<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use App\Models\SparePart;
use App\Filament\Support\PermissionedApexChartWidget;

class SparePartUsageChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'sparePartsChart';
    protected static ?string $heading = 'Spare Part Usage';

    protected function getOptions(): array
    {
        $data = $this->applyDateFilter(
            DB::table('invoice_spare_part')
                ->join('invoices', 'invoice_spare_part.invoice_id', '=', 'invoices.id'),
            'invoices.created_at'
        )
            ->select('invoice_spare_part.spare_part_id', DB::raw('SUM(invoice_spare_part.quantity) as qty'))
            ->groupBy('invoice_spare_part.spare_part_id')
            ->orderByDesc('qty')
            ->limit(10)
            ->get();

        $labels = SparePart::whereIn('id', $data->pluck('spare_part_id'))->pluck('name_en', 'id');

        return [
            'chart' => ['type' => 'bar', 'height' => 320],
            'series' => [[
                'name' => 'Qty Used',
                'data' => $data->pluck('qty')->map(fn ($qty): int => (int) $qty)->toArray(),
            ]],
            'xaxis' => ['categories' => $data->pluck('spare_part_id')->map(fn ($id) => $labels[$id] ?? ('#' . $id))->toArray()],
            'colors' => ['#ef4444'],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'horizontal' => true]],
            'dataLabels' => ['enabled' => false],
        ];
    }
}
