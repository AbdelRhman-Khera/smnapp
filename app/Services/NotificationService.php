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

    public static function notifyFreelancersForRequest($maintenanceRequest, $message)
    {
        $maintenanceRequest->loadMissing([
            'address.district',
            'products',
        ]);

        $district = $maintenanceRequest->address?->district;
        $productIds = $maintenanceRequest->products->pluck('id')->toArray();

        if (! $district || empty($productIds)) {
            return;
        }

        $technicians = Technician::query()
            ->where('is_freelancer', true)
            ->where('activated', true)
            ->where('authorized', true)
            ->whereHas('districts', function ($query) use ($district) {
                $query->where('districts.id', $district->id);
            })
            ->whereHas('products', function ($query) use ($productIds) {
                $query->whereIn('products.id', $productIds);
            })
            ->get();

        foreach ($technicians as $technician) {
            $technician->notify(new TechnicianNotification($message, $maintenanceRequest->id));
        }
    }
}
