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

        if (! $maintenanceRequest->technician()->where('is_freelancer', true)->exists()) {
            return;
        }

        $devicesCount = max(1, (int) $maintenanceRequest->products()->sum('maintenance_request_product.quantity'));

        TechnicianEarning::firstOrCreate(
            ['maintenance_request_id' => $maintenanceRequest->id],
            [
                'technician_id' => $maintenanceRequest->technician_id,
                'request_type' => $maintenanceRequest->type,
                'devices_count' => $devicesCount,
                'amount' => Setting::technicianFeeFor($maintenanceRequest->type) * $devicesCount,
                'status' => 'pending',
            ]
        );
    }
}
