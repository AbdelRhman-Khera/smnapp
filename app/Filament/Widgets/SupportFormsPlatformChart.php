<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SupportFormsPlatformChart extends ApexChartWidget
{
    protected static ?string $chartId = 'supportFormsPlatformChart';
    protected static ?string $heading = 'Support Forms by Platform';

    protected function getOptions(): array
    {
        $platforms = ['app', 'web'];
        $counts = collect($platforms)->map(fn($p) => DB::table('support_forms')->where('platform', $p)->count());

        return [
            'chart' => ['type' => 'donut'],
            'series' => $counts->toArray(),
            'labels' => $platforms,
        ];
    }
}
