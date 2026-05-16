<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\SparePart;
use App\Models\TechnicianSparePartRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class TechnicianSparePartRequestController extends Controller
{
    public function branches()
    {
        $branches = Branch::all();
        return response()->json([
            'status' => 200,
            'message' => 'Branches fetched successfully.',
            'data' => $branches,
        ]);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'branch_id' => 'required|exists:branches,id',

            'notes' => 'nullable|string',

            'items' => 'required|array|min:1',

            'items.*.spare_part_id' => 'required|exists:spare_parts,id',

            'items.*.quantity' => 'required|integer|min:1',

        ]);

        if ($validator->fails()) {

            return response()->json([
                'status' => 422,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $technician = $request->user();

        $sparePartRequest = TechnicianSparePartRequest::create([
            'branch_id' => $request->branch_id,
            'technician_id' => $technician->id,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        foreach ($request->items as $item) {

            $sparePartRequest->items()->create([
                'spare_part_id' => $item['spare_part_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | SAP Integration
        |--------------------------------------------------------------------------
        */

        try {

            $payload = [
                'SUPP_PLANT' => $sparePartRequest->branch->sap_id,
                'REF' => (string) $sparePartRequest->id,

                'ITEMS' => $sparePartRequest->items->map(function ($item) use ($technician) {

                    return [
                        'MATNR'    => (string) $item->sparePart->sap_id,
                        'QTY'      => (string) $item->quantity,
                        'PLANT'    => (string) $technician->site_id,
                        'STGE_LOC' => (string) $technician->storage_location,
                    ];
                })->values()->toArray(),
            ];

            $response = Http::withBasicAuth(
                'TEST',
                'EASTER@Egypt@2026'
            )
                ->acceptJson()
                ->contentType('application/json')
                ->timeout(60)
                ->post(
                    'https://dev.samnan.com.sa/sap/bc/zrestful_sales?sap-client=300&Action=CREATE_STO&sap-language=E',
                    $payload
                );

            $responseData = $response->json();
            // dd($responseData,$payload,$responseData[0]['STATUS'] );

            // $sparePartRequest->update([
            //     'request_payload' => $payload,
            //     'response' => $responseData,
            // ]);
            $status = $responseData[0]['STATUS'] ?? null;
            if ($status === 'S') {

                $sparePartRequest->update([
                    'status' => 'created',

                    'sap_ref' => $responseData[0]['DESC'] ?? null,

                    'response' => $responseData,
                ]);
            } else {

                $sparePartRequest->update([
                    'status' => 'failed',
                    'response' => $responseData,
                ]);

                throw new \Exception(
                    $responseData[0]['DESC'] ?: 'SAP STO creation failed'
                );
            }
        } catch (\Throwable $e) {

            $sparePartRequest->update([
                'status' => 'failed',

                'response' => [
                    'error' => $e->getMessage(),
                ],
            ]);

            report($e);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Request created successfully.',
            'data' => $sparePartRequest->load([
                'branch',
                'items.sparePart',
            ]),
        ]);
    }

    public function index(Request $request)
    {
        $technician = $request->user();

        $requests = TechnicianSparePartRequest::where('technician_id', $technician->id)
            ->with(['branch', 'items.sparePart'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 200,
            'message' => 'Requests fetched successfully.',
            'data' => $requests,
        ]);
    }

    public function show(Request $request, $id)
    {
        $technician = $request->user();

        $sparePartRequest = TechnicianSparePartRequest::where('id', $id)
            ->where('technician_id', $technician->id)
            ->with(['branch', 'items.sparePart'])
            ->first();

        if (!$sparePartRequest) {
            return response()->json([
                'status' => 404,
                'message' => 'Request not found.',
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Request details fetched successfully.',
            'data' => $sparePartRequest,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $spareRequest = TechnicianSparePartRequest::with('items')
            ->findOrFail($id);
\DB::statement("UPDATE technician_spare_part_requests SET status = 'delivered' WHERE id = ?", [$spareRequest->id]);

dd(\DB::select("SELECT status FROM technician_spare_part_requests WHERE id = ?", [$spareRequest->id]));
        foreach ($request->items as $itemData) {
            $spare = SparePart::where('sap_id', $itemData['item_id'])->first();
            $item = $spareRequest->items()
                ->where('spare_part_id', $spare->id)
                ->first();

            if ($item) {

                $item->update([
                    'approved_quantity' => $itemData['approved_quantity'],
                    'item_no'           => $itemData['item_no'],
                ]);
            }
        }

        $spareRequest->update([
            'status' => 'ready_to_deliver',
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Request approved successfully.',
            // 'data' => $spareRequest->load('items.sparePart'),
        ]);
    }

    function confirmDelivery($id)
    {
        $spareRequest = TechnicianSparePartRequest::with([
            'items.sparePart'
        ])->findOrFail($id);

        if ($spareRequest->status !== 'ready_to_deliver') {
            return response()->json([
                'status' => 400,
                'message' => 'Request is not ready for delivery confirmation.',
            ], 400);
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE GR Payload
        |--------------------------------------------------------------------------
        */

        $items = $spareRequest->items->map(function ($item, $index) {

            return [
                'MATNR' => (string) $item->sparePart->sap_id,

                'QTY' => (string) ($item->approved_quantity),

                'PO_ITEM' => (string) ($item->item_no),
            ];
        })->values()->toArray();

        $payload = [
            'GR_NO' => $spareRequest->sap_ref,
            'ITEMS' => $items,
        ];

        try {

            $response = Http::withBasicAuth(
                'test',
                'EASTER@Egypt@2026'
            )->post(
                'https://dev.samnan.com.sa/sap/bc/zrestful_sales?sap-client=300&Action=CREATE_GR&sap-language=E',
                $payload
            );

            $responseData = $response->json();

            /*
            |--------------------------------------------------------------------------
            | Save Response
            |--------------------------------------------------------------------------
            */

            $status = $responseData[0]['STATUS'] ?? null;
            if ($status === 'S') {

            \DB::enableQueryLog();
                $spareRequest->status = 'delivered';
                $spareRequest->gr_response = $responseData;
                $spareRequest->delivered_at = now();

                $spareRequest->save();

dd(\DB::getQueryLog());

                return response()->json([
                    'status' => 200,
                    'message' => 'Delivery confirmed successfully.',
                    'sap_response' => $responseData,
                    'data' => $spareRequest->load('items.sparePart'),
                ]);
            }else {
                throw new \Exception(
                    $responseData[0]['DESC'] ?: 'SAP GR creation failed'
                );
            }
        } catch (\Exception $e) {

            $spareRequest->update([
                'gr_response' => [
                    'error' => $e->getMessage(),
                ],
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'SAP request failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
