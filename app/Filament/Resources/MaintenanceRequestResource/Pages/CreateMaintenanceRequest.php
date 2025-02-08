<?php

namespace App\Filament\Resources\MaintenanceRequestResource\Pages;

use App\Filament\Resources\MaintenanceRequestResource;
use App\Models\MaintenanceRequest;
use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\CreateRecord;

class CreateMaintenanceRequest extends CreateRecord
{
    protected static string $resource = MaintenanceRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default status to 'pending'
        $data['last_status'] = 'pending';


        return $data;
    }

    protected function afterCreate(): void
    {

        $maintenanceRequest = $this->getRecord();


        $maintenanceRequest->statuses()->create([
            'status' => 'pending',
        ]);
    }




}
