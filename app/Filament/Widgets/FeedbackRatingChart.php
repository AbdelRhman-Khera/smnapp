<?php

namespace App\Filament\Widgets;

use App\Models\Feedback;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class FeedbackRatingChart extends ApexChartWidget
{
    protected static ?string $chartId = 'feedbackRatingChart';
    protected static ?string $heading = 'Feedback Ratings';

    protected function getOptions(): array
    {
        $ratings = range(1, 5);
        $counts = collect($ratings)->map(fn($r) => Feedback::where('rating', $r)->count());

        return [
            'chart' => ['type' => 'bar'],
            'series' => [[
                'name' => 'Count',
                'data' => $counts->toArray(),
            ]],
            'xaxis' => ['categories' => $ratings],
        ];
    }
}
