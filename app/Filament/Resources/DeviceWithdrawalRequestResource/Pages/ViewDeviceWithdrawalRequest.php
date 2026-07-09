<?php

namespace App\Filament\Resources\DeviceWithdrawalRequestResource\Pages;

use App\Filament\Resources\DeviceWithdrawalRequestResource;
use Filament\Resources\Pages\ViewRecord;

class ViewDeviceWithdrawalRequest extends ViewRecord
{
    protected static string $resource = DeviceWithdrawalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeviceWithdrawalRequestResource::assignDeliveryTechnicianPageAction(),
            DeviceWithdrawalRequestResource::receiveAtBranchPageAction(),
            DeviceWithdrawalRequestResource::startRepairPageAction(),
            DeviceWithdrawalRequestResource::completeRepairPageAction(),
            DeviceWithdrawalRequestResource::createFollowUpRequestPageAction(),
        ];
    }
}
