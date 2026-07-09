<?php

namespace App\Filament\Resources\PushNotificationCampaignResource\Pages;

use App\Filament\Resources\PushNotificationCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPushNotificationCampaign extends EditRecord
{
    protected static string $resource = PushNotificationCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
