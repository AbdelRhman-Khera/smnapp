<?php

namespace App\Filament\Widgets;

use App\Models\Slot;
use App\Models\MaintenanceRequest;
use Filament\Widgets\Widget;

class AppointmentWidget extends Widget
{
    protected static string $view = 'filament.widgets.appointment-widget';

    protected int|string|array $columnSpan = 'full';
    public ?Slot $slot = null;
    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.resources.maintenance-requests.view');
    }
    public function mount($record)
    {
        $this->slot = Slot::where('id', $record->slot_id)->first();
    }
}
