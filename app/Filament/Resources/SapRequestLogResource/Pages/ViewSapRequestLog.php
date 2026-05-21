<?php

namespace App\Filament\Resources\SapRequestLogResource\Pages;

use App\Filament\Resources\SapRequestLogResource;
use App\Models\SapRequestLog;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSapRequestLog extends ViewRecord
{
    protected static string $resource = SapRequestLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resend')
                ->label('Resend')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Resend SAP request?')
                ->modalDescription('This will send the related maintenance request to SAP again.')
                ->visible(fn (SapRequestLog $record): bool => SapRequestLogResource::canResend($record))
                ->action(fn (SapRequestLog $record) => SapRequestLogResource::resend($record)),
        ];
    }
}
