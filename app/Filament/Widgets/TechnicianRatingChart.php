<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use App\Filament\Support\PermissionedApexChartWidget;

class TechnicianRatingChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'technicianRatingChart';
    protected static ?string $heading = 'Average Rating per Technician (Top 10)';

    protected function getOptions(): array
    {
        $data = $this->applyDateFilter(
            DB::table('feedback')
                ->join('maintenance_requests', 'feedback.maintenance_request_id', '=', 'maintenance_requests.id')
                ->join('technicians', 'maintenance_requests.technician_id', '=', 'technicians.id'),
            'feedback.created_at'
        )
            ->selectRaw("CONCAT(technicians.first_name, ' ', COALESCE(technicians.last_name, '')) as technician, AVG(feedback.rating) as avg_rating, COUNT(*) as reviews")
            ->groupBy('technicians.id', 'technician')
            ->orderByDesc('avg_rating')
            ->orderByDesc('reviews')
            ->limit(10)
            ->get();

        return [
            'chart' => ['type' => 'bar', 'height' => 320],
            'series' => [[
                'name' => 'Avg Rating',
                'data' => $data->pluck('avg_rating')->map(fn ($rating): float => round((float) $rating, 2))->toArray(),
            ]],
            'xaxis' => ['categories' => $data->pluck('technician')->map(fn ($name) => trim($name))->toArray()],
            'yaxis' => ['max' => 5],
            'colors' => ['#f59e0b'],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'horizontal' => true]],
            'dataLabels' => ['enabled' => true],
        ];
    }
}
