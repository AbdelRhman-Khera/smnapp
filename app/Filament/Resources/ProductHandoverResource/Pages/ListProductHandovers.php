<?php

namespace App\Filament\Resources\ProductHandoverResource\Pages;

use App\Filament\Resources\ProductHandoverResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductHandovers extends ListRecords
{
    protected static string $resource = ProductHandoverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Handover'),
        ];
    }
}
