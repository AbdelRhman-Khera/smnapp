<?php

namespace App\Filament\Resources\DeviceWithdrawalRequestResource\Pages;

use App\Filament\Resources\DeviceWithdrawalRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeviceWithdrawalRequest extends EditRecord
{
    protected static string $resource = DeviceWithdrawalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }
}
