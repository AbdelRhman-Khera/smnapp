<?php

namespace App\Filament\Resources\ProductHandoverResource\Pages;

use App\Filament\Resources\ProductHandoverResource;
use App\Services\NotificationService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProductHandover extends ViewRecord
{
    protected static string $resource = ProductHandoverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cancel')
                ->label('Cancel Handover')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->status === 'pending')
                ->requiresConfirmation()
                ->modalHeading('Cancel product handover?')
                ->modalDescription('The technician will be notified that this handover was canceled.')
                ->action(function (): void {
                    $this->record->cancel(auth()->id());

                    NotificationService::notifyTechnicianTranslated(
                        $this->record->technician_id,
                        'notifications.technician.product_handover_canceled',
                        ['id' => $this->record->maintenance_request_id],
                        $this->record->maintenance_request_id
                    );

                    $this->refreshFormData(['status', 'canceled_by', 'canceled_at']);
                }),
        ];
    }
}
