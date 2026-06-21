<?php

namespace App\Filament\Resources\PushNotificationCampaignResource\Pages;

use App\Filament\Resources\PushNotificationCampaignResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePushNotificationCampaign extends CreateRecord
{
    protected static string $resource = PushNotificationCampaignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
