<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Address;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DeviceWithdrawalRequest;
use App\Models\MaintenanceRequest;
use App\Models\Product;
use App\Models\Service;
use App\Models\Slot;
use App\Models\SparePart;
use App\Models\Technician;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SimulationController extends Controller
{
    public function index(Request $request)
    {
        $simulationRequest = $request->integer('simulation_request_id')
            ? MaintenanceRequest::with([
                'customer',
                'address.district.area',
                'technician',
                'slot',
                'invoices.services',
                'invoices.spareParts',
                'statuses',
                'deviceWithdrawalRequests.items.product',
                'deviceWithdrawalRequests.branch',
                'deviceWithdrawalRequests.technician',
                'deviceWithdrawalRequests.handoffTechnician',
                'deviceWithdrawalRequests.followUpMaintenanceRequest',
                'workshopWithdrawalSource.items.product',
                'workshopWithdrawalSource.branch',
            ])
                ->findOrFail($request->integer('simulation_request_id'))
            : null;

        return view('simulate.index', [
            'simulationRequest' => $simulationRequest,
            'customers' => Customer::query()->orderBy('first_name')->orderBy('last_name')->get(),
            'addresses' => Address::query()->orderBy('name')->get(),
            'products' => Product::query()->active()->orderBy('name_en')->get(),
            'technicians' => Technician::query()->orderBy('first_name')->orderBy('last_name')->get(),
            'branches' => Branch::query()->orderBy('name_en')->get(),
            'services' => Service::query()->orderBy('name_en')->get(),
            'spareParts' => SparePart::query()->orderBy('name_en')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'address_id' => ['required', 'exists:addresses,id'],
            'type' => ['required', 'in:regular_maintenance,emergency_maintenance,new_installation,warranty'],
            'problem_description' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        abort_unless(Address::query()->whereKey($data['address_id'])->where('customer_id', $data['customer_id'])->exists(), 422, 'Address does not belong to the selected customer.');

        $simulationRequest = $this->createRequest($data);

        return $this->redirectToSimulation($simulationRequest, 'Simulation request created.');
    }

    public function payVisitFeeAction(MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $this->payVisitFee($maintenanceRequest);

        return $this->redirectToSimulation($maintenanceRequest, 'Visit fee paid. Request is ready for appointment booking.');
    }

    public function assignTechnicianAction(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $data = $request->validate([
            'technician_id' => ['required', 'exists:technicians,id'],
            'scheduled_at' => ['required', 'date'],
        ]);

        $this->assignTechnician($maintenanceRequest, (int) $data['technician_id'], $data['scheduled_at']);

        return $this->redirectToSimulation($maintenanceRequest, 'Technician and appointment assigned.');
    }

    public function onTheWayAction(MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $this->advanceTechnicianStatus($maintenanceRequest, 'technician_on_the_way');

        return $this->redirectToSimulation($maintenanceRequest, 'Technician marked as on the way.');
    }

    public function inProgressAction(MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $this->advanceTechnicianStatus($maintenanceRequest, 'in_progress');

        return $this->redirectToSimulation($maintenanceRequest, 'Request marked as in progress.');
    }

    public function createFinalInvoiceAction(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $data = $request->validate([
            'services' => ['nullable', 'array'],
            'services.*' => ['exists:services,id'],
            'spare_parts' => ['nullable', 'array'],
            'spare_parts.*.spare_part_id' => ['nullable', 'exists:spare_parts,id'],
            'spare_parts.*.quantity' => ['nullable', 'integer', 'min:1'],
            'spare_parts.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->createFinalInvoice($maintenanceRequest, $data['services'] ?? [], $data['spare_parts'] ?? []);

        return $this->redirectToSimulation($maintenanceRequest, 'Final invoice created.');
    }

    public function payFinalInvoiceAction(MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $maintenanceRequest = $this->payFinalInvoice($maintenanceRequest);

        return $this->redirectToSimulation(
            $maintenanceRequest,
            $maintenanceRequest->last_status === 'service_paid'
                ? 'Workshop repair invoice paid. Request is ready for appointment booking.'
                : 'Final invoice paid and request completed.'
        );
    }

    public function completeWithoutPaymentAction(MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $this->completeWithoutPayment($maintenanceRequest);

        return $this->redirectToSimulation($maintenanceRequest, 'Request completed with a zero invoice.');
    }

    public function createWithdrawalAction(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'exists:branches,id'],
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['exists:products,id'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->createWithdrawal($maintenanceRequest, $data);

        return $this->redirectToSimulation($maintenanceRequest, 'Device withdrawal request created.');
    }

    public function approveWithdrawalAction(DeviceWithdrawalRequest $withdrawalRequest): RedirectResponse
    {
        $this->customerDecisionForWithdrawal($withdrawalRequest, true);

        return $this->redirectToSimulation($withdrawalRequest->maintenanceRequest, 'Customer approved the withdrawal request.');
    }

    public function rejectWithdrawalAction(DeviceWithdrawalRequest $withdrawalRequest): RedirectResponse
    {
        $this->customerDecisionForWithdrawal($withdrawalRequest, false);

        return $this->redirectToSimulation($withdrawalRequest->maintenanceRequest, 'Customer rejected the withdrawal request.');
    }

    public function assignWithdrawalDeliveryTechnicianAction(Request $request, DeviceWithdrawalRequest $withdrawalRequest): RedirectResponse
    {
        $data = $request->validate([
            'technician_id' => ['required', 'exists:technicians,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->assignWithdrawalDeliveryTechnician($withdrawalRequest, (int) $data['technician_id'], $data);

        return $this->redirectToSimulation($withdrawalRequest->maintenanceRequest, 'Withdrawal assigned to another technician.');
    }

    public function receiveWithdrawalFromTechnicianAction(DeviceWithdrawalRequest $withdrawalRequest): RedirectResponse
    {
        $this->receiveWithdrawalFromTechnician($withdrawalRequest);

        return $this->redirectToSimulation($withdrawalRequest->maintenanceRequest, 'Delivery technician received the withdrawn device.');
    }

    public function deliverWithdrawalToBranchAction(Request $request, DeviceWithdrawalRequest $withdrawalRequest): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->deliverWithdrawalToBranch($withdrawalRequest, (int) $data['branch_id'], $data['notes'] ?? null);

        return $this->redirectToSimulation($withdrawalRequest->maintenanceRequest, 'Withdrawn device delivered to branch.');
    }

    public function branchReceiveWithdrawalAction(DeviceWithdrawalRequest $withdrawalRequest): RedirectResponse
    {
        $this->branchReceiveWithdrawal($withdrawalRequest);

        return $this->redirectToSimulation($withdrawalRequest->maintenanceRequest, 'Branch received the withdrawn device.');
    }

    public function startWithdrawalRepairAction(DeviceWithdrawalRequest $withdrawalRequest): RedirectResponse
    {
        $this->startWithdrawalRepair($withdrawalRequest);

        return $this->redirectToSimulation($withdrawalRequest->maintenanceRequest, 'Workshop repair started.');
    }

    public function completeWithdrawalRepairAction(Request $request, DeviceWithdrawalRequest $withdrawalRequest): RedirectResponse
    {
        $data = $request->validate([
            'workshop_notes' => ['nullable', 'string'],
        ]);

        $this->completeWithdrawalRepair($withdrawalRequest, $data['workshop_notes'] ?? null);

        return $this->redirectToSimulation($withdrawalRequest->maintenanceRequest, 'Workshop repair completed.');
    }

    public function createWithdrawalFollowUpAction(Request $request, DeviceWithdrawalRequest $withdrawalRequest): RedirectResponse
    {
        $data = $request->validate([
            'invoice_total' => ['required', 'numeric', 'min:0'],
            'problem_description' => ['required', 'string'],
        ]);

        $followUp = $this->createWithdrawalFollowUp($withdrawalRequest, $data);

        return $this->redirectToSimulation($followUp, 'Follow-up request and invoice created. Continue the cycle from this request.');
    }

    public function deliverWithdrawalToCustomerAction(DeviceWithdrawalRequest $withdrawalRequest): RedirectResponse
    {
        $this->deliverWithdrawalToCustomer($withdrawalRequest);

        return $this->redirectToSimulation($withdrawalRequest->followUpMaintenanceRequest, 'Technician delivered the repaired device to customer.');
    }

    public function confirmWithdrawalCustomerReceiptAction(DeviceWithdrawalRequest $withdrawalRequest): RedirectResponse
    {
        $this->confirmWithdrawalCustomerReceipt($withdrawalRequest);

        return $this->redirectToSimulation($withdrawalRequest->followUpMaintenanceRequest, 'Customer confirmed device receipt. Withdrawal cycle closed.');
    }

    public function createRequest(array $data): MaintenanceRequest
    {
        return DB::transaction(function () use ($data): MaintenanceRequest {
            $request = MaintenanceRequest::create([
                'customer_id' => $data['customer_id'],
                'address_id' => $data['address_id'],
                'type' => $data['type'],
                'problem_description' => $data['problem_description'] ?? null,
                'photos' => [],
                'last_status' => in_array($data['type'], ['regular_maintenance', 'emergency_maintenance'], true)
                    ? 'visit_payment_pending'
                    : 'pending',
            ]);

            $products = collect($data['products'] ?? [])
                ->filter(fn (array $item): bool => filled($item['product_id'] ?? null))
                ->mapWithKeys(fn (array $item): array => [
                    (int) $item['product_id'] => ['quantity' => max(1, (int) ($item['quantity'] ?? 1))],
                ])
                ->all();

            $request->products()->sync($products);
            $request->recalculateHours();
            $request->statuses()->create(['status' => $request->last_status]);

            if ($request->requiresVisitFeePayment()) {
                $request->invoices()->create([
                    'invoice_type' => 'visit_fee',
                    'total' => $request->calculateVisitFee(),
                    'status' => 'pending',
                ]);
            }

            return $request->fresh(['products', 'invoices', 'statuses']);
        });
    }

    public function payVisitFee(MaintenanceRequest $request): MaintenanceRequest
    {
        return DB::transaction(function () use ($request): MaintenanceRequest {
            $invoice = $request->invoices()
                ->where('invoice_type', 'visit_fee')
                ->where('status', 'pending')
                ->latest()
                ->firstOrFail();

            $invoice->update([
                'status' => 'completed',
                'payment_method' => 'online',
                'payment_details' => ['simulation' => true, 'paid_at' => now()->toDateTimeString()],
            ]);

            $request->statuses()->create([
                'status' => 'service_paid',
                'notes' => 'Visit fee paid through simulation.',
            ]);
            $request->update(['last_status' => 'service_paid']);

            return $request->fresh(['invoices', 'statuses']);
        });
    }

    public function assignTechnician(MaintenanceRequest $request, int $technicianId, string $scheduledAt): MaintenanceRequest
    {
        return DB::transaction(function () use ($request, $technicianId, $scheduledAt): MaintenanceRequest {
            if ($request->requiresVisitFeePayment() && ! in_array($request->last_status, ['service_paid', 'technician_assigned'], true)) {
                abort(422, 'Visit fee must be paid before assigning a technician.');
            }

            if (! $request->requiresVisitFeePayment() && ! in_array($request->last_status, ['pending', 'technician_assigned'], true)) {
                abort(422, 'Only pending or technician assigned requests can be assigned.');
            }

            $start = Carbon::parse($scheduledAt)->minute(0)->second(0);
            $requiredSlots = max(1, (int) ceil((float) ($request->hours ?? 1)));
            $slotIds = [];

            for ($i = 0; $i < $requiredSlots; $i++) {
                $slotTime = $start->copy()->addHours($i);
                $slot = Slot::query()->firstOrCreate(
                    [
                        'technician_id' => $technicianId,
                        'date' => $slotTime->toDateString(),
                        'time' => $slotTime->format('H:i:s'),
                    ],
                    ['is_booked' => false]
                );

                $slot->update(['is_booked' => true]);
                $slotIds[] = $slot->id;
            }

            $request->update([
                'technician_id' => $technicianId,
                'slot_id' => $slotIds[0],
                'extra_slot_id' => array_slice($slotIds, 1),
                'last_status' => 'technician_assigned',
            ]);
            $request->statuses()->create(['status' => 'technician_assigned', 'notes' => 'Technician assigned through simulation.']);

            return $request->fresh(['slot', 'technician', 'statuses']);
        });
    }

    public function advanceTechnicianStatus(MaintenanceRequest $request, string $status): MaintenanceRequest
    {
        $allowedTransitions = [
            'technician_on_the_way' => 'technician_assigned',
            'in_progress' => 'technician_on_the_way',
        ];

        abort_unless(($allowedTransitions[$status] ?? null) === $request->last_status, 422, 'Invalid simulation status transition.');

        $request->statuses()->create([
            'status' => $status,
            'notes' => 'Status updated through simulation.',
            'latitude' => '24.7136',
            'longitude' => '46.6753',
        ]);
        $request->update(['last_status' => $status]);

        return $request->fresh(['statuses']);
    }

    public function createFinalInvoice(MaintenanceRequest $request, array $serviceIds, array $spareParts): MaintenanceRequest
    {
        return DB::transaction(function () use ($request, $serviceIds, $spareParts): MaintenanceRequest {
            abort_unless($request->last_status === 'in_progress', 422, 'Request must be in progress.');

            $services = Service::query()->whereIn('id', $serviceIds)->get();
            $sparePartSync = [];
            $sparePartsTotal = 0;

            foreach ($spareParts as $item) {
                if (! filled($item['spare_part_id'] ?? null)) {
                    continue;
                }

                $part = SparePart::query()->findOrFail($item['spare_part_id']);
                $quantity = max(1, (int) ($item['quantity'] ?? 1));
                $price = (float) ($item['price'] ?? $part->price);
                $sparePartsTotal += $price * $quantity;
                $sparePartSync[$part->id] = ['quantity' => $quantity, 'price' => $price];
            }

            $invoice = $request->invoices()->create([
                'invoice_type' => 'final',
                'total' => (float) $services->sum('price') + $sparePartsTotal,
                'status' => 'pending',
            ]);
            $invoice->services()->sync($services->pluck('id')->all());
            $invoice->spareParts()->sync($sparePartSync);

            $request->statuses()->create(['status' => 'waiting_for_payment', 'notes' => 'Final invoice created through simulation.']);
            $request->update(['last_status' => 'waiting_for_payment', 'invoice_number' => $invoice->id]);

            return $request->fresh(['invoices.services', 'invoices.spareParts', 'statuses']);
        });
    }

    public function payFinalInvoice(MaintenanceRequest $request): MaintenanceRequest
    {
        return DB::transaction(function () use ($request): MaintenanceRequest {
            $invoice = $request->invoices()
                ->whereIn('invoice_type', ['final', 'workshop'])
                ->where('status', 'pending')
                ->latest()
                ->firstOrFail();

            $invoice->update([
                'status' => 'completed',
                'payment_method' => 'online',
                'payment_details' => ['simulation' => true, 'paid_at' => now()->toDateTimeString()],
            ]);

            if ($invoice->invoice_type === 'workshop') {
                $request->statuses()->create(['status' => 'service_paid', 'notes' => 'Workshop repair invoice paid through simulation.']);
                $request->update(['last_status' => 'service_paid']);

                return $request->fresh(['invoices', 'statuses']);
            }

            $request->statuses()->create(['status' => 'completed', 'notes' => 'Final invoice paid through simulation.']);
            $request->update(['last_status' => 'completed']);

            return $request->fresh(['invoices', 'statuses']);
        });
    }

    public function createWithdrawal(MaintenanceRequest $request, array $data): DeviceWithdrawalRequest
    {
        return DB::transaction(function () use ($request, $data): DeviceWithdrawalRequest {
            abort_unless($request->last_status === 'in_progress', 422, 'Request must be in progress.');
            abort_unless(filled($request->technician_id), 422, 'Request must have an assigned technician.');

            $requestProductIds = $request->products()->pluck('products.id')->all();
            $productIds = collect($data['product_ids'])->map(fn ($id): int => (int) $id)->unique()->values();

            abort_unless($productIds->diff($requestProductIds)->isEmpty(), 422, 'Withdrawal products must belong to the maintenance request.');

            $withdrawalRequest = DeviceWithdrawalRequest::create([
                'maintenance_request_id' => $request->id,
                'customer_id' => $request->customer_id,
                'technician_id' => $request->technician_id,
                'branch_id' => $data['branch_id'] ?? null,
                'status' => DeviceWithdrawalRequest::STATUS_PENDING_CUSTOMER_APPROVAL,
                'technician_notes' => $data['notes'] ?? null,
            ]);

            foreach ($productIds as $productId) {
                $withdrawalRequest->items()->create([
                    'product_id' => $productId,
                    'serial_number' => $data['serial_number'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'photos' => [],
                    'status' => DeviceWithdrawalRequest::STATUS_PENDING_CUSTOMER_APPROVAL,
                ]);
            }

            return $withdrawalRequest->fresh(['items.product']);
        });
    }

    public function customerDecisionForWithdrawal(DeviceWithdrawalRequest $withdrawalRequest, bool $approved): DeviceWithdrawalRequest
    {
        return DB::transaction(function () use ($withdrawalRequest, $approved): DeviceWithdrawalRequest {
            abort_unless($withdrawalRequest->status === DeviceWithdrawalRequest::STATUS_PENDING_CUSTOMER_APPROVAL, 422, 'Withdrawal must be waiting for customer approval.');

            $status = $approved
                ? DeviceWithdrawalRequest::STATUS_APPROVED_BY_CUSTOMER
                : DeviceWithdrawalRequest::STATUS_REJECTED_BY_CUSTOMER;

            $withdrawalRequest->update([
                'status' => $status,
                'customer_decision_notes' => ['simulation' => true, 'approved' => $approved],
                'customer_decision_at' => now(),
            ]);
            $withdrawalRequest->items()->update(['status' => $status]);

            return $withdrawalRequest->fresh(['items']);
        });
    }

    public function assignWithdrawalDeliveryTechnician(DeviceWithdrawalRequest $withdrawalRequest, int $technicianId, array $data): DeviceWithdrawalRequest
    {
        return DB::transaction(function () use ($withdrawalRequest, $technicianId, $data): DeviceWithdrawalRequest {
            abort_unless($withdrawalRequest->status === DeviceWithdrawalRequest::STATUS_APPROVED_BY_CUSTOMER, 422, 'Withdrawal must be approved by customer.');
            abort_unless($withdrawalRequest->technician_id !== $technicianId, 422, 'Choose another technician.');

            $withdrawalRequest->update([
                'handoff_technician_id' => $technicianId,
                'branch_id' => $data['branch_id'] ?? $withdrawalRequest->branch_id,
                'handoff_notes' => $data['notes'] ?? null,
                'status' => DeviceWithdrawalRequest::STATUS_ASSIGNED_TO_DELIVERY_TECHNICIAN,
                'assigned_to_handoff_technician_at' => now(),
            ]);
            $withdrawalRequest->items()->update(['status' => DeviceWithdrawalRequest::STATUS_ASSIGNED_TO_DELIVERY_TECHNICIAN]);

            return $withdrawalRequest->fresh(['items', 'handoffTechnician']);
        });
    }

    public function receiveWithdrawalFromTechnician(DeviceWithdrawalRequest $withdrawalRequest): DeviceWithdrawalRequest
    {
        return DB::transaction(function () use ($withdrawalRequest): DeviceWithdrawalRequest {
            abort_unless($withdrawalRequest->status === DeviceWithdrawalRequest::STATUS_ASSIGNED_TO_DELIVERY_TECHNICIAN, 422, 'Withdrawal must be assigned to a delivery technician.');

            $withdrawalRequest->update([
                'status' => DeviceWithdrawalRequest::STATUS_RECEIVED_BY_DELIVERY_TECHNICIAN,
                'received_by_handoff_technician_at' => now(),
            ]);
            $withdrawalRequest->items()->update(['status' => DeviceWithdrawalRequest::STATUS_RECEIVED_BY_DELIVERY_TECHNICIAN]);

            return $withdrawalRequest->fresh(['items']);
        });
    }

    public function deliverWithdrawalToBranch(DeviceWithdrawalRequest $withdrawalRequest, int $branchId, ?string $notes): DeviceWithdrawalRequest
    {
        return DB::transaction(function () use ($withdrawalRequest, $branchId, $notes): DeviceWithdrawalRequest {
            abort_unless(in_array($withdrawalRequest->status, [
                DeviceWithdrawalRequest::STATUS_APPROVED_BY_CUSTOMER,
                DeviceWithdrawalRequest::STATUS_RECEIVED_BY_DELIVERY_TECHNICIAN,
            ], true), 422, 'Withdrawal is not ready to be delivered to branch.');

            $withdrawalRequest->update([
                'branch_id' => $branchId,
                'status' => DeviceWithdrawalRequest::STATUS_DELIVERED_TO_BRANCH,
                'technician_notes' => $notes ?? $withdrawalRequest->technician_notes,
                'delivered_to_branch_at' => now(),
            ]);
            $withdrawalRequest->items()->update(['status' => DeviceWithdrawalRequest::STATUS_DELIVERED_TO_BRANCH]);

            return $withdrawalRequest->fresh(['items', 'branch']);
        });
    }

    public function branchReceiveWithdrawal(DeviceWithdrawalRequest $withdrawalRequest): DeviceWithdrawalRequest
    {
        return DB::transaction(function () use ($withdrawalRequest): DeviceWithdrawalRequest {
            abort_unless($withdrawalRequest->status === DeviceWithdrawalRequest::STATUS_DELIVERED_TO_BRANCH, 422, 'Withdrawal must be delivered to branch first.');

            $withdrawalRequest->update([
                'status' => DeviceWithdrawalRequest::STATUS_RECEIVED_BY_BRANCH,
                'received_by_user_id' => auth()->id(),
                'received_by_branch_at' => now(),
            ]);
            $withdrawalRequest->items()->update(['status' => DeviceWithdrawalRequest::STATUS_RECEIVED_BY_BRANCH]);

            return $withdrawalRequest->fresh(['items']);
        });
    }

    public function startWithdrawalRepair(DeviceWithdrawalRequest $withdrawalRequest): DeviceWithdrawalRequest
    {
        return DB::transaction(function () use ($withdrawalRequest): DeviceWithdrawalRequest {
            abort_unless($withdrawalRequest->status === DeviceWithdrawalRequest::STATUS_RECEIVED_BY_BRANCH, 422, 'Withdrawal must be received by branch first.');

            $withdrawalRequest->update([
                'status' => DeviceWithdrawalRequest::STATUS_UNDER_REPAIR,
                'repair_started_at' => now(),
            ]);
            $withdrawalRequest->items()->update(['status' => DeviceWithdrawalRequest::STATUS_UNDER_REPAIR]);

            return $withdrawalRequest->fresh(['items']);
        });
    }

    public function completeWithdrawalRepair(DeviceWithdrawalRequest $withdrawalRequest, ?string $notes): DeviceWithdrawalRequest
    {
        return DB::transaction(function () use ($withdrawalRequest, $notes): DeviceWithdrawalRequest {
            abort_unless($withdrawalRequest->status === DeviceWithdrawalRequest::STATUS_UNDER_REPAIR, 422, 'Withdrawal must be under repair first.');

            $withdrawalRequest->update([
                'status' => DeviceWithdrawalRequest::STATUS_REPAIR_COMPLETED,
                'workshop_notes' => $notes,
                'repair_completed_at' => now(),
            ]);
            $withdrawalRequest->items()->update(['status' => DeviceWithdrawalRequest::STATUS_REPAIR_COMPLETED]);

            return $withdrawalRequest->fresh(['items']);
        });
    }

    public function createWithdrawalFollowUp(DeviceWithdrawalRequest $withdrawalRequest, array $data): MaintenanceRequest
    {
        return DB::transaction(function () use ($withdrawalRequest, $data): MaintenanceRequest {
            abort_unless($withdrawalRequest->status === DeviceWithdrawalRequest::STATUS_REPAIR_COMPLETED, 422, 'Withdrawal repair must be completed first.');
            abort_unless(blank($withdrawalRequest->follow_up_maintenance_request_id), 422, 'Follow-up request already exists.');

            $withdrawalRequest->loadMissing(['maintenanceRequest', 'items']);

            $followUp = MaintenanceRequest::create([
                'customer_id' => $withdrawalRequest->customer_id,
                'type' => 'regular_maintenance',
                'address_id' => $withdrawalRequest->maintenanceRequest?->address_id,
                'problem_description' => $data['problem_description'],
                'last_status' => 'waiting_for_payment',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $sync = $withdrawalRequest->items
                ->pluck('product_id')
                ->unique()
                ->mapWithKeys(fn ($productId): array => [$productId => ['quantity' => 1]])
                ->all();

            $followUp->products()->sync($sync);
            $followUp->recalculateHours();
            $followUp->statuses()->create(['status' => 'waiting_for_payment', 'notes' => 'Workshop follow-up created through simulation.']);

            $invoice = $followUp->invoices()->create([
                'invoice_type' => 'workshop',
                'total' => (float) $data['invoice_total'],
                'status' => 'pending',
                'notes' => [[
                    'source' => 'simulation_device_withdrawal',
                    'device_withdrawal_request_id' => $withdrawalRequest->id,
                    'created_at' => now()->toDateTimeString(),
                ]],
            ]);

            $followUp->update(['invoice_number' => $invoice->id]);

            $withdrawalRequest->update([
                'status' => DeviceWithdrawalRequest::STATUS_FOLLOW_UP_REQUEST_CREATED,
                'follow_up_maintenance_request_id' => $followUp->id,
            ]);
            $withdrawalRequest->items()->update(['status' => DeviceWithdrawalRequest::STATUS_FOLLOW_UP_REQUEST_CREATED]);

            return $followUp->fresh(['invoices', 'statuses']);
        });
    }

    public function deliverWithdrawalToCustomer(DeviceWithdrawalRequest $withdrawalRequest): DeviceWithdrawalRequest
    {
        return DB::transaction(function () use ($withdrawalRequest): DeviceWithdrawalRequest {
            $withdrawalRequest->loadMissing('followUpMaintenanceRequest');

            abort_unless($withdrawalRequest->status === DeviceWithdrawalRequest::STATUS_FOLLOW_UP_REQUEST_CREATED, 422, 'Follow-up request must be created first.');
            abort_unless($withdrawalRequest->followUpMaintenanceRequest?->last_status === 'completed', 422, 'Follow-up request must be completed first.');

            $withdrawalRequest->update([
                'status' => DeviceWithdrawalRequest::STATUS_DELIVERED_TO_CUSTOMER,
                'delivered_to_customer_at' => now(),
            ]);
            $withdrawalRequest->items()->update(['status' => DeviceWithdrawalRequest::STATUS_DELIVERED_TO_CUSTOMER]);

            return $withdrawalRequest->fresh(['items']);
        });
    }

    public function confirmWithdrawalCustomerReceipt(DeviceWithdrawalRequest $withdrawalRequest): DeviceWithdrawalRequest
    {
        return DB::transaction(function () use ($withdrawalRequest): DeviceWithdrawalRequest {
            abort_unless($withdrawalRequest->status === DeviceWithdrawalRequest::STATUS_DELIVERED_TO_CUSTOMER, 422, 'Device must be delivered to customer first.');

            $withdrawalRequest->update([
                'status' => DeviceWithdrawalRequest::STATUS_COMPLETED,
                'customer_received_at' => now(),
            ]);
            $withdrawalRequest->items()->update(['status' => DeviceWithdrawalRequest::STATUS_COMPLETED]);

            return $withdrawalRequest->fresh(['items']);
        });
    }

    public function completeWithoutPayment(MaintenanceRequest $request): MaintenanceRequest
    {
        return DB::transaction(function () use ($request): MaintenanceRequest {
            abort_unless(in_array($request->last_status, ['in_progress', 'waiting_for_payment'], true), 422, 'Request must be in progress or waiting for payment.');

            abort_if(
                $request->invoices()->where('invoice_type', 'final')->where('status', '!=', 'pending')->exists(),
                422,
                'The final invoice has already been paid.'
            );

            $request->invoices()
                ->where('invoice_type', 'final')
                ->where('status', 'pending')
                ->get()
                ->each
                ->delete();

            $invoice = $request->invoices()->firstOrCreate(
                ['invoice_type' => 'zero_service', 'status' => 'completed'],
                ['total' => 0, 'notes' => [['note' => 'Created through simulation.', 'created_at' => now()->toDateTimeString()]]]
            );

            $request->statuses()->create(['status' => 'completed', 'notes' => 'Completed without payment through simulation.']);
            $request->update(['last_status' => 'completed', 'invoice_number' => $invoice->id]);

            return $request->fresh(['invoices', 'statuses']);
        });
    }

    private function redirectToSimulation(MaintenanceRequest $maintenanceRequest, string $message): RedirectResponse
    {
        return redirect()
            ->route('simulation.index', ['simulation_request_id' => $maintenanceRequest->id])
            ->with('success', $message);
    }
}
