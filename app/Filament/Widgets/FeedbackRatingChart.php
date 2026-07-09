<?php

namespace App\Filament\Widgets;

use App\Models\Feedback;
use App\Filament\Support\PermissionedApexChartWidget;

class FeedbackRatingChart extends PermissionedApexChartWidget
{
    protected static ?string $chartId = 'feedbackRatingChart';
    protected static ?string $heading = 'Feedback Ratings';

    protected function getOptions(): array
    {
        $ratings = range(1, 5);
        $counts = collect($ratings)->map(fn (int $rating): int => $this->applyDateFilter(Feedback::where('rating', $rating))->count());

        return [
            'chart' => ['type' => 'bar', 'height' => 320],
            'series' => [[
                'name' => 'Count',
                'data' => $counts->toArray(),
            ]],
            'xaxis' => ['categories' => array_map(fn (int $rating): string => $rating . ' ★', $ratings)],
            'colors' => ['#eab308'],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '55%', 'distributed' => false]],
            'dataLabels' => ['enabled' => false],
        ];
    }
}
