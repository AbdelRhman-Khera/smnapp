<?php

namespace App\Filament\Resources\SapRequestLogResource\Pages;

use App\Filament\Resources\SapRequestLogResource;
use Filament\Resources\Pages\ListRecords;

class ListSapRequestLogs extends ListRecords
{
    protected static string $resource = SapRequestLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
