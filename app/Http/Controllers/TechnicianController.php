<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Slot;
use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\NotificationService;

class TechnicianController extends Controller
{
    public function getTechnician()
    {
        $technician = Technician::find(auth()->user()->id);

        if (!$technician) {
            return response()->json([
                'status' => 404,
                'response_code' => 'TECHNICIAN_NOT_FOUND',
                'message' => __('messages.technician_not_found'),
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'response_code' => 'TECHNICIAN_FOUND',
            'message' => __('messages.technician_found'),
            'data' => $technician,
        ], 200);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $technician = Technician::where('phone', $request->phone)->first();

        if (!$technician || !Hash::check($request->password, $technician->password)) {
            return response()->json([
                'status' => 401,
                'response_code' => 'INVALID_CREDENTIALS',
                'message' => __('messages.invalid_credentials'),
                'data' => null,
            ], 401);
        }

        $token = $technician->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 200,
            'response_code' => 'LOGIN_SUCCESS',
            'message' => __('messages.login_success'),
            'data' => ['token' => $token, 'technician' => $technician],
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 200,
            'response_code' => 'LOGOUT_SUCCESS',
            'message' => __('messages.logout_success'),
            'data' => null,
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $technician = $request->user();
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Hash::check($request->current_password, $technician->password)) {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_CURRENT_PASSWORD',
                'message' => __('messages.invalid_current_password'),
                'data' => null,
            ], 400);
        }

        $technician->update(['password' => Hash::make($request->new_password)]);

        return response()->json([
            'status' => 200,
            'response_code' => 'PASSWORD_CHANGED',
            'message' => __('messages.password_changed'),
            'data' => null,
        ], 200);
    }

    public function getRequestsSummary(Request $request)
    {
        $technician = Technician::with(['districts', 'products'])->find(auth()->user()->id);

        // Count all maintenance requests assigned to the technician
        $totalRequests = MaintenanceRequest::where('technician_id', $technician->id)->count();

        // Count requests by type
        $requestsByType = MaintenanceRequest::where('technician_id', $technician->id)
            ->select('type', \DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get();

        // Count requests by current status (latest status only)
        $requestsByStatus = \DB::table('maintenance_requests')
            ->join('request_statuses', function ($join) {
                $join->on('maintenance_requests.id', '=', 'request_statuses.maintenance_request_id')
                    ->whereRaw('request_statuses.id = (SELECT MAX(id) FROM request_statuses WHERE maintenance_requests.id = request_statuses.maintenance_request_id)');
            })
            ->select('request_statuses.status', \DB::raw('count(*) as count'))
            ->where('maintenance_requests.technician_id', $technician->id)
            ->groupBy('request_statuses.status')
            ->get();

        // Get the next request (nearest in time) from slots
        $nextRequest = MaintenanceRequest::with(['customer', 'address', 'products', 'statuses' => function ($query) {
            $query->latest();
        }, 'slot'])
            ->where('technician_id', $technician->id)
            ->whereHas('statuses', function ($query) {
                $query->where('status', 'pending')
                    ->orWhere('status', 'technician_assigned')
                    ->orWhere('status', 'technician_on_the_way');
            })
            ->whereHas('slot', function ($query) {
                $query->where('is_booked', true)
                    ->whereDate('date', '>=', now())
                    ->orderBy('date', 'asc')
                    ->orderBy('time', 'asc');
            })
            ->first();

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        // Get all requests (history) with slot info
        $allRequests = MaintenanceRequest::with(['customer', 'address', 'products', 'statuses' => function ($query) {
            $query->latest();
        }, 'slot'])
            ->where('technician_id', $technician->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
        // Count completed and ongoing requests
        $completedRequests = \DB::table('maintenance_requests')
            ->join('request_statuses', function ($join) {
                $join->on('maintenance_requests.id', '=', 'request_statuses.maintenance_request_id')
                    ->whereRaw('request_statuses.id = (SELECT MAX(id) FROM request_statuses WHERE maintenance_requests.id = request_statuses.maintenance_request_id)');
            })
            ->where('maintenance_requests.technician_id', $technician->id)
            ->where('request_statuses.status', 'paid')
            ->count();

        $ongoingRequests = $totalRequests - $completedRequests;

        return response()->json([
            'status' => 200,
            'response_code' => 'TECHNICIAN_REQUESTS_SUMMARY',
            'message' => __('messages.technician_requests_summary'),
            'data' => [
                'technician' => $technician,
                'total_requests' => $totalRequests,
                'requests_by_type' => $requestsByType,
                'requests_by_status' => $requestsByStatus,
                'completed_requests' => $completedRequests,
                'ongoing_requests' => $ongoingRequests,
                'next_request' => $nextRequest,
                'all_requests' => $allRequests,
            ],
        ]);
    }

    public function getAllRequests(Request $request)
    {
        $technician = $request->user();

        $query = MaintenanceRequest::with([
            'customer',
            'technician',
            'address',
            'slot',
            'products',
            'statuses',
            'invoice',
            'invoice.services',
            'invoice.spareParts',
        ])
            ->leftJoin('slots', 'maintenance_requests.slot_id', '=', 'slots.id')
            ->select('maintenance_requests.*')
            ->where('maintenance_requests.technician_id', $technician->id);


        if ($request->filled('types') && is_array($request->types)) {
            $query->whereIn('maintenance_requests.type', $request->types);
        }


        if ($request->filled('statuses') && is_array($request->statuses)) {
            $query->whereIn('maintenance_requests.last_status', $request->statuses);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('slots.date', [
                $request->start_date,
                $request->end_date,
            ]);
        }

        if ($request->filled('start_date') && !$request->filled('end_date')) {
            $query->whereDate('slots.date', '>=', $request->start_date);
        }

        if ($request->filled('end_date') && !$request->filled('start_date')) {
            $query->whereDate('slots.date', '<=', $request->end_date);
        }

        $query
            // upcoming first, then past, then null slots
            ->orderByRaw("
            CASE
                WHEN slots.date IS NULL THEN 2
                WHEN TIMESTAMP(slots.date, slots.time) >= NOW() THEN 0
                ELSE 1
            END
        ")
            // upcoming → nearest first
            ->orderByRaw("
            CASE
                WHEN slots.date IS NOT NULL
                 AND TIMESTAMP(slots.date, slots.time) >= NOW()
                THEN TIMESTAMP(slots.date, slots.time)
            END ASC
        ")
            // past → latest first
            ->orderByRaw("
            CASE
                WHEN slots.date IS NOT NULL
                 AND TIMESTAMP(slots.date, slots.time) < NOW()
                THEN TIMESTAMP(slots.date, slots.time)
            END DESC
        ")
            ->orderBy('maintenance_requests.id', 'desc');


        $perPage = (int) $request->input('per_page', 10);
        $page = (int) $request->input('page', 1);

        $maintenanceRequests = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status' => 200,
            'response_code' => 'TECHNICIAN_MAINTENANCE_REQUESTS_FETCHED',
            'message' => __('messages.maintenance_requests_fetched'),
            'data' => $maintenanceRequests,
        ], 200);
    }



    public function setOnTheWay(Request $request, $id)
    {
        $maintenanceRequest = MaintenanceRequest::findOrFail($id);

        if ($maintenanceRequest->technician_id != Auth::id()) {
            return response()->json([
                'status' => 403,
                'response_code' => 'UNAUTHORIZED_TECHNICIAN',
                'message' => 'You are not authorized to update this request.',
            ], 403);
        }

        if ($maintenanceRequest->current_status->status != 'technician_assigned') {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_STATUS',
                'message' => 'The request is not in technician_assigned status.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $maintenanceRequest->statuses()->create([
            'status' => 'technician_on_the_way',
            'notes' => $request->notes,
        ]);

        $maintenanceRequest->update([
            'status' => 'technician_on_the_way',
        ]);

        NotificationService::notifyCustomer(
            $maintenanceRequest->customer_id,
            __("notifications.customer.technician_on_the_way", ['id' => $maintenanceRequest->id]),
            $maintenanceRequest->id
        );

        return response()->json([
            'status' => 200,
            'response_code' => 'STATUS_UPDATED',
            'message' => 'Request status updated to technician_on_the_way.',
        ], 200);
    }

    public function setInProgress(Request $request, $id)
    {
        $maintenanceRequest = MaintenanceRequest::findOrFail($id);

        if ($maintenanceRequest->technician_id != Auth::id()) {
            return response()->json([
                'status' => 403,
                'response_code' => 'UNAUTHORIZED_TECHNICIAN',
                'message' => 'You are not authorized to update this request.',
            ], 403);
        }


        if ($maintenanceRequest->current_status->status != 'technician_on_the_way') {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_STATUS',
                'message' => 'The request is not in technician_on_the_way status.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'long' => 'required|numeric',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $maintenanceRequest->statuses()->create([
            'status' => 'in_progress',
            'latitude' => $request->lat,
            'longitude' => $request->long,
            'notes' => $request->notes,
        ]);

        $maintenanceRequest->update([
            'status' => 'in_progress',
        ]);

        NotificationService::notifyCustomer(
            $maintenanceRequest->customer_id,
            __("notifications.customer.in_progress", ['id' => $maintenanceRequest->id]),
            $maintenanceRequest->id
        );

        return response()->json([
            'status' => 200,
            'response_code' => 'STATUS_UPDATED',
            'message' => 'Request status updated to in_progress.',
        ], 200);
    }

    public function setWaitingForPayment(Request $request, $id)
    {
        $maintenanceRequest = MaintenanceRequest::findOrFail($id);

        if ($maintenanceRequest->technician_id != Auth::id()) {
            return response()->json([
                'status' => 403,
                'response_code' => 'UNAUTHORIZED_TECHNICIAN',
                'message' => 'You are not authorized to update this request.',
            ], 403);
        }

        if ($maintenanceRequest->current_status->status != 'in_progress') {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_STATUS',
                'message' => 'The request is not in in_progress status.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
            'spare_parts' => 'nullable|array',
            'spare_parts.*.id' => 'required|exists:spare_parts,id',
            'spare_parts.*.quantity' => 'required|integer|min:1',
            'services' => 'required|array',
            'services.*.id' => 'required|exists:services,id',
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

        $total = 0;

        foreach ($validatedData['spare_parts'] as $sparePart) {
            $sparePartModel = \App\Models\SparePart::findOrFail($sparePart['id']);
            $total += $sparePartModel->price * $sparePart['quantity'];
        }

        foreach ($validatedData['services'] as $service) {
            $serviceModel = \App\Models\Service::findOrFail($service['id']);
            $total += $serviceModel->price;
        }

        $invoice = new Invoice();
        $invoice->maintenance_request_id = $maintenanceRequest->id;
        $invoice->total = $total;
        $invoice->status = 'pending';
        $invoice->save();

        foreach ($validatedData['spare_parts'] as $sparePart) {
            $invoice->spareParts()->attach($sparePart['id'], [
                'quantity' => $sparePart['quantity'],
                'price' => \App\Models\SparePart::findOrFail($sparePart['id'])->price,
            ]);
        }

        foreach ($validatedData['services'] as $service) {
            $invoice->services()->attach($service['id']);
        }

        $maintenanceRequest->statuses()->create([
            'status' => 'waiting_for_payment',
            'notes' => $validatedData['notes'] ?? null,
        ]);

        $maintenanceRequest->update([
            'status' => 'waiting_for_payment',
            'invoice_number' => $invoice->id,
        ]);

        NotificationService::notifyCustomer(
            $maintenanceRequest->customer_id,
            __("notifications.customer.waiting_for_payment", ['id' => $maintenanceRequest->id]),
            $maintenanceRequest->id
        );

        $invoice = $invoice->load(['spareParts', 'services']);

        return response()->json([
            'status' => 200,
            'response_code' => 'STATUS_UPDATED',
            'message' => 'Request status updated to waiting_for_payment and invoice created.',
            'data' => [
                'maintenance_request' => $maintenanceRequest,
                'invoice' => $invoice,
            ],
        ], 200);
    }

    public function confirmCashPayment(Request $request, $id)
    {
        $maintenanceRequest = MaintenanceRequest::findOrFail($id);

        if ($maintenanceRequest->technician_id != Auth::id()) {
            return response()->json([
                'status' => 403,
                'response_code' => 'UNAUTHORIZED_TECHNICIAN',
                'message' => 'You are not authorized to confirm payment for this request.',
            ], 403);
        }

        if ($maintenanceRequest->current_status->status != 'waiting_for_technician_confirm_payment') {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_REQUEST_STATUS',
                'message' => 'The request is not in waiting_for_technician_confirm_payment status.',
            ], 400);
        }

        $invoice = $maintenanceRequest->invoice;

        if (!$invoice || $invoice->payment_method != 'cash' || $invoice->status != 'pending') {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_INVOICE',
                'message' => 'The invoice is not valid for cash confirmation.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'required|string',
            'lat' => 'required|numeric',
            'long' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $invoice->update([
            'status' => 'completed',
        ]);



        $maintenanceRequest->statuses()->create([
            'status' => 'completed',
            'notes' => $request->input('notes'),
            'latitude' => $request->input('lat'),
            'longitude' => $request->input('long'),
        ]);

        $maintenanceRequest->update([
            'last_status' => 'completed',
        ]);

        NotificationService::notifyCustomer(
            $maintenanceRequest->customer_id,
            __("notifications.customer.payment_confirmed", ['id' => $maintenanceRequest->id]),
            $maintenanceRequest->id
        );

        return response()->json([
            'status' => 200,
            'response_code' => 'PAYMENT_CONFIRMED',
            'message' => 'Cash payment confirmed and request marked as completed.',
            'data' => [
                'maintenance_request' => $maintenanceRequest->load(['statuses', 'customer', 'slot', 'technician', 'address', 'products', 'invoice', 'invoice.services', 'invoice.spareParts']),
            ],
        ], 200);
    }


    public function finishInstallation(Request $request, $id)
    {
        $maintenanceRequest = MaintenanceRequest::findOrFail($id);

        if ($maintenanceRequest->technician_id != Auth::id()) {
            return response()->json([
                'status' => 403,
                'response_code' => 'UNAUTHORIZED_TECHNICIAN',
                'message' => 'You are not authorized to update this request.',
            ], 403);
        }

        if ($maintenanceRequest->type != 'new_installation') {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_REQUEST_TYPE',
                'message' => 'This request is not a new installation.',
            ], 400);
        }

        if ($maintenanceRequest->current_status->status != 'in_progress') {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_STATUS',
                'message' => 'The request is not in progress.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $maintenanceRequest->statuses()->create([
            'status' => 'completed',
            'notes' => $request->notes,
        ]);

        $maintenanceRequest->update([
            'last_status' => 'completed',
        ]);

        NotificationService::notifyCustomer(
            $maintenanceRequest->customer_id,
            __("notifications.customer.installation_completed", ['id' => $maintenanceRequest->id]),
            $maintenanceRequest->id
        );

        return response()->json([
            'status' => 200,
            'response_code' => 'INSTALLATION_COMPLETED',
            'message' => 'Installation request marked as completed.',
            'data' => $maintenanceRequest->load([
                'statuses',
                'customer',
                'slot',
                'technician',
                'address',
                'invoice',
                'invoice.services',
                'invoice.spareParts',
                'products'
            ]),
        ], 200);
    }

    public function updateFcmToken(Request $request)
    {
        $technician = $request->user();

        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $technician->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'status' => 200,
            'response_code' => 'FCM_TOKEN_UPDATED',
            'message' => __('messages.fcm_token_updated'),
            'data' => $technician,
        ], 200);
    }

    //freelancer technicians methods
    public function getFreelancerOpenRequests(Request $request)
    {
        $technician = $request->user();

        if (! $technician->is_freelancer) {
            return response()->json([
                'status' => 403,
                'response_code' => 'NOT_FREELANCER',
                'message' => 'Only freelancer technicians can access this list.',
            ], 403);
        }

        $districtIds = $technician->districts()->pluck('districts.id')->toArray();
        $productIds = $technician->products()->pluck('products.id')->toArray();
    // $requests = MaintenanceRequest::where('is_open_for_freelancers', true)->get();
    //     dd($requests);
        $requests = MaintenanceRequest::with([
            'customer',
            'address.district',
            'products',
            'statuses',
        ])
            ->where('is_open_for_freelancers', true)
            ->whereNull('technician_id')
            ->whereNull('slot_id')
            ->whereIn('last_status', ['pending'])
            ->whereHas('address', function ($query) use ($districtIds) {
                $query->whereIn('district_id', $districtIds);
            })
            ->whereHas('products', function ($query) use ($productIds) {
                $query->whereIn('products.id', $productIds);
            })
            ->latest()
            ->paginate((int) $request->input('per_page', 10));

        return response()->json([
            'status' => 200,
            'response_code' => 'FREELANCER_OPEN_REQUESTS_FETCHED',
            'message' => 'Open freelancer requests fetched successfully.',
            'data' => $requests,
        ], 200);
    }

    public function claimFreelancerRequest(Request $request, $id)
    {
        $technician = $request->user();

        if (! $technician->is_freelancer) {
            return response()->json([
                'status' => 403,
                'response_code' => 'NOT_FREELANCER',
                'message' => 'Only freelancer technicians can claim urgent requests.',
            ], 403);
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $id, $technician) {
            $maintenanceRequest = MaintenanceRequest::with([
                'customer',
                'address.district',
                'products',
                'statuses',
            ])
                ->lockForUpdate()
                ->findOrFail($id);

            if (! $maintenanceRequest->is_open_for_freelancers) {
                return response()->json([
                    'status' => 400,
                    'response_code' => 'REQUEST_NOT_OPEN_FOR_FREELANCERS',
                    'message' => 'This request is not open for freelancer technicians.',
                ], 400);
            }

            if ($maintenanceRequest->technician_id || $maintenanceRequest->slot_id) {
                return response()->json([
                    'status' => 400,
                    'response_code' => 'REQUEST_ALREADY_ASSIGNED',
                    'message' => 'This request has already been assigned.',
                ], 400);
            }

            if ($maintenanceRequest->last_status !== 'pending') {
                return response()->json([
                    'status' => 400,
                    'response_code' => 'INVALID_STATUS',
                    'message' => 'Only pending requests can be claimed.',
                ], 400);
            }

            $district = $maintenanceRequest->address?->district;
            $requestProductIds = $maintenanceRequest->products->pluck('id')->toArray();

            $canServeDistrict = $district && $technician->districts()
                ->where('districts.id', $district->id)
                ->exists();

            $canServeProducts = $technician->products()
                ->whereIn('products.id', $requestProductIds)
                ->exists();

            if (! $canServeDistrict || ! $canServeProducts) {
                return response()->json([
                    'status' => 403,
                    'response_code' => 'TECHNICIAN_NOT_ELIGIBLE',
                    'message' => 'Technician is not eligible for this request district or products.',
                ], 403);
            }

            $requiredSlots = max(1, (int) ceil((float) ($maintenanceRequest->hours ?? 1)));

            $start = now()->copy()->minute(0)->second(0);

            $slotIds = [];

            for ($i = 0; $i < $requiredSlots; $i++) {
                $slotTime = $start->copy()->addHours($i);

                $existingBookedSlot = Slot::query()
                    ->where('technician_id', $technician->id)
                    ->whereDate('date', $slotTime->toDateString())
                    ->whereTime('time', $slotTime->format('H:i:s'))
                    ->where('is_booked', true)
                    ->lockForUpdate()
                    ->first();

                if ($existingBookedSlot) {
                    return response()->json([
                        'status' => 400,
                        'response_code' => 'TECHNICIAN_HAS_BOOKED_SLOT',
                        'message' => 'Technician already has a booked slot in the required time range.',
                    ], 400);
                }

                $slot = Slot::query()
                    ->where('technician_id', $technician->id)
                    ->whereDate('date', $slotTime->toDateString())
                    ->whereTime('time', $slotTime->format('H:i:s'))
                    ->lockForUpdate()
                    ->first();

                if (! $slot) {
                    $slot = Slot::create([
                        'technician_id' => $technician->id,
                        'date' => $slotTime->toDateString(),
                        'time' => $slotTime->format('H:i:s'),
                        'is_booked' => true,
                    ]);
                } else {
                    $slot->update([
                        'is_booked' => true,
                    ]);
                }

                $slotIds[] = $slot->id;
            }

            $mainSlotId = $slotIds[0];
            $extraSlotIds = array_slice($slotIds, 1);

            $maintenanceRequest->update([
                'technician_id' => $technician->id,
                'slot_id' => $mainSlotId,
                'extra_slot_id' => $extraSlotIds,
                'is_open_for_freelancers' => false,
                'freelancer_assigned_at' => now(),
                'last_status' => 'technician_assigned',
            ]);

            $maintenanceRequest->statuses()->create([
                'status' => 'technician_assigned',
                'notes' => 'Freelancer technician claimed the urgent request.',
            ]);

            NotificationService::notifyCustomer(
                $maintenanceRequest->customer_id,
                __("notifications.customer.technician_assigned", ['id' => $maintenanceRequest->id]),
                $maintenanceRequest->id
            );

            NotificationService::notifyTechnician(
                $technician->id,
                __("notifications.technician.new_request", ['id' => $maintenanceRequest->id]),
                $maintenanceRequest->id
            );

            return response()->json([
                'status' => 200,
                'response_code' => 'FREELANCER_REQUEST_CLAIMED',
                'message' => 'Request assigned to freelancer technician successfully.',
                'data' => [
                    'maintenance_request' => $maintenanceRequest->fresh([
                        'customer',
                        'technician',
                        'address',
                        'slot',
                        'products',
                        'statuses',
                    ]),
                    'slot_id' => $mainSlotId,
                    'extra_slot_ids' => $extraSlotIds,
                ],
            ], 200);
        });
    }
}
