<?php

namespace App\Filament\Resources\MaintenanceRequestResource\Pages;

use App\Filament\Resources\MaintenanceRequestResource;
use App\Filament\Widgets\AppointmentWidget;
use App\Filament\Widgets\FeedbackWidget;
use App\Filament\Widgets\InvoiceWidget;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Widgets\StatusHistoryWidget;

class ViewMaintenanceRequest extends ViewRecord
{
    protected static string $resource = MaintenanceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            StatusHistoryWidget::class,
            AppointmentWidget::class,
            InvoiceWidget::class,
            FeedbackWidget::class,
        ];
    }
}
