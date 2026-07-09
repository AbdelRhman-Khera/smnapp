<?php

namespace App\Filament\Resources\PushNotificationCampaignResource\Pages;

use App\Filament\Resources\PushNotificationCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPushNotificationCampaigns extends ListRecords
{
    protected static string $resource = PushNotificationCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
