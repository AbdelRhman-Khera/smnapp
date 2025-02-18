<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Technician;
use App\Notifications\CustomerNotification;
use App\Notifications\TechnicianNotification;

class NotificationService
{
    public static function notifyCustomer($customerId, $message, $requestId)
    {
        $customer = Customer::find($customerId);
        if ($customer) {
            $customer->notify(new CustomerNotification($message, $requestId));
        }
    }

    public static function notifyTechnician($technicianId, $message, $requestId)
    {
        $technician = Technician::find($technicianId);
        if ($technician) {
            $technician->notify(new TechnicianNotification($message, $requestId));
        }
    }
}
