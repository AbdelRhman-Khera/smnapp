<?php

namespace App\Filament\Widgets;

use App\Models\DeviceWithdrawalRequest;
use App\Models\MaintenanceRequest;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

class DeviceWithdrawalDetailsWidget extends Widget
{
    protected static string $view = 'filament.widgets.device-withdrawal-details-widget';

    protected int|string|array $columnSpan = 'full';

    public ?MaintenanceRequest $record = null;

    public ?Collection $withdrawalRequests = null;

    public ?DeviceWithdrawalRequest $workshopWithdrawalSource = null;

    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.resources.maintenance-requests.view');
    }

    public function mount(MaintenanceRequest $record): void
    {
        $this->record = $record;

        $this->withdrawalRequests = $record->deviceWithdrawalRequests()
            ->with([
                'branch',
                'technician',
                'handoffTechnician',
                'items.product',
                'followUpMaintenanceRequest',
            ])
            ->latest()
            ->get();

        $this->workshopWithdrawalSource = $record->workshopWithdrawalSource()
            ->with([
                'branch',
                'maintenanceRequest',
                'technician',
                'handoffTechnician',
                'items.product',
            ])
            ->first();
    }
}
