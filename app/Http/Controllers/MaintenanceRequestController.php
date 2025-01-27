<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\MaintenanceRequest;
use App\Models\Slot;
use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class MaintenanceRequestController extends Controller
{

    public function index(Request $request)
    {
        $customer = $request->user();

        $query = MaintenanceRequest::with(['customer', 'technician', 'address', 'slot', 'products', 'statuses', 'invoice', 'invoice.services', 'invoice.spareParts'])
            ->where('customer_id', $customer->id);

        // Filter by maintenance types
        if ($request->has('types') && is_array($request->types)) {
            $query->whereIn('type', $request->types);
        }

        // Filter by current status
        if ($request->has('statuses') && is_array($request->statuses)) {
            $query->whereHas('statuses', function ($query) use ($request) {
                $query->latest() // Get the latest status
                    ->whereIn('status', $request->statuses);
            });
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        // Retrieve `per_page` and `page` from query parameters with defaults
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        // Paginate the results
        $maintenanceRequests = $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);


        // $maintenanceRequests->getCollection()->transform(function ($request) {
        //     $request->current_status = $request->current_status; // Append current status
        //     return $request;
        // });

        return response()->json([
            'status' => 200,
            'response_code' => 'MAINTENANCE_REQUESTS_FETCHED',
            'message' => __('messages.maintenance_requests_fetched'),
            'data' => $maintenanceRequests,
        ], 200);
    }


    /**
     * Display the specified maintenance request.
     */
    public function show($id)
    {
        $maintenanceRequest = MaintenanceRequest::with(['customer', 'slot', 'technician', 'address', 'products', 'statuses', 'invoice', 'invoice.services', 'invoice.spareParts', 'feedback'])->findOrFail($id);
        // dd($maintenanceRequest->invoice);
        return response()->json([
            'status' => 200,
            'response_code' => 'MAINTENANCE_REQUEST_FETCHED',
            'message' =>  __('messages.maintenance_request_fetched'),
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
            'message' => __('messages.slots_fetched'),
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

        $maintenanceRequest = MaintenanceRequest::with(['customer', 'technician', 'address', 'products', 'statuses'])->findOrFail($request->request_id);
        $newSlot = Slot::with('technician')->findOrFail($request->slot_id);

        // Check if the new slot is already booked
        if ($newSlot->is_booked) {
            return response()->json([
                'status' => 400,
                'response_code' => 'SLOT_ALREADY_BOOKED',
                'message' => __('messages.slot_already_booked'),
            ], 400);
        }

        // If the request already has a slot, mark the old slot as not booked
        if ($maintenanceRequest->slot_id) {
            $oldSlot = Slot::find($maintenanceRequest->slot_id);
            if ($oldSlot) {
                $oldSlot->update(['is_booked' => false]);
            }
        }

        // Update the new slot to booked
        $newSlot->update(['is_booked' => true]);

        // Assign the new technician and slot to the request
        $maintenanceRequest->update([
            'technician_id' => $newSlot->technician_id,
            'slot_id' => $newSlot->id,
        ]);

        // Update the request status
        $maintenanceRequest->statuses()->create([
            'status' => 'technician_assigned',
        ]);

        return response()->json([
            'status' => 200,
            'response_code' => 'SLOT_ASSIGNED',
            'message' => __('messages.slot_assigned'),
            'data' => [
                'maintenance_request' => $maintenanceRequest,
                'slot' => $newSlot,
            ],
        ], 200);
    }


    public function cancel(Request $request, $id)
    {
        $maintenanceRequest = MaintenanceRequest::findOrFail($id);
        $customer = $request->user();
        if ($customer->id !== $maintenanceRequest->customer_id) {
            return response()->json([
                'status' => 403,
                'response_code' => 'FORBIDDEN',
                'message' => __('messages.not_authorized'),
            ], 403);
        }


        if ($maintenanceRequest->current_status->status === 'completed' || $maintenanceRequest->current_status->status === 'canceled') {
            return response()->json([
                'status' => 400,
                'response_code' => 'CANNOT_CANCEL',
                'message' => __('messages.cannot_cancel_request'),
            ], 400);
        }

        $maintenanceRequest->statuses()->create([
            'status' => 'canceled',
        ]);

        if ($maintenanceRequest->slot_id) {
            $oldSlot = Slot::find($maintenanceRequest->slot_id);
            if ($oldSlot) {
                $oldSlot->update(['is_booked' => false]);
            }
        }


        return response()->json([
            'status' => 200,
            'response_code' => 'REQUEST_CANCELED',
            'message' => __('messages.request_canceled'),
            'data' => $maintenanceRequest,
        ], 200);
    }

    public function setPaymentMethod(Request $request, $id)
    {
        $maintenanceRequest = MaintenanceRequest::findOrFail($id);

        if ($maintenanceRequest->customer_id != $request->user()->id) {
            return response()->json([
                'status' => 403,
                'response_code' => 'UNAUTHORIZED_CUSTOMER',
                'message' => 'You are not authorized to update this request.',
            ], 403);
        }

        if ($maintenanceRequest->current_status->status != 'waiting_for_payment') {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_STATUS',
                'message' => 'The request is not in waiting_for_payment status.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string|in:cash,online',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        $invoice = $maintenanceRequest->invoice;
        if (!$invoice) {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVOICE_NOT_FOUND',
                'message' => 'Invoice not found for this request.',
            ], 400);
        }

        $invoice->update([
            'payment_method' => $validatedData['payment_method'],
        ]);

        return response()->json([
            'status' => 200,
            'response_code' => 'PAYMENT_METHOD_UPDATED',
            'message' => 'Payment method updated successfully.',
            'data' => [
                'maintenance_request' => $maintenanceRequest->load(['statuses', 'feedback', 'customer', 'slot', 'technician', 'address', 'products', 'invoice', 'invoice.services', 'invoice.spareParts']),
                'invoice' => $invoice->load('services', 'spareParts'),
            ],
        ], 200);
    }
    public function submitFeedback(Request $request, $id)
    {
        $maintenanceRequest = MaintenanceRequest::findOrFail($id);

        if ($maintenanceRequest->customer_id != $request->user()->id) {
            return response()->json([
                'status' => 403,
                'response_code' => 'UNAUTHORIZED_CUSTOMER',
                'message' => 'You are not authorized to submit feedback for this request.',
            ], 403);
        }

        if ($maintenanceRequest->current_status->status != 'completed') {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_REQUEST_STATUS',
                'message' => 'Feedback can only be submitted for completed requests.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'feedback_text' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        $feedback = Feedback::create([
            'maintenance_request_id' => $maintenanceRequest->id,
            'rating' => $validatedData['rating'],
            'feedback_text' => $validatedData['feedback_text'] ?? null,
        ]);

        $technician = $maintenanceRequest->technician;
        if ($technician) {
            $technician->reviews_count += 1;
            $technician->rating = Feedback::whereHas('maintenanceRequest', function ($query) use ($technician) {
                $query->where('technician_id', $technician->id);
            })->avg('rating');
            $technician->save();
        }

        return response()->json([
            'status' => 200,
            'response_code' => 'FEEDBACK_SUBMITTED',
            'message' => 'Feedback submitted successfully.',
            'data' => $maintenanceRequest->load(['statuses', 'feedback', 'customer', 'slot', 'technician', 'address', 'products', 'invoice', 'invoice.services', 'invoice.spareParts']),
        ], 200);
    }
}
