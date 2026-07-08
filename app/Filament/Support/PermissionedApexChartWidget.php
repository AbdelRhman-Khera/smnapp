<?php

namespace App\Filament\Support;

use Filament\Forms\Components\DatePicker;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

abstract class PermissionedApexChartWidget extends ApexChartWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('widget_' . class_basename(static::class)) ?? false;
    }

    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('date_start')
                ->label('From'),

            DatePicker::make('date_end')
                ->label('To'),
        ];
    }

    protected function applyDateFilter($query, string $column = 'created_at')
    {
        $start = $this->filterFormData['date_start'] ?? null;
        $end = $this->filterFormData['date_end'] ?? null;

        if ($start) {
            $query->whereDate($column, '>=', $start);
        }

        if ($end) {
            $query->whereDate($column, '<=', $end);
        }

        return $query;
    }
}
