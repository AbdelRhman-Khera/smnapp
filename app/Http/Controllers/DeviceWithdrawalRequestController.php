<?php

namespace App\Http\Controllers;

use App\Models\DeviceWithdrawalRequest;
use App\Models\MaintenanceRequest;
use App\Models\Customer;
use App\Models\Technician;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DeviceWithdrawalRequestController extends Controller
{
    public function technicianStore(Request $request, $id)
    {
        $maintenanceRequest = MaintenanceRequest::with(['products', 'deviceWithdrawalRequests.items'])
            ->findOrFail($id);
        $technician = $request->user();

        if (! $technician instanceof Technician) {
            return response()->json([
                'status' => 403,
                'response_code' => 'TECHNICIAN_ONLY',
                'message' => 'Only technicians can create device withdrawal requests.',
            ], 403);
        }

        if ($maintenanceRequest->technician_id !== $technician->id) {
            return response()->json([
                'status' => 403,
                'response_code' => 'FORBIDDEN',
                'message' => __('messages.not_authorized'),
            ], 403);
        }

        if ($maintenanceRequest->last_status !== 'in_progress') {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_STATUS',
                'message' => 'Device withdrawal can only be requested while the maintenance request is in progress.',
            ], 400);
        }

        $requestProductIds = $maintenanceRequest->products->pluck('id')->all();

        $validator = Validator::make($request->all(), [
            'branch_id' => ['nullable', 'exists:branches,id'],
            'technician_notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::in($requestProductIds)],
            'items.*.serial_number' => ['nullable', 'string', 'max:255'],
            'items.*.notes' => ['nullable', 'string'],
            'items.*.photos' => ['nullable', 'array'],
            'items.*.photos.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $withdrawalRequest = DB::transaction(function () use ($request, $maintenanceRequest, $technician, $validated) {
            $withdrawalRequest = DeviceWithdrawalRequest::create([
                'maintenance_request_id' => $maintenanceRequest->id,
                'customer_id' => $maintenanceRequest->customer_id,
                'technician_id' => $technician->id,
                'branch_id' => $validated['branch_id'] ?? null,
                'status' => DeviceWithdrawalRequest::STATUS_PENDING_CUSTOMER_APPROVAL,
                'technician_notes' => $validated['technician_notes'] ?? null,
            ]);

            foreach ($validated['items'] as $index => $item) {
                $photos = [];

                foreach ((array) $request->file("items.$index.photos", []) as $photo) {
                    $photos[] = $photo->store('device_withdrawals', 'public');
                }

                $withdrawalRequest->items()->create([
                    'product_id' => $item['product_id'],
                    'serial_number' => $item['serial_number'] ?? null,
                    'notes' => $item['notes'] ?? null,
                    'photos' => $photos,
                    'status' => DeviceWithdrawalRequest::STATUS_PENDING_CUSTOMER_APPROVAL,
                ]);
            }

            return $withdrawalRequest->fresh(['items.product', 'branch', 'maintenanceRequest']);
        });

        NotificationService::notifyCustomerTranslated(
            $withdrawalRequest->customer_id,
            'notifications.customer.device_withdrawal_requested',
            ['id' => $withdrawalRequest->id],
            $withdrawalRequest->maintenance_request_id
        );

        return response()->json([
            'status' => 201,
            'response_code' => 'DEVICE_WITHDRAWAL_REQUEST_CREATED',
            'message' => 'Device withdrawal request created successfully.',
            'data' => $withdrawalRequest,
        ], 201);
    }

    public function technicianIndex(Request $request)
    {
        $technician = $request->user();

        if (! $technician instanceof Technician) {
            return response()->json([
                'status' => 403,
                'response_code' => 'TECHNICIAN_ONLY',
                'message' => 'Only technicians can access device withdrawal requests.',
            ], 403);
        }

        $query = DeviceWithdrawalRequest::with([
            'items.product',
            'branch',
            'maintenanceRequest',
            'customer',
            'handoffTechnician',
            'followUpMaintenanceRequest',
        ])
            ->where(function ($query) use ($technician) {
                $query->where('technician_id', $technician->id)
                    ->orWhere('handoff_technician_id', $technician->id)
                    ->orWhereHas('followUpMaintenanceRequest', function ($query) use ($technician) {
                        $query->where('technician_id', $technician->id);
                    });
            })
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'status' => 200,
            'response_code' => 'TECHNICIAN_DEVICE_WITHDRAWAL_REQUESTS_FETCHED',
            'message' => 'Technician device withdrawal requests fetched successfully.',
            'data' => $query->paginate((int) $request->input('per_page', 10)),
        ], 200);
    }

    public function customerIndex(Request $request)
    {
        $customer = $request->user();

        if (! $customer instanceof Customer) {
            return response()->json([
                'status' => 403,
                'response_code' => 'CUSTOMER_ONLY',
                'message' => 'Only customers can access device withdrawal requests.',
            ], 403);
        }

        $query = DeviceWithdrawalRequest::with([
            'items.product',
            'branch',
            'maintenanceRequest',
            'technician',
            'handoffTechnician',
            'followUpMaintenanceRequest.invoice',
        ])
            ->where('customer_id', $customer->id)
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'status' => 200,
            'response_code' => 'DEVICE_WITHDRAWAL_REQUESTS_FETCHED',
            'message' => 'Device withdrawal requests fetched successfully.',
            'data' => $query->paginate((int) $request->input('per_page', 10)),
        ], 200);
    }

    public function customerApprove(Request $request, $id)
    {
        return $this->customerDecision($request, $id, true);
    }

    public function customerReject(Request $request, $id)
    {
        return $this->customerDecision($request, $id, false);
    }

    public function customerConfirmReceived(Request $request, $id)
    {
        $withdrawalRequest = DeviceWithdrawalRequest::with('followUpMaintenanceRequest')->findOrFail($id);
        $customer = $request->user();

        if (! $customer instanceof Customer) {
            return response()->json([
                'status' => 403,
                'response_code' => 'CUSTOMER_ONLY',
                'message' => 'Only customers can confirm device receipt.',
            ], 403);
        }

        if ($withdrawalRequest->customer_id !== $customer->id) {
            return response()->json([
                'status' => 403,
                'response_code' => 'FORBIDDEN',
                'message' => __('messages.not_authorized'),
            ], 403);
        }

        if ($withdrawalRequest->status !== DeviceWithdrawalRequest::STATUS_DELIVERED_TO_CUSTOMER) {
            return response()->json([
                'status' => 400,
                'response_code' => 'DEVICE_NOT_DELIVERED_TO_CUSTOMER',
                'message' => 'Device receipt can only be confirmed after the technician delivers it to the customer.',
            ], 400);
        }

        $withdrawalRequest->update([
            'status' => DeviceWithdrawalRequest::STATUS_COMPLETED,
            'customer_received_at' => now(),
        ]);

        $withdrawalRequest->items()->update([
            'status' => DeviceWithdrawalRequest::STATUS_COMPLETED,
        ]);

        return response()->json([
            'status' => 200,
            'response_code' => 'DEVICE_RECEIPT_CONFIRMED',
            'message' => 'Device receipt confirmed successfully.',
            'data' => $withdrawalRequest->fresh(['items.product', 'followUpMaintenanceRequest']),
        ], 200);
    }

    public function assignDeliveryTechnician(Request $request, $id)
    {
        $withdrawalRequest = DeviceWithdrawalRequest::with(['items', 'maintenanceRequest'])->findOrFail($id);
        $technician = $request->user();

        if (! $technician instanceof Technician) {
            return response()->json([
                'status' => 403,
                'response_code' => 'TECHNICIAN_ONLY',
                'message' => 'Only technicians can assign delivery technicians.',
            ], 403);
        }

        if ($withdrawalRequest->technician_id !== $technician->id) {
            return response()->json([
                'status' => 403,
                'response_code' => 'FORBIDDEN',
                'message' => __('messages.not_authorized'),
            ], 403);
        }

        if ($withdrawalRequest->status !== DeviceWithdrawalRequest::STATUS_APPROVED_BY_CUSTOMER) {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_STATUS',
                'message' => 'Only customer approved withdrawal requests can be assigned to another technician.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'technician_id' => ['required', 'exists:technicians,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $deliveryTechnicianId = (int) $request->technician_id;

        if ($deliveryTechnicianId === (int) $technician->id) {
            return response()->json([
                'status' => 422,
                'response_code' => 'INVALID_TECHNICIAN',
                'message' => 'Please choose another technician.',
            ], 422);
        }

        $withdrawalRequest->update([
            'handoff_technician_id' => $deliveryTechnicianId,
            'branch_id' => $request->input('branch_id', $withdrawalRequest->branch_id),
            'handoff_notes' => $request->input('notes'),
            'status' => DeviceWithdrawalRequest::STATUS_ASSIGNED_TO_DELIVERY_TECHNICIAN,
            'assigned_to_handoff_technician_at' => now(),
        ]);

        $withdrawalRequest->items()->update([
            'status' => DeviceWithdrawalRequest::STATUS_ASSIGNED_TO_DELIVERY_TECHNICIAN,
        ]);

        NotificationService::notifyTechnicianTranslated(
            $deliveryTechnicianId,
            'notifications.technician.device_withdrawal_assigned',
            ['id' => $withdrawalRequest->id],
            $withdrawalRequest->maintenance_request_id
        );

        return response()->json([
            'status' => 200,
            'response_code' => 'DEVICE_WITHDRAWAL_ASSIGNED_TO_TECHNICIAN',
            'message' => 'Device withdrawal request assigned to delivery technician successfully.',
            'data' => $withdrawalRequest->fresh(['items.product', 'branch', 'technician', 'handoffTechnician']),
        ], 200);
    }

    public function receiveFromTechnician(Request $request, $id)
    {
        $withdrawalRequest = DeviceWithdrawalRequest::with(['items', 'handoffTechnician'])->findOrFail($id);
        $technician = $request->user();

        if (! $technician instanceof Technician) {
            return response()->json([
                'status' => 403,
                'response_code' => 'TECHNICIAN_ONLY',
                'message' => 'Only technicians can receive device withdrawal requests.',
            ], 403);
        }

        if ($withdrawalRequest->handoff_technician_id !== $technician->id) {
            return response()->json([
                'status' => 403,
                'response_code' => 'FORBIDDEN',
                'message' => __('messages.not_authorized'),
            ], 403);
        }

        if ($withdrawalRequest->status !== DeviceWithdrawalRequest::STATUS_ASSIGNED_TO_DELIVERY_TECHNICIAN) {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_STATUS',
                'message' => 'Only assigned withdrawal requests can be received by the delivery technician.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $withdrawalRequest->update([
            'status' => DeviceWithdrawalRequest::STATUS_RECEIVED_BY_DELIVERY_TECHNICIAN,
            'handoff_notes' => $request->input('notes', $withdrawalRequest->handoff_notes),
            'received_by_handoff_technician_at' => now(),
        ]);

        $withdrawalRequest->items()->update([
            'status' => DeviceWithdrawalRequest::STATUS_RECEIVED_BY_DELIVERY_TECHNICIAN,
        ]);

        return response()->json([
            'status' => 200,
            'response_code' => 'DEVICE_WITHDRAWAL_RECEIVED_BY_TECHNICIAN',
            'message' => 'Device withdrawal request received by delivery technician successfully.',
            'data' => $withdrawalRequest->fresh(['items.product', 'branch', 'technician', 'handoffTechnician']),
        ], 200);
    }

    public function technicianDeliverToBranch(Request $request, $id)
    {
        $withdrawalRequest = DeviceWithdrawalRequest::with(['items', 'branch'])->findOrFail($id);
        $technician = $request->user();

        if (! $technician instanceof Technician) {
            return response()->json([
                'status' => 403,
                'response_code' => 'TECHNICIAN_ONLY',
                'message' => 'Only technicians can deliver device withdrawal requests.',
            ], 403);
        }

        $canOriginalTechnicianDeliver = $withdrawalRequest->technician_id === $technician->id
            && $withdrawalRequest->status === DeviceWithdrawalRequest::STATUS_APPROVED_BY_CUSTOMER;

        $canHandoffTechnicianDeliver = $withdrawalRequest->handoff_technician_id === $technician->id
            && $withdrawalRequest->status === DeviceWithdrawalRequest::STATUS_RECEIVED_BY_DELIVERY_TECHNICIAN;

        if (! $canOriginalTechnicianDeliver && ! $canHandoffTechnicianDeliver) {
            return response()->json([
                'status' => 403,
                'response_code' => 'FORBIDDEN',
                'message' => __('messages.not_authorized'),
            ], 403);
        }

        if (! in_array($withdrawalRequest->status, [
            DeviceWithdrawalRequest::STATUS_APPROVED_BY_CUSTOMER,
            DeviceWithdrawalRequest::STATUS_RECEIVED_BY_DELIVERY_TECHNICIAN,
        ], true)) {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_STATUS',
                'message' => 'Only approved or delivery-technician received withdrawal requests can be delivered to branch.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'branch_id' => ['required', 'exists:branches,id'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $withdrawalRequest->update([
            'branch_id' => $request->branch_id,
            'status' => DeviceWithdrawalRequest::STATUS_DELIVERED_TO_BRANCH,
            'technician_notes' => $request->input('notes', $withdrawalRequest->technician_notes),
            'delivered_to_branch_at' => now(),
        ]);

        $withdrawalRequest->items()->update([
            'status' => DeviceWithdrawalRequest::STATUS_DELIVERED_TO_BRANCH,
        ]);

        return response()->json([
            'status' => 200,
            'response_code' => 'DEVICE_WITHDRAWAL_DELIVERED_TO_BRANCH',
            'message' => 'Device withdrawal request delivered to branch successfully.',
            'data' => $withdrawalRequest->fresh(['items.product', 'branch', 'maintenanceRequest']),
        ], 200);
    }

    public function technicianDeliverToCustomer(Request $request, $id)
    {
        $withdrawalRequest = DeviceWithdrawalRequest::with(['items', 'followUpMaintenanceRequest'])->findOrFail($id);
        $technician = $request->user();

        if (! $technician instanceof Technician) {
            return response()->json([
                'status' => 403,
                'response_code' => 'TECHNICIAN_ONLY',
                'message' => 'Only technicians can deliver devices to customers.',
            ], 403);
        }

        if (! $withdrawalRequest->followUpMaintenanceRequest) {
            return response()->json([
                'status' => 400,
                'response_code' => 'FOLLOW_UP_REQUEST_NOT_FOUND',
                'message' => 'Follow-up maintenance request was not created for this withdrawal request.',
            ], 400);
        }

        if ($withdrawalRequest->followUpMaintenanceRequest->technician_id !== $technician->id) {
            return response()->json([
                'status' => 403,
                'response_code' => 'FORBIDDEN',
                'message' => __('messages.not_authorized'),
            ], 403);
        }

        if ($withdrawalRequest->followUpMaintenanceRequest->last_status !== 'completed') {
            return response()->json([
                'status' => 400,
                'response_code' => 'FOLLOW_UP_REQUEST_NOT_COMPLETED',
                'message' => 'The follow-up maintenance request must be completed before delivering the device to the customer.',
            ], 400);
        }

        if ($withdrawalRequest->status !== DeviceWithdrawalRequest::STATUS_FOLLOW_UP_REQUEST_CREATED) {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_STATUS',
                'message' => 'Only withdrawal requests with a created follow-up request can be delivered to the customer.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $withdrawalRequest->update([
            'status' => DeviceWithdrawalRequest::STATUS_DELIVERED_TO_CUSTOMER,
            'customer_delivery_notes' => $request->input('notes'),
            'delivered_to_customer_at' => now(),
        ]);

        $withdrawalRequest->items()->update([
            'status' => DeviceWithdrawalRequest::STATUS_DELIVERED_TO_CUSTOMER,
        ]);

        return response()->json([
            'status' => 200,
            'response_code' => 'DEVICE_DELIVERED_TO_CUSTOMER',
            'message' => 'Device delivered to customer successfully.',
            'data' => $withdrawalRequest->fresh(['items.product', 'followUpMaintenanceRequest']),
        ], 200);
    }

    private function customerDecision(Request $request, $id, bool $approved)
    {
        $withdrawalRequest = DeviceWithdrawalRequest::with('items')->findOrFail($id);
        $customer = $request->user();

        if (! $customer instanceof Customer) {
            return response()->json([
                'status' => 403,
                'response_code' => 'CUSTOMER_ONLY',
                'message' => 'Only customers can approve or reject device withdrawal requests.',
            ], 403);
        }

        if ($withdrawalRequest->customer_id !== $customer->id) {
            return response()->json([
                'status' => 403,
                'response_code' => 'FORBIDDEN',
                'message' => __('messages.not_authorized'),
            ], 403);
        }

        if ($withdrawalRequest->status !== DeviceWithdrawalRequest::STATUS_PENDING_CUSTOMER_APPROVAL) {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_STATUS',
                'message' => 'This withdrawal request is not waiting for customer approval.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = $approved
            ? DeviceWithdrawalRequest::STATUS_APPROVED_BY_CUSTOMER
            : DeviceWithdrawalRequest::STATUS_REJECTED_BY_CUSTOMER;

        $withdrawalRequest->update([
            'status' => $status,
            'customer_decision_notes' => [
                'approved' => $approved,
                'notes' => $request->input('notes'),
            ],
            'customer_decision_at' => now(),
        ]);

        $withdrawalRequest->items()->update([
            'status' => $status,
        ]);

        NotificationService::notifyTechnicianTranslated(
            $withdrawalRequest->technician_id,
            $approved
                ? 'notifications.technician.device_withdrawal_approved'
                : 'notifications.technician.device_withdrawal_rejected',
            ['id' => $withdrawalRequest->id],
            $withdrawalRequest->maintenance_request_id
        );

        return response()->json([
            'status' => 200,
            'response_code' => $approved ? 'DEVICE_WITHDRAWAL_APPROVED' : 'DEVICE_WITHDRAWAL_REJECTED',
            'message' => $approved
                ? 'Device withdrawal request approved successfully.'
                : 'Device withdrawal request rejected successfully.',
            'data' => $withdrawalRequest->fresh(['items.product', 'branch', 'maintenanceRequest']),
        ], 200);
    }
}
