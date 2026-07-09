<?php

namespace App\Support;

use App\Models\Customer;

class CustomerPhone
{
    public static function canView(): bool
    {
        return auth()->user()?->can('view_customer_phone') ?? false;
    }

    public static function display(?string $phone): ?string
    {
        if (blank($phone)) {
            return $phone;
        }

        return static::canView() ? $phone : static::mask($phone);
    }

    public static function mask(?string $phone): string
    {
        return '••••••' . substr((string) $phone, -2);
    }

    public static function optionLabel(?Customer $customer): string
    {
        if (! $customer) {
            return '-';
        }

        $name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
            ?: 'Customer #' . $customer->id;

        return $name . ' - ' . static::display($customer->phone);
    }
}
