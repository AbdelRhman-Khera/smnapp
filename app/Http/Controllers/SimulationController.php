<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Address;
use App\Models\Customer;
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
            ? MaintenanceRequest::with(['customer', 'address.district.area', 'technician', 'slot', 'invoices.services', 'invoices.spareParts', 'statuses'])
                ->findOrFail($request->integer('simulation_request_id'))
            : null;

        return view('simulate.index', [
            'simulationRequest' => $simulationRequest,
            'customers' => Customer::query()->orderBy('first_name')->orderBy('last_name')->get(),
            'addresses' => Address::query()->orderBy('name')->get(),
            'products' => Product::query()->active()->orderBy('name_en')->get(),
            'technicians' => Technician::query()->orderBy('first_name')->orderBy('last_name')->get(),
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
        $this->payFinalInvoice($maintenanceRequest);

        return $this->redirectToSimulation($maintenanceRequest, 'Final invoice paid and request completed.');
    }

    public function completeWithoutPaymentAction(MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $this->completeWithoutPayment($maintenanceRequest);

        return $this->redirectToSimulation($maintenanceRequest, 'Request completed with a zero invoice.');
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
            if ($request->requiresVisitFeePayment() && $request->last_status !== 'service_paid') {
                abort(422, 'Visit fee must be paid before assigning a technician.');
            }

            if (! $request->requiresVisitFeePayment() && $request->last_status !== 'pending') {
                abort(422, 'Only pending requests can be assigned.');
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
                ->where('invoice_type', 'final')
                ->where('status', 'pending')
                ->latest()
                ->firstOrFail();

            $invoice->update([
                'status' => 'completed',
                'payment_method' => 'online',
                'payment_details' => ['simulation' => true, 'paid_at' => now()->toDateTimeString()],
            ]);
            $request->statuses()->create(['status' => 'completed', 'notes' => 'Final invoice paid through simulation.']);
            $request->update(['last_status' => 'completed']);

            return $request->fresh(['invoices', 'statuses']);
        });
    }

    public function completeWithoutPayment(MaintenanceRequest $request): MaintenanceRequest
    {
        return DB::transaction(function () use ($request): MaintenanceRequest {
            abort_unless($request->last_status === 'in_progress', 422, 'Request must be in progress.');

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
