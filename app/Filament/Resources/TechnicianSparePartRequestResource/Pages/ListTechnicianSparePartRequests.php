<?php

namespace App\Filament\Resources\TechnicianSparePartRequestResource\Pages;

use App\Filament\Resources\TechnicianSparePartRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTechnicianSparePartRequests extends ListRecords
{
    protected static string $resource = TechnicianSparePartRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
