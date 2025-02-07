<?php

namespace App\Filament\Resources\SupportFormResource\Pages;

use App\Filament\Resources\SupportFormResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupportForms extends ListRecords
{
    protected static string $resource = SupportFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
