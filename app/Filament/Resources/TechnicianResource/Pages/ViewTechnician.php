<?php

namespace App\Filament\Resources\TechnicianResource\Pages;

use App\Filament\Resources\TechnicianResource;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Widgets\TechnicianMaintenanceHistory;

class ViewTechnician extends ViewRecord
{
    protected static string $resource = TechnicianResource::class;

    // protected function getFooterWidgets(): array
    // {
    //     return [
    //         TechnicianMaintenanceHistory::class,
    //     ];
    // }
}
