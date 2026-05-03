<?php

namespace App\Http\Controllers;


use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\MaintenanceRequest;
use App\Models\Product;
use App\Models\SapRequestLog;
use Carbon\Carbon;


class SapController extends Controller
{
    public function customerSync(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'phone' => [
                'required',
                'string',
                'max:20',

                'regex:/^(5|05)\d{8}$/',
            ],
            'first_name' => 'required|string|max:100',
            'last_name'  => 'nullable|string|max:100',
            'email'      => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // $phone = trim($data['phone']);
        // if (preg_match('/^5\d{8}$/', $phone)) {
        //     $phone = '0' . $phone;
        // }

        // Check existing customer
        $existing = Customer::where('phone', $data['phone'])->first();
        if ($existing) {
            return response()->json([
                'status' => 200,
                'response_code' => 'CUSTOMER_ALREADY_EXISTS',
                'message' => 'Customer exists',
                'data' => [
                    'customer_id' => $existing->id,
                ],
            ], 200);
        }

        //  Generate password
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $plainPassword = '';
        for ($i = 0; $i < 10; $i++) {
            $plainPassword .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        $customer = Customer::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'] ?? null,
            'phone'      => $data['phone'],
            'email'      => $data['email'] ?? null,
            'password'   => Hash::make($plainPassword),
            'authorized' => 1,
            'activated'  => 1,
            'otp'        => null,
        ]);

        //  Send SMS
        $appLink = (string) config('app.app_download_link');
        $smsText = "Welcome!\nLogin: {$data['phone']}\nPassword: {$plainPassword}\nDownload the app: {$appLink}";

        $smsResp = Http::withBasicAuth(
            (string) config('services.4jawaly.key'),
            (string) config('services.4jawaly.secret')
        )->post('https://api-sms.4jawaly.com/api/v1/account/area/sms/send', [
            'messages' => [[
                'text' => $smsText,
                'numbers' => [$data['phone']],
                'sender' => (string) config('services.4jawaly.sender', 'SamnanCo'),
            ]],
        ]);

        return response()->json([
            'status' => 201,
            'response_code' => 'CUSTOMER_CREATED_AND_SMS_SENT',
            'message' => 'Created and sent SMS',
            'data' => [
                'customer_id' => $customer->id,
                'sms_ok' => $smsResp->successful(),
                'sms_http_status' => $smsResp->status(),
            ],
        ], 201);
    }

    public function getMaintenanceRequestFull($id)
    {
        $maintenanceRequest = MaintenanceRequest::with([
            'customer',
            'slot',
            'technician',
            'address',
            'address.city',
            'address.district',
            'products',
            'statuses',
            'invoice',
            'invoice.services',
            'invoice.spareParts',
            'feedback',
        ])->find($id);

        if (!$maintenanceRequest) {
            return response()->json([
                'status' => 404,
                'response_code' => 'MAINTENANCE_REQUEST_NOT_FOUND',
                'message' => 'Maintenance request not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'response_code' => 'MAINTENANCE_REQUEST_FETCHED',
            'message' => 'Maintenance request fetched successfully',
            'data' => $maintenanceRequest,
        ], 200);
    }

    public function createSalesOrder(MaintenanceRequest $maintenanceRequest, string $paymentMethod = 'Cash'): array
    {
        $maintenanceRequest->loadMissing([
            'customer',
            'technician',
            'address.city',
            'products',
            'invoice',
            'invoice.services',
            'invoice.spareParts',
        ]);

        if ($maintenanceRequest->sap_sync_status === 'success') {
            return [
                'success' => true,
                'message' => 'Already synced with SAP.',
            ];
        }

        $url = 'https://dev.samnan.com.sa/sap/bc/zrestful_sales?sap-client=300&Action=CREATE_SALESORDER&sap-language=E';

        $serviceItems = collect($maintenanceRequest->invoice?->services ?? [])
            ->map(function ($service) {
                return array_filter([
                    'MATNR' => (string) ($service->sap_id ?? ''),
                    'QTY'   => '1',
                    'PRICE' => isset($service->price) ? (string) $service->price : null,
                ]);
            });

        $sparePartItems = collect($maintenanceRequest->invoice?->spareParts ?? [])
            ->map(function ($sparePart) {
                return array_filter([
                    'MATNR' => (string) ($sparePart->sap_id ?? ''),
                    'QTY'   => (string) ($sparePart->pivot->quantity ?? 1),
                    'PRICE' => isset($sparePart->pivot->price)
                        ? (string) $sparePart->pivot->price
                        : (isset($sparePart->price) ? (string) $sparePart->price : null),
                ]);
            });

        $items = $serviceItems
            ->merge($sparePartItems)
            // ->filter(fn($item) => !empty($item['MATNR']))
            ->values()
            ->toArray();

        $payload = [
            'CUSTOMER_ID' => (string) (
                $maintenanceRequest->entry_sap_id
                ?? '18002W03'
            ),

            'TECHNICIAN_ID' => (string) (
                $maintenanceRequest->technician->sap_id
                ?? ''
            ),

            'ORDER_NO' => (string) ($maintenanceRequest->id),

            'PAYMENT_METHOD' => $paymentMethod,

            'PHONE' => (string) ($maintenanceRequest->customer->phone ?? ''),

            'NAME' => trim(
                ($maintenanceRequest->customer->first_name ?? '') . ' ' .
                    ($maintenanceRequest->customer->last_name ?? '')
            ),

            'STREET' => (string) ($maintenanceRequest->address->street ?? ''),

            'CITY' => (string) (
                $maintenanceRequest->address->city->name_en
                ?? $maintenanceRequest->address->city->name_ar
                ?? ''
            ),

            'SITE' => (string) ($maintenanceRequest->technician->site_id ?? ''),

            'ITEMS' => $items,
        ];

        try {
            $response = Http::withBasicAuth('test', 'EASTER@Egypt@2026')
                ->acceptJson()
                ->post($url, $payload);

            $body = $response->json();

            $firstRow = is_array($body) && isset($body[0]) ? $body[0] : [];

            $sapStatus = $firstRow['STATUS'] ?? null;
            $sapDesc = $firstRow['DESC'] ?? null;

            $isSuccess = $response->successful() && $sapStatus === 'S';

            SapRequestLog::create([
                'maintenance_request_id' => $maintenanceRequest->id,
                'payment_method' => $paymentMethod,
                'http_status' => $response->status(),
                'sap_status' => $sapStatus,
                'sap_desc' => $sapDesc,
                'request_payload' => $payload,
                'response_body' => $body,
                'is_success' => $isSuccess,
            ]);

            $maintenanceRequest->update([
                'sap_sync_status' => $isSuccess ? 'success' : 'failed',
                'sap_sales_order_no' => $isSuccess ? $sapDesc : null,
                'sap_last_error' => $isSuccess ? null : ($sapDesc ?? 'SAP request failed'),
            ]);

            return [
                'success' => $isSuccess,
                'sap_status' => $sapStatus,
                'sap_desc' => $sapDesc,
                'response' => $body,
            ];
        } catch (\Throwable $e) {
            SapRequestLog::create([
                'maintenance_request_id' => $maintenanceRequest->id,
                'payment_method' => $paymentMethod,
                'request_payload' => $payload,
                'error_message' => $e->getMessage(),
                'is_success' => false,
            ]);

            $maintenanceRequest->update([
                'sap_sync_status' => 'failed',
                'sap_last_error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
