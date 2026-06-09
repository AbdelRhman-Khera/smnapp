<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function paymentMethods(): JsonResponse
    {
        $paymentMethods = collect(Setting::paymentMethods())
            ->map(fn (array $method): array => [
                'code' => $method['code'] ?? null,
                'label' => app()->getLocale() === 'ar'
                    ? ($method['label_ar'] ?? $method['label_en'] ?? null)
                    : ($method['label_en'] ?? null),
                'label_en' => $method['label_en'] ?? null,
                'label_ar' => $method['label_ar'] ?? null,
                'is_active' => (bool) ($method['is_active'] ?? false),
            ]);

        return response()->json([
            'status' => 200,
            'response_code' => 'PAYMENT_METHODS_FETCHED',
            'message' => 'Payment methods fetched successfully.',
            'data' => [
                'payment_methods' => $paymentMethods,
            ],
        ]);
    }
}
