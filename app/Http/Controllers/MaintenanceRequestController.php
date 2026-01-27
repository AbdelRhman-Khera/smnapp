<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\MaintenanceRequest;
use App\Models\Product;
use App\Models\Slot;
use App\Models\Technician;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Paytabscom\Laravel_paytabs\Facades\paypage;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use function Pest\Laravel\json;

class MaintenanceRequestController extends Controller
{

    public function index(Request $request)
    {
        $customer = $request->user();

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
            ->where('maintenance_requests.customer_id', $customer->id);


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


    /**
     * Display the specified maintenance request.
     */
    public function show($id)
    {
        $maintenanceRequest = MaintenanceRequest::with([
            'customer',
            'slot',
            'technician',
            'address',
            'products',
            'statuses',
            'invoice',
            'invoice.services',
            'invoice.spareParts',
            'feedback'
        ])->findOrFail($id);

        // hide technician phone before the appointment day
        $mask = '#########';

        $slotDate = optional($maintenanceRequest->slot)->date;
        if ($slotDate && $maintenanceRequest->technician) {

            // Compare by date only (ignore time)
            $today = Carbon::now()->startOfDay();
            $appointmentDay = Carbon::parse($slotDate)->startOfDay();

            if ($today->lt($appointmentDay)) {
                $maintenanceRequest->technician->phone = $mask;
            }
        }

        return response()->json([
            'status' => 200,
            'response_code' => 'MAINTENANCE_REQUEST_FETCHED',
            'message' => __('messages.maintenance_request_fetched'),
            'data' => $maintenanceRequest,
        ], 200);
    }



    public function create(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:new_installation,regular_maintenance,emergency_maintenance',
            // Accept either:
            // 1) products: [1,2,3]
            // 2) products: [{product_id: 1, quantity: 2}, ...]
            'products' => 'required|array|min:1',
            'address_id' => 'required|exists:addresses,id',
            'problem_description' => 'nullable|string',
            'last_maintenance_date' => 'nullable|date',
            'photos' => 'nullable|array',
        ]);

        $validator->after(function ($validator) use ($request) {
            try {
                $this->normalizeProductsForAttach($request->input('products', []));
            } catch (\InvalidArgumentException $e) {
                $validator->errors()->add('products', $e->getMessage());
            }
        });

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

        $maintenanceRequest->products()->attach(
            $this->normalizeProductsForAttach($request->products)
        );

        $maintenanceRequest->statuses()->create([
            'status' => 'pending',
        ]);

        $maintenanceRequest->last_status = 'pending';
        $maintenanceRequest->save();
        $maintenanceRequest->load(['customer', 'slot', 'technician', 'address', 'products', 'statuses', 'invoice', 'feedback']);

