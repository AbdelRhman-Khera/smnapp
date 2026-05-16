<?php

namespace App\Filament\Resources\TechnicianSparePartRequestResource\Pages;

use App\Filament\Resources\TechnicianSparePartRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTechnicianSparePartRequest extends ViewRecord
{
    protected static string $resource = TechnicianSparePartRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
        ];
    }
}
