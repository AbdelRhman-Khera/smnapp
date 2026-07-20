<?php

namespace App\Http\Controllers;

use App\Models\ProductHandover;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductHandoverController extends Controller
{
    public function index(Request $request)
    {
        $technician = $request->user();

        $handovers = ProductHandover::query()
            ->with([
                'items.product',
                'maintenanceRequest:id,type,last_status,customer_id,address_id,created_at',
                'maintenanceRequest.customer:id,first_name,last_name,phone',
                'maintenanceRequest.address.city',
                'maintenanceRequest.address.district',
            ])
            ->where('technician_id', $technician->id)
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->status)
            )
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'status' => 200,
            'response_code' => 'PRODUCT_HANDOVERS',
            'message' => 'Product handovers retrieved successfully.',
            'data' => [
                'product_handovers' => $handovers,
            ],
        ], 200);
    }

    public function show(Request $request, $id)
    {
        $technician = $request->user();

        $handover = ProductHandover::query()
            ->with([
                'items.product',
                'maintenanceRequest:id,type,last_status,customer_id,address_id,created_at',
                'maintenanceRequest.customer:id,first_name,last_name,phone',
                'maintenanceRequest.address.city',
                'maintenanceRequest.address.district',
            ])
            ->where('technician_id', $technician->id)
            ->find($id);

        if (! $handover) {
            return response()->json([
                'status' => 404,
                'response_code' => 'HANDOVER_NOT_FOUND',
                'message' => 'Product handover not found.',
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'response_code' => 'PRODUCT_HANDOVER',
            'message' => 'Product handover retrieved successfully.',
            'data' => [
                'product_handover' => $handover,
            ],
        ], 200);
    }

    public function accept(Request $request, $id)
    {
        return $this->processDecision($request, $id, 'accept');
    }

    public function reject(Request $request, $id)
    {
        return $this->processDecision($request, $id, 'reject');
    }

    private function processDecision(Request $request, $id, string $decision)
    {
        $technician = $request->user();

        $handover = ProductHandover::with('items.product', 'maintenanceRequest')->find($id);

        if (! $handover || $handover->technician_id !== $technician->id) {
            return response()->json([
                'status' => 404,
                'response_code' => 'HANDOVER_NOT_FOUND',
                'message' => 'Product handover not found.',
            ], 404);
        }

        if ($handover->status !== 'pending') {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_HANDOVER_STATUS',
                'message' => 'This handover was already processed.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'notes' => $decision === 'reject' ? 'required|string' : 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($handover, $decision, $request) {
            $decision === 'accept'
                ? $handover->accept($request->notes)
                : $handover->reject($request->notes);
        });

        NotificationService::notifyAdmins(
            $decision === 'accept'
                ? "Technician {$technician->name} accepted product handover #{$handover->id} (request #{$handover->maintenance_request_id})."
                : "Technician {$technician->name} rejected product handover #{$handover->id} (request #{$handover->maintenance_request_id}). Reason: {$request->notes}",
            $handover->maintenance_request_id
        );

        return response()->json([
            'status' => 200,
            'response_code' => $decision === 'accept' ? 'HANDOVER_ACCEPTED' : 'HANDOVER_REJECTED',
            'message' => $decision === 'accept'
                ? 'Product handover accepted successfully.'
                : 'Product handover rejected successfully.',
            'data' => [
                'product_handover' => $handover->fresh(['items.product', 'maintenanceRequest']),
            ],
        ], 200);
    }
}
