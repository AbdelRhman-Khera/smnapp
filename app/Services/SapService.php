<?php

namespace App\Services;

use App\Models\MaintenanceRequest;
use App\Models\Product;
use App\Models\SapRequestLog;
use Illuminate\Support\Facades\Http;

class SapService
{
    public function createSalesOrder(MaintenanceRequest $maintenanceRequest): array
    {
        $maintenanceRequest->loadMissing([
            'customer',
            'address.city',
            'technician',
        ]);

      
        // if (
        //     $maintenanceRequest->sap_sync_status === 'success'
        //     && !empty($maintenanceRequest->sap_sales_order_no)
        // ) {
        //     return [
        //         'success' => true,
        //         'message' => 'Already synced before.',
        //         'duplicate' => true,
        //     ];
        // }

        

        $payload = [
            'CUSTOMER_ID'    => $maintenanceRequest->entry_sap_id ?? '18002W03',
            'TECHNICIAN_ID'  => $maintenanceRequest->technician->sap_id ?? $maintenanceRequest->technician_id,
            'ORDER_NO'       => (string) ($maintenanceRequest->sap_order_id ?: $maintenanceRequest->id),
            'PAYMENT_METHOD' => $paymentMethod === 'online' ? 'Online' : 'Cash',
            'PHONE'          => $maintenanceRequest->customer->phone ?? null,
            'NAME'           => trim(($maintenanceRequest->customer->first_name ?? '') . ' ' . ($maintenanceRequest->customer->last_name ?? '')),
            'STREET'         => $maintenanceRequest->address->street ?? null,
            'CITY'           => $maintenanceRequest->address->city->name_en ?? $maintenanceRequest->address->city->name_ar ?? null,
            'SITE'           => $maintenanceRequest->customer->site_code ?? $maintenanceRequest->entry_sap_id ?? null,
            'ITEMS'          => $items,
        ];

        $url = 'https://dev.samnan.com.sa/sap/bc/zrestful_sales?sap-client=300&Action=CREATE_SALESORDER&sap-language=E';

        try {
            $response = Http::withBasicAuth('test', 'EASTER@Egypt@2026')
                ->acceptJson()
                ->post($url, $payload);

            $responseBody = $response->json();
            $first = is_array($responseBody) && isset($responseBody[0]) ? $responseBody[0] : [];

            $sapStatus = $first['STATUS'] ?? null;
            $sapDesc = $first['DESC'] ?? null;

            $success = $response->successful() && $sapStatus === 'S';

            SapRequestLog::create([
                'maintenance_request_id' => $maintenanceRequest->id,
                'action' => 'create_sales_order',
                'payment_method' => $paymentMethod,
                'http_method' => 'POST',
                'url' => $url,
                'http_status' => $response->status(),
                'sap_status' => $sapStatus,
                'sap_desc' => $sapDesc,
                'request_payload' => $payload,
                'response_body' => $responseBody,
                'is_success' => $success,
                'created_by' => $createdBy,
            ]);

            if ($success) {
                $maintenanceRequest->update([
                    'sap_sync_status' => 'success',
                    'sap_sales_order_no' => $sapDesc,
                    'sap_last_error' => null,
                ]);
            } else {
                $maintenanceRequest->update([
                    'sap_sync_status' => 'failed',
                    'sap_last_error' => $sapDesc ?: 'Unknown SAP error',
                ]);
            }

            return [
                'success' => $success,
                'sap_status' => $sapStatus,
                'sap_desc' => $sapDesc,
                'response' => $responseBody,
            ];
        } catch (\Throwable $e) {
            SapRequestLog::create([
                'maintenance_request_id' => $maintenanceRequest->id,
                'action' => 'create_sales_order',
                'payment_method' => $paymentMethod,
                'http_method' => 'POST',
                'url' => $url,
                'request_payload' => $payload,
                'error_message' => $e->getMessage(),
                'is_success' => false,
                'created_by' => $createdBy,
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