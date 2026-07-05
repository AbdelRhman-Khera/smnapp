<?php

namespace App\Observers;

use App\Models\MaintenanceRequest;
use App\Models\Setting;
use App\Models\TechnicianEarning;

class MaintenanceRequestObserver
{
    public function updated(MaintenanceRequest $maintenanceRequest): void
    {
        if (! $maintenanceRequest->wasChanged('last_status') || $maintenanceRequest->last_status !== 'completed') {
            return;
        }

        if (! $maintenanceRequest->technician_id) {
            return;
        }

        TechnicianEarning::firstOrCreate(
            ['maintenance_request_id' => $maintenanceRequest->id],
            [
                'technician_id' => $maintenanceRequest->technician_id,
                'request_type' => $maintenanceRequest->type,
                'amount' => Setting::technicianFeeFor($maintenanceRequest->type),
                'status' => 'pending',
            ]
        );
    }
}
