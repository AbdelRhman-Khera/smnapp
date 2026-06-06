<?php

namespace App\Filament\Resources\TechnicianResource\Pages;

use App\Filament\Pages\TechnicianCalendar;
use App\Filament\Resources\TechnicianResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTechnicians extends ListRecords
{
    protected static string $resource = TechnicianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('calendar')
                ->label('Calendar')
                ->icon('heroicon-o-calendar-days')
                ->color('gray')
                ->url(TechnicianCalendar::getUrl())
                ->visible(fn (): bool => auth()->user()?->can('page_TechnicianCalendar') ?? false),

            Actions\CreateAction::make(),
        ];
    }
}
