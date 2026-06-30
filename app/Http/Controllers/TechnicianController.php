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
    public function index(Request $request)
    {
        $authTechnician = $request->user();

        if (! $authTechnician instanceof Technician) {
            return response()->json([
                'status' => 403,
                'response_code' => 'TECHNICIAN_ONLY',
                'message' => 'Only authenticated technicians can access technicians list.',
                'data' => null,
            ], 403);
        }

        if (! $authTechnician->authorized || ! $authTechnician->activated) {
            return response()->json([
                'status' => 403,
                'response_code' => 'TECHNICIAN_NOT_AUTHORIZED',
                'message' => 'Technician is not authorized.',
                'data' => null,
            ], 403);
        }

        $technicians = Technician::query()
            ->select([
                'id',
                'first_name',
                'last_name',
                'phone',
                'email',
                'rating',
                'reviews_count',
                'is_freelancer',
                'sap_id',
                'site_id',
                'storage_location',
            ])
            ->where('authorized', true)
            ->where('activated', true)
            ->when($request->boolean('exclude_self'), fn ($query) => $query->whereKeyNot($authTechnician->id))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return response()->json([
            'status' => 200,
            'response_code' => 'TECHNICIANS_FETCHED',
            'message' => 'Technicians fetched successfully.',
            'data' => $technicians,
        ], 200);
    }

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
            ->where('request_statuses.status', 'completed')
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
            'address.city',
            'address.district',
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
            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
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
            'latitude' => $request->lat,
            'longitude' => $request->long,

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
            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
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

            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
            'spare_parts' => 'nullable|array',
            'spare_parts.*.id' => 'required|exists:spare_parts,id',
            'spare_parts.*.quantity' => 'required|integer|min:1',
            'services' => 'nullable|array',
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

        foreach (($validatedData['spare_parts'] ?? []) as $sparePart) {
            $sparePartModel = \App\Models\SparePart::findOrFail($sparePart['id']);
            $total += $sparePartModel->price * $sparePart['quantity'];
        }

        foreach ($validatedData['services'] as $service) {
            $serviceModel = \App\Models\Service::findOrFail($service['id']);
            $total += $serviceModel->price;
        }

        $invoice = new Invoice();
        $invoice->maintenance_request_id = $maintenanceRequest->id;
        $invoice->invoice_type = 'final';
        $invoice->total = $total;
        $invoice->status = 'pending';
        $invoice->save();

        foreach (($validatedData['spare_parts'] ?? []) as $sparePart) {
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
            'latitude' => $validatedData['lat'],
            'longitude' => $validatedData['long'],
        ]);

        $maintenanceRequest->update([
            'last_status' => 'waiting_for_payment',
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

        $sapResponse = app(\App\Http\Controllers\SapController::class)
            ->createSalesOrder($maintenanceRequest->fresh(), 'Cash');

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

            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
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
            'latitude' => $request->lat,
            'longitude' => $request->long,
        ]);

        $invoice = $this->createZeroServiceInvoice($maintenanceRequest, 'New installation completed without charge.');

        $maintenanceRequest->update([
            'last_status' => 'completed',
            'invoice_number' => $invoice->id,
        ]);

        NotificationService::notifyCustomer(
            $maintenanceRequest->customer_id,
            __("notifications.customer.installation_completed", ['id' => $maintenanceRequest->id]),
            $maintenanceRequest->id
        );

        // $sapResponse = app(\App\Http\Controllers\SapController::class)
        //     ->createSalesOrder($maintenanceRequest->fresh(), 'Cash');

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

    public function completeWithoutPayment(Request $request, $id)
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
                'message' => 'The request is not in progress.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $invoice = $this->createZeroServiceInvoice(
            $maintenanceRequest,
            'Maintenance request completed without charge.'
        );

        $maintenanceRequest->statuses()->create([
            'status' => 'completed',
            'notes' => $request->notes,
            'latitude' => $request->lat,
            'longitude' => $request->long,
        ]);

        $maintenanceRequest->update([
            'last_status' => 'completed',
            'invoice_number' => $invoice->id,
        ]);

        return response()->json([
            'status' => 200,
            'response_code' => 'REQUEST_COMPLETED_WITHOUT_PAYMENT',
            'message' => 'Request completed with a zero invoice.',
            'data' => [
                'maintenance_request' => $maintenanceRequest->load(['statuses', 'invoice', 'invoice.services', 'invoice.spareParts']),
                'invoice' => $invoice,
            ],
        ], 200);
    }

    public function updatePendingInvoice(Request $request, $id)
    {
        $maintenanceRequest = MaintenanceRequest::with(['invoice.services', 'invoice.spareParts'])->findOrFail($id);

        if ($maintenanceRequest->technician_id != Auth::id()) {
            return response()->json([
                'status' => 403,
                'response_code' => 'UNAUTHORIZED_TECHNICIAN',
                'message' => 'You are not authorized to update this request invoice.',
            ], 403);
        }

        $invoice = $maintenanceRequest->invoices()
            ->where('invoice_type', 'final')
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (! $invoice) {
            return response()->json([
                'status' => 400,
                'response_code' => 'PENDING_INVOICE_NOT_FOUND',
                'message' => 'No unpaid final invoice found for this request.',
            ], 400);
        }

        if (! in_array($maintenanceRequest->last_status, ['waiting_for_payment', 'waiting_for_technician_confirm_payment'], true)) {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVOICE_NOT_EDITABLE',
                'message' => 'Invoice can only be updated before customer payment is completed.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'spare_parts' => 'nullable|array',
            'spare_parts.*.id' => 'required|exists:spare_parts,id',
            'spare_parts.*.quantity' => 'required|integer|min:1',
            'spare_parts.*.price' => 'nullable|numeric|min:0',
            'services' => 'nullable|array',
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
        $before = $this->invoiceSnapshot($invoice->load(['services', 'spareParts']));

        $services = array_key_exists('services', $validatedData)
            ? $validatedData['services']
            : $invoice->services->map(fn ($service) => ['id' => $service->id])->all();
        $spareParts = array_key_exists('spare_parts', $validatedData)
            ? $validatedData['spare_parts']
            : $invoice->spareParts
                ->map(fn ($part) => [
                    'id' => $part->id,
                    'quantity' => (int) ($part->pivot->quantity ?? 1),
                    'price' => (float) ($part->pivot->price ?? $part->price ?? 0),
                ])
                ->all();

        $servicesTotal = 0;
        $serviceIds = [];

        foreach ($services as $service) {
            $serviceModel = \App\Models\Service::findOrFail($service['id']);
            $servicesTotal += (float) $serviceModel->price;
            $serviceIds[] = $serviceModel->id;
        }

        $sparePartsTotal = 0;
        $sparePartSync = [];

        foreach ($spareParts as $sparePart) {
            $sparePartModel = \App\Models\SparePart::findOrFail($sparePart['id']);
            $price = array_key_exists('price', $sparePart)
                ? (float) $sparePart['price']
                : (float) $sparePartModel->price;
            $quantity = (int) $sparePart['quantity'];

            $sparePartsTotal += $price * $quantity;
            $sparePartSync[$sparePartModel->id] = [
                'quantity' => $quantity,
                'price' => $price,
            ];
        }

        $invoice->services()->sync(array_values(array_unique($serviceIds)));
        $invoice->spareParts()->sync($sparePartSync);

        $invoice->refresh()->load(['services', 'spareParts']);

        $notes = collect($invoice->notes ?? [])
            ->push([
                'type' => 'invoice_updated_by_technician',
                'technician_id' => Auth::id(),
                'note' => $validatedData['notes'] ?? null,
                'before' => $before,
                'after' => $this->invoiceSnapshot($invoice),
                'created_at' => now()->toDateTimeString(),
            ])
            ->values()
            ->all();

        $invoice->update([
            'total' => $servicesTotal + $sparePartsTotal,
            'payment_method' => $validatedData['payment_method'] ?? $invoice->payment_method,
            'notes' => $notes,
        ]);

        return response()->json([
            'status' => 200,
            'response_code' => 'INVOICE_UPDATED',
            'message' => 'Invoice updated successfully.',
            'data' => [
                'maintenance_request' => $maintenanceRequest->fresh(['statuses', 'invoice', 'invoice.services', 'invoice.spareParts']),
                'invoice' => $invoice->fresh(['services', 'spareParts']),
            ],
        ], 200);
    }

    private function invoiceSnapshot(Invoice $invoice): array
    {
        return [
            'payment_method' => $invoice->payment_method,
            'total' => (float) $invoice->total,
            'services' => $invoice->services
                ->map(fn ($service): array => [
                    'id' => $service->id,
                    'name' => $service->name_en ?? $service->name_ar,
                    'price' => (float) ($service->price ?? 0),
                ])
                ->values()
                ->all(),
            'spare_parts' => $invoice->spareParts
                ->map(fn ($part): array => [
                    'id' => $part->id,
                    'name' => $part->name_en ?? $part->name_ar,
                    'quantity' => (int) ($part->pivot->quantity ?? 1),
                    'price' => (float) ($part->pivot->price ?? $part->price ?? 0),
                ])
                ->values()
                ->all(),
        ];
    }

    private function createZeroServiceInvoice(MaintenanceRequest $maintenanceRequest, string $note): Invoice
    {
        return $maintenanceRequest->invoices()->firstOrCreate(
            [
                'invoice_type' => 'zero_service',
                'status' => 'completed',
            ],
            [
                'total' => 0,
                'payment_method' => null,
                'notes' => [
                    [
                        'note' => $note,
                        'created_at' => now()->toDateTimeString(),
                    ],
                ],
            ]
        );
    }

    public function updateFcmToken(Request $request)
    {
        $technician = $request->user();

        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string|max:255',
            'preferred_locale' => 'nullable|in:ar,en',
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
            'preferred_locale' => $request->input('preferred_locale', substr((string) $request->header('Accept-Language', 'en'), 0, 2)) === 'ar' ? 'ar' : 'en',
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
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereIn('type', ['regular_maintenance', 'emergency_maintenance'])
                        ->where('last_status', 'service_paid');
                })->orWhere(function ($query) {
                    $query->whereNotIn('type', ['regular_maintenance', 'emergency_maintenance'])
                        ->where('last_status', 'pending');
                });
            })
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

            $requiredStatus = $maintenanceRequest->requiresVisitFeePayment()
                ? 'service_paid'
                : 'pending';

            if ($maintenanceRequest->last_status !== $requiredStatus) {
                return response()->json([
                    'status' => 400,
                    'response_code' => 'INVALID_STATUS',
                    'message' => $maintenanceRequest->requiresVisitFeePayment()
                        ? 'Visit fee must be paid before this request can be claimed.'
                        : 'Only pending requests can be claimed.',
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

    public function confirmMachinePayment(Request $request, $id)
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

        if (!$invoice || $invoice->payment_method != 'machine' || $invoice->status != 'pending') {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_INVOICE',
                'message' => 'The invoice is not valid for machine confirmation.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'machine_pic' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
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

        if ($request->hasFile('machine_pic')) {
            $machinePic = $request
                ->file('machine_pic')
                ->store('machine_pics', 'public');

            $invoice->update([
                'machine_pic' => $machinePic,
            ]);
        }

        $maintenanceRequest->statuses()->create([
            'status' => 'completed',
            'notes' => 'تم الدفع عن طريق الجهاز وتم تأكيد الدفع من الفني.',
            'latitude' => $request->input('lat'),
            'longitude' => $request->input('long'),
        ]);

        $maintenanceRequest->update([
            'last_status' => 'completed',
        ]);

        $sapResponse = app(\App\Http\Controllers\SapController::class)
            ->createSalesOrder($maintenanceRequest->fresh(), 'Machine');

        NotificationService::notifyCustomer(
            $maintenanceRequest->customer_id,
            __("notifications.customer.payment_confirmed", ['id' => $maintenanceRequest->id]),
            $maintenanceRequest->id
        );


        return response()->json([
            'status' => 200,
            'response_code' => 'PAYMENT_CONFIRMED',
            'message' => 'Machine payment confirmed and request marked as completed.',
            'data' => [
                'maintenance_request' => $maintenanceRequest->load(['statuses', 'customer', 'slot', 'technician', 'address', 'products', 'invoice', 'invoice.services', 'invoice.spareParts']),
            ],
        ], 200);
    }

    public function cancelRequest(Request $request, $id)
    {
        $maintenanceRequest = MaintenanceRequest::findOrFail($id);
        $technician = $request->user();
        if ($technician->id !== $maintenanceRequest->technician_id) {
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

        $validator = Validator::make($request->all(), [
            'notes' => 'required|string',
            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
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
            'status' => 'canceled',
            'notes' => $request->notes ?? null,
            'latitude' => $request->lat,
            'longitude' => $request->long,
        ]);

        $maintenanceRequest->update([
            'last_status' => 'canceled',
        ]);

        if ($maintenanceRequest->slot_id) {
            $oldSlot = Slot::find($maintenanceRequest->slot_id);
            if ($oldSlot) {
                $oldSlot->update(['is_booked' => false]);
            }
            $slotIds = collect([$maintenanceRequest->slot_id])
                ->merge($maintenanceRequest->extra_slot_id ?? [])
                ->filter()
                ->unique()
                ->toArray();

            Slot::whereIn('id', $slotIds)->update([
                'is_booked' => false,
            ]);
        }


        return response()->json([
            'status' => 200,
            'response_code' => 'REQUEST_CANCELED',
            'message' => __('messages.request_canceled'),
            'data' => $maintenanceRequest,
        ], 200);
    }
}