        return response()->json([
            'status' => 200,
            'response_code' => 'REQUEST_CREATED',
            'message' => __('messages.request_created'),
            'data' => $maintenanceRequest,
        ], 200);
    }


    private function normalizeProductsForAttach(array $products): array
    {
        $attach = [];

        foreach ($products as $item) {
            // Backwards compatible: numeric IDs
            if (is_numeric($item)) {
                $productId = (int) $item;
                if ($productId <= 0) {
                    throw new \InvalidArgumentException('Invalid product id.');
                }
                $attach[$productId] = ['quantity' => 1];
                continue;
            }

            if (!is_array($item)) {
                throw new \InvalidArgumentException('Products must be an array of ids or objects with product_id and quantity.');
            }

            $productId = isset($item['product_id']) ? (int) $item['product_id'] : 0;
            $quantity = isset($item['quantity']) ? (int) $item['quantity'] : 0;

            if ($productId <= 0) {
                throw new \InvalidArgumentException('Each product must have a valid product_id.');
            }

            if ($quantity <= 0) {
                throw new \InvalidArgumentException('Each product must have a quantity of at least 1.');
            }

            $attach[$productId] = ['quantity' => $quantity];
        }

        if (empty($attach)) {
            throw new \InvalidArgumentException('At least one product is required.');
        }

        // Ensure all product ids exist.
        $found = Product::whereIn('id', array_keys($attach))->pluck('id')->all();
        $missing = array_diff(array_keys($attach), $found);
        if (!empty($missing)) {
            throw new \InvalidArgumentException('Some selected products do not exist.');
        }

        return $attach;
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

        $district = $maintenanceRequest->address->district;
        $products = $maintenanceRequest->products->pluck('id')->toArray();

        $availableDays = $district->available_days ?? [];
        $date = Carbon::parse($request->date);
        $dayOfWeek = $date->englishDayOfWeek;

        if (!empty($availableDays) && !in_array($dayOfWeek, $availableDays)) {
            return response()->json([
                'status' => 200,
                'response_code' => 'NO_SLOTS_AVAILABLE',
                'message' => 'No slots available on this day for the selected district.',
                'data' => [],
            ], 200);
        }

        $technicians = Technician::whereHas('districts', function ($query) use ($district) {
            $query->where('name_en', $district->name_en);
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
            'last_status' => 'technician_assigned',
        ]);

        // Update the request status
        $maintenanceRequest->statuses()->create([
            'status' => 'technician_assigned',
        ]);

        NotificationService::notifyCustomer(
            $maintenanceRequest->customer_id,
            __("notifications.customer.technician_assigned", ['id' => $maintenanceRequest->id]),
            $maintenanceRequest->id
        );

        NotificationService::notifyTechnician(
            $newSlot->technician_id,
            __("notifications.technician.new_request", ['id' => $maintenanceRequest->id]),
            $maintenanceRequest->id
        );

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

        $maintenanceRequest->update([
            'last_status' => 'canceled',
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

        if ($validatedData['payment_method'] == 'cash') {
            $maintenanceRequest->statuses()->create([
                'status' => 'waiting_for_technician_confirm_payment',
            ]);
            $maintenanceRequest->update([
                'last_status' => 'waiting_for_technician_confirm_payment',
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

        ////paytabs
        if ($validatedData['payment_method'] == 'online') {
            $cart_id = 'MR-' . $maintenanceRequest->id;
            $cart_amount = $invoice->total;
            $cart_description = "Payment for Maintenance Request #{$maintenanceRequest->id}";
            $name = $maintenanceRequest->customer->first_name . ' ' . $maintenanceRequest->customer->last_name;
            $email = $maintenanceRequest->customer->email ?? 'test@samnan.com';
            $phone = $maintenanceRequest->customer->phone;
            $street1 = $maintenanceRequest->address->street ?? 'N/A';
            $city = $maintenanceRequest->address->city->name ?? 'N/A';
            $state = $maintenanceRequest->address->district->name ?? 'N/A';
            $country = 'SA';
            $zip = '00000';
            $ip = $request->ip();
            $return = route('payment.success', ['id' => $maintenanceRequest->id]);
            $callback = route('payment.callback', ['id' => $maintenanceRequest->id]);
            $language = 'en';
            $pay = paypage::sendPaymentCode('all')
                ->sendTransaction('sale', 'ecom')
                ->sendCart($cart_id, $cart_amount, $cart_description)
                ->sendCustomerDetails($name, $email, $phone, $street1, $city, $state, $country, $zip, $ip)
                ->shipping_same_billing()
                ->sendURLs($return, $callback)
                ->sendLanguage($language)
                ->create_pay_page();;

            return response()->json([
                'status' => 200,
                'response_code' => 'PAYMENT_LINK_GENERATED',
                'message' => 'Payment link generated successfully.',
                'payment_url' => $pay->getTargetUrl(),
            ], 200);
        }
    }

    public function paymentCallback(Request $request, $id)
    {
        $maintenanceRequest = MaintenanceRequest::with('invoice')->findOrFail($id);

        if ($request->respStatus == 'A') {
            $maintenanceRequest->statuses()->create([
                'status' => 'completed',
                'notes'  => $request->tranRef,
            ]);
            $maintenanceRequest->update([
                'last_status' => 'completed',
            ]);

            $maintenanceRequest->invoice->update([
                'status' => 'completed',
                'payment_details' => $request->tranRef,
            ]);

            return response()->json([
                'status' => 200,
                'response_code' => 'PAYMENT_SUCCESSFUL',
                'message' => 'Payment completed successfully.',
                'data' => $maintenanceRequest->load(['statuses', 'invoice', 'feedback', 'customer', 'slot', 'technician', 'address', 'products']),
            ], 200);
        } else {
            return response()->json([
                'status' => 400,
                'response_code' => 'PAYMENT_FAILED',
                'message' => 'Payment failed. Please try again.',
            ], 400);
        }
    }

    public function paymentCallbackMobile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'responseStatus' => 'required',
            'requestId' => 'required|exists:maintenance_requests,id',
            'transactionReference' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $maintenanceRequest = MaintenanceRequest::with('invoice')->findOrFail($request->requestId);

        if ($request->responseStatus == 'A') {
            $maintenanceRequest->statuses()->create([
                'status' => 'completed',
                'notes'  => $request->transactionReference,
            ]);
            $maintenanceRequest->update([
                'last_status' => 'completed',
            ]);

            $maintenanceRequest->invoice->update([
                'payment_method' => 'online',
                'status' => 'completed',
                'payment_details' => $request->transactionReference,
            ]);

            return response()->json([
                'status' => 200,
                'response_code' => 'PAYMENT_SUCCESSFUL',
                'message' => 'Payment completed successfully.',
                'data' => $maintenanceRequest->load(['statuses', 'invoice', 'feedback', 'customer', 'slot', 'technician', 'address', 'products']),
            ], 200);
        } else {
            return response()->json([
                'status' => 400,
                'response_code' => 'PAYMENT_FAILED',
                'message' => 'Payment failed. Please try again.',
            ], 400);
        }
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

    public function getSpecificProductByOrder($id)
    {
        $mRequest = MaintenanceRequest::where('sap_order_id', $id)
            ->where('last_status', '!=', 'canceled')
            ->first();
        if ($mRequest) {
            return response()->json([
                'message' => __('messages.invalid_order_id'),
                'maintenance_request_id' => $mRequest->id,
            ], 409);
        }
        try {

            // Call SAP API
            $response = Http::withBasicAuth('Test', '@lexandria@Rise12345')
                // ->withoutVerifying()   // local for testing only
                ->acceptJson()
                ->post('https://dev.samnan.com.sa/sap/bc/zrestful_sales?sap-client=300&Action=GET_INVOICE_LINE', [
                    'VBELN' => $id
                ]);

            if (!$response->successful()) {
                return response()->json([
                    'status' => 400,
                    'response_code' => 'INVALID_ORDER',
                    'message' => __('messages.invalid_order_id'),
                ], 400);
            }

            $sapItems = $response->json();
            if (empty($sapItems) || !is_array($sapItems)) {
                return response()->json([
                    'status' => 404,
                    'response_code' => 'NO_PRODUCTS_FOUND',
                    'message' => __('messages.no_products_found'),
                ], 404);
            }

            $sapItems = collect($sapItems)
                ->filter(fn($row) => !empty($row['MATNR']))
                ->map(fn($row) => [
                    'sap_id' => (string) $row['MATNR'],
                    'qty'    => (float) ($row['QTY'] ?? 0),
                ]);

            $qtyBySapId = $sapItems
                ->groupBy('sap_id')
                ->map(fn($rows) => $rows->sum('qty')); // [sap_id => total_qty]

            $products = Product::whereIn('sap_id', $qtyBySapId->keys()->all())->get();

            $data = $products->map(function ($product) use ($qtyBySapId) {
                return [
                    'product' => $product,
                    'qty'     => $qtyBySapId->get((string) $product->sap_id, 0),
                ];
            })->values();

            return response()->json([
                'status' => 200,
                'response_code' => 'ORDER_PRODUCTS_FETCHED',
                'message' => __('messages.products_fetched'),
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'response_code' => 'INTERNAL_SERVER_ERROR',
                'message' => __('messages.internal_server_error'),
                'debug' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function testpay()
    {
        $maintenanceRequest = MaintenanceRequest::with('invoice')->findOrFail(15);
        $cart_id = uniqid('MR-');
        // $cart_id = 'MR-' . $maintenanceRequest->id;
        $cart_amount = $maintenanceRequest->invoice->total;
        $cart_description = "Payment for Maintenance Request #{$maintenanceRequest->id}";
        $name = $maintenanceRequest->customer->first_name . ' ' . $maintenanceRequest->customer->last_name;
        $email = $maintenanceRequest->customer->email ?? 'test@test.com';
        $phone = $maintenanceRequest->customer->phone;
        $street1 = $maintenanceRequest->address->street ?? 'N/A';
        $city = $maintenanceRequest->address->city->name ?? 'N/A';
        $state = $maintenanceRequest->address->district->name ?? 'N/A';
        $country = 'SA';
        $zip = '00000';
        $ip = '127.0.0.1';
        // $return = route('payment.success', ['id' => $maintenanceRequest->id]);
        // $return = 'https://webhook.site/6fd757f3-d75e-4c4b-b8a9-ad5185bcbfdd';
        $return = 'https://app.rezeqstore.com/api/v1/payment/success/' . $maintenanceRequest->id;
        $callback = 'https://app.rezeqstore.com/api/v1/payment/callback/' . $maintenanceRequest->id;
        // $callback = 'https://webhook.site/6fd757f3-d75e-4c4b-b8a9-ad5185bcbfdd';
        // $callback = route('payment.callback', ['id' => $maintenanceRequest->id]);
        $language = 'en';
        $pay = paypage::sendPaymentCode('all')
            ->sendTransaction('sale', 'ecom')
            ->sendCart($cart_id, $cart_amount, $cart_description)
            ->sendCustomerDetails($name, $email, $phone, $street1, $city, $state, $country, $zip, $ip)
            ->shipping_same_billing()
            ->sendURLs($return, $callback)
            ->sendLanguage($language)
            ->create_pay_page();;

        return $pay;
    }
}
