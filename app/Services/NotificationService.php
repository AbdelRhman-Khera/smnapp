<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Technician;
use App\Models\User;
use App\Notifications\CustomerNotification;
use App\Notifications\TechnicianNotification;
use App\Notifications\UserNotification;

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

    public static function notifyAdmins($message, $requestId)
    {
        $admins = User::all();
        foreach ($admins as $admin) {
            $admin->notify(new UserNotification($message, $requestId));
        }
    }
}
