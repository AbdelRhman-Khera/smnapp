<?php

namespace App\Filament\Resources\TechnicianPayoutRequestResource\Pages;

use App\Filament\Resources\TechnicianPayoutRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListTechnicianPayoutRequests extends ListRecords
{
    protected static string $resource = TechnicianPayoutRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
