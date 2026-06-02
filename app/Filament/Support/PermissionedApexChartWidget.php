<?php

namespace App\Filament\Support;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

abstract class PermissionedApexChartWidget extends ApexChartWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('widget_' . class_basename(static::class)) ?? false;
    }
}
