<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('widget_' . class_basename(static::class)) ?? false;
    }

    protected function getStats(): array
    {
        return [
            //
        ];
    }
}
