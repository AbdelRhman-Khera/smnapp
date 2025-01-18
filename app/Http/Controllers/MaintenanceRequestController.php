<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\Slot;
use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class MaintenanceRequestController extends Controller
{

    public function index(Request $request)
    {
        $maintenanceRequests = MaintenanceRequest::with(['customer', 'technician', 'address', 'products'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'status' => 200,
            'response_code' => 'MAINTENANCE_REQUESTS_FETCHED',
            'message' => 'Maintenance requests fetched successfully.',
            'data' => $maintenanceRequests,
        ], 200);
    }

    /**
     * Display the specified maintenance request.
     */
    public function show($id)
    {
        $maintenanceRequest = MaintenanceRequest::with(['customer', 'technician', 'address', 'products', 'statuses'])->findOrFail($id);

        return response()->json([
            'status' => 200,
            'response_code' => 'MAINTENANCE_REQUEST_FETCHED',
            'message' => 'Maintenance request fetched successfully.',
            'data' => $maintenanceRequest,
        ], 200);
    }



    public function create(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:new_installation,regular_maintenance,emergency_maintenance',
            'products' => 'required|array',
            'address_id' => 'required|exists:addresses,id',
            'problem_description' => 'nullable|string',
            'last_maintenance_date' => 'nullable|date',
            'photos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        // Process photos
        $photoPaths = [];
        if ($request->has('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('maintenance_photos', 'public');
                $photoPaths[] = $path;
            }
        }

        $maintenanceRequest = MaintenanceRequest::create([
            'customer_id' => $customer->id,
            'type' => $request->type,
            'address_id' => $request->address_id,
            'problem_description' => $request->problem_description ?? null,
            'last_maintenance_date' => $request->last_maintenance_date ?? null,
            'photos' => $photoPaths ?? [],
        ]);

        $maintenanceRequest->products()->attach($request->products);

        $maintenanceRequest->statuses()->create([
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 200,
            'response_code' => 'REQUEST_CREATED',
            'message' => __('messages.request_created'),
            'data' => $maintenanceRequest,
        ], 200);
    }

    public function getAvailableSlots(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|exists:maintenance_requests,id',
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $maintenanceRequest = MaintenanceRequest::with('address', 'products')->findOrFail($request->request_id);

        $district = $maintenanceRequest->address->district['name_en'];
        $products = $maintenanceRequest->products->pluck('id')->toArray();

        $technicians = Technician::whereHas('districts', function ($query) use ($district) {
            $query->where('name_en', $district);
        })->whereHas('products', function ($query) use ($products) {
            $query->whereIn('products.id', $products);
        })->get();

        $technicianIds = $technicians->pluck('id')->toArray();

        $slots = Slot::whereIn('technician_id', $technicianIds)
            ->whereDate('date', $request->date)
            ->where('is_booked', false)
            ->with('technician')
            ->get();

        return response()->json([
            'status' => 200,
            'response_code' => 'SLOTS_FETCHED',
            'message' => 'Available slots fetched successfully.',
            'data' => $slots,
        ], 200);
    }

    public function assignSlot(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|exists:maintenance_requests,id',
            'slot_id' => 'required|exists:slots,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $maintenanceRequest = MaintenanceRequest::with('address', 'products')->findOrFail($request->request_id);
        $slot = Slot::with('technician')->findOrFail($request->slot_id);

        if ($slot->is_booked) {
            return response()->json([
                'status' => 400,
                'response_code' => 'SLOT_ALREADY_BOOKED',
                'message' => 'The selected slot is already booked.',
            ], 400);
        }

        // Update slot to booked
        $slot->update(['is_booked' => true]);

        // Assign technician to the request
        $maintenanceRequest->update([
            'technician_id' => $slot->technician_id,
            'slot_id' => $slot->id,
        ]);

        // update request status
        $maintenanceRequest->statuses()->create([
            'status' => 'technician_assigned',
        ]);

        return response()->json([
            'status' => 200,
            'response_code' => 'SLOT_ASSIGNED',
            'message' => 'Slot assigned and technician linked to the request successfully.',
            'data' => [
                'maintenance_request' => $maintenanceRequest,
                'slot' => $slot,
            ],
        ], 200);
    }
}
