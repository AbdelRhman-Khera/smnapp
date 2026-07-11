<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance Simulation</title>
    <style>
        :root { color-scheme: dark; font-family: Inter, Arial, sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: #101113; color: #f8fafc; }
        main { width: min(1120px, calc(100% - 32px)); margin: 0 auto; padding: 40px 0 64px; }
        h1, h2, h3, p { margin-top: 0; }
        h1 { font-size: 28px; margin-bottom: 8px; }
        h2 { font-size: 18px; margin-bottom: 18px; }
        h3 { font-size: 16px; margin-bottom: 10px; }
        .muted { color: #9ca3af; }
        .stack { display: grid; gap: 20px; }
        .card { border: 1px solid #30343b; border-radius: 10px; background: #191b1f; padding: 24px; }
        .grid { display: grid; gap: 16px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .grid-four { display: grid; gap: 12px; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        label { display: grid; gap: 7px; color: #d1d5db; font-size: 14px; font-weight: 600; }
        input, select, textarea { width: 100%; border: 1px solid #3f444d; border-radius: 7px; background: #101113; color: #f8fafc; padding: 10px 12px; font: inherit; }
        textarea { resize: vertical; min-height: 84px; }
        button { border: 0; border-radius: 7px; padding: 10px 14px; background: #3157b7; color: #fff; cursor: pointer; font: inherit; font-weight: 700; }
        button:hover { background: #3d66ce; }
        .secondary { background: #30343b; }
        .secondary:hover { background: #414750; }
        .success { background: #19734e; }
        .success:hover { background: #23815b; }
        .danger { background: #9b2c2c; }
        .danger:hover { background: #b23636; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; }
        .notice { border-radius: 8px; padding: 12px 14px; font-weight: 600; }
        .notice.success { background: #123d2d; color: #bbf7d0; }
        .notice.error { background: #481d1d; color: #fecaca; }
        .metric { border: 1px solid #30343b; border-radius: 8px; background: #111317; padding: 14px; }
        .metric small { display: block; color: #9ca3af; margin-bottom: 7px; text-transform: uppercase; }
        .metric strong { font-size: 18px; text-transform: capitalize; }
        .timeline { display: grid; gap: 10px; }
        .line { display: flex; align-items: center; justify-content: space-between; gap: 14px; border: 1px solid #30343b; border-radius: 8px; background: #111317; padding: 14px; }
        .badge { display: inline-block; border-radius: 999px; background: #233b77; color: #c7d7ff; padding: 4px 9px; font-size: 12px; font-weight: 700; text-transform: capitalize; }
        .inline-form { display: inline; }
        .checkbox-grid { display: grid; gap: 10px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .checkbox-grid label { display: flex; align-items: center; gap: 8px; border: 1px solid #30343b; border-radius: 7px; padding: 10px; background: #111317; }
        .checkbox-grid input { width: auto; }
        @media (max-width: 760px) { .grid, .grid-four { grid-template-columns: 1fr; } main { width: min(100% - 24px, 1120px); padding-top: 24px; } .card { padding: 18px; } }
    </style>
</head>
<body>
    <main>
        <div class="stack">
            <header>
                <h1>Maintenance Request Simulation</h1>
                <p class="muted">This page creates and updates real maintenance requests, invoices, slots, and status history.</p>
            </header>

            @if (session('success'))
                <div class="notice success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="notice error">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <section class="card">
                <h2>Create Simulation Request</h2>
                <form method="post" action="{{ route('simulation.store') }}" class="stack">
                    @csrf
                    <div class="grid">
                        <label>Customer
                            <select name="customer_id" id="customer_id" required>
                                <option value="">Select customer</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                                        {{ trim($customer->first_name . ' ' . $customer->last_name) }} - {{ $customer->phone }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label>Address
                            <select name="address_id" id="address_id" required>
                                <option value="">Select address</option>
                                @foreach ($addresses as $address)
                                    <option value="{{ $address->id }}" data-customer="{{ $address->customer_id }}" @selected(old('address_id') == $address->id)>
                                        {{ $address->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label>Request Type
                            <select name="type" required>
                                <option value="regular_maintenance">Regular Maintenance</option>
                                <option value="emergency_maintenance">Emergency Maintenance</option>
                                <option value="new_installation">New Installation</option>
                                <option value="warranty">Warranty</option>
                            </select>
                        </label>
                        <label>Product
                            <select name="products[0][product_id]" required>
                                <option value="">Select product</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name_en ?: $product->name_ar }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Quantity
                            <input type="number" min="1" name="products[0][quantity]" value="1" required>
                        </label>
                    </div>
                    <label>Problem Description
                        <textarea name="problem_description" placeholder="Optional issue description"></textarea>
                    </label>
                    <div class="actions"><button type="submit">Create Request</button></div>
                </form>
            </section>

            @if ($simulationRequest)
                @php($request = $simulationRequest)
                <section class="card">
                    <h2>Current Request #{{ $request->id }}</h2>
                    <div class="grid-four">
                        <div class="metric"><small>Status</small><strong>{{ str_replace('_', ' ', $request->last_status) }}</strong></div>
                        <div class="metric"><small>Type</small><strong>{{ str_replace('_', ' ', $request->type) }}</strong></div>
                        <div class="metric"><small>Hours</small><strong>{{ $request->hours ?? 0 }}</strong></div>
                        <div class="metric"><small>Invoices</small><strong>{{ $request->invoices->count() }}</strong></div>
                    </div>

                    <div class="actions" style="margin-top: 20px;">
                        @if ($request->last_status === 'visit_payment_pending')
                            <form method="post" action="{{ route('simulation.visit-fee', $request) }}" class="grid" style="width: 100%; grid-template-columns: 1fr auto; align-items: end;">
                                @csrf
                                <label>Note<input type="text" name="note" placeholder="Optional status note"></label>
                                <button type="submit">Pay Visit Fee</button>
                            </form>
                        @endif
                        @if (in_array($request->last_status, ['pending', 'service_paid']))
                            <form method="post" action="{{ route('simulation.assign', $request) }}" class="grid" style="width: 100%; grid-template-columns: 1fr 1fr 1fr auto; align-items: end;">
                                @csrf
                                <label>Technician
                                    <select name="technician_id" required>
                                        <option value="">Select technician</option>
                                        @foreach ($technicians as $technician)
                                            <option value="{{ $technician->id }}">{{ trim($technician->first_name . ' ' . $technician->last_name) }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>Appointment Time
                                    <input type="datetime-local" name="scheduled_at" value="{{ now()->addDay()->setTime(10, 0)->format('Y-m-d\\TH:i') }}" required>
                                </label>
                                <label>Note<input type="text" name="note" placeholder="Optional status note"></label>
                                <button type="submit">Assign Technician</button>
                            </form>
                        @endif
                        @if ($request->last_status === 'technician_assigned')
                            <form method="post" action="{{ route('simulation.on-the-way', $request) }}" class="grid" style="width: 100%; grid-template-columns: 1fr auto; align-items: end;">
                                @csrf
                                <label>Note<input type="text" name="note" placeholder="Optional status note"></label>
                                <button type="submit">Set On The Way</button>
                            </form>
                        @endif
                        @if ($request->last_status === 'technician_on_the_way')
                            <form method="post" action="{{ route('simulation.in-progress', $request) }}" class="grid" style="width: 100%; grid-template-columns: 1fr auto; align-items: end;">
                                @csrf
                                <label>Note<input type="text" name="note" placeholder="Optional status note"></label>
                                <button type="submit">Set In Progress</button>
                            </form>
                        @endif
                    </div>
                </section>

                @if ($request->workshopWithdrawalSource)
                    @php($sourceWithdrawal = $request->workshopWithdrawalSource)
                    <section class="card">
                        <h2>Workshop Withdrawal Source #{{ $sourceWithdrawal->id }}</h2>
                        <div class="grid-four">
                            <div class="metric"><small>Withdrawal Status</small><strong>{{ str_replace('_', ' ', $sourceWithdrawal->status) }}</strong></div>
                            <div class="metric"><small>Branch</small><strong>{{ $sourceWithdrawal->branch?->name_en ?? '-' }}</strong></div>
                            <div class="metric"><small>Original Request</small><strong>#{{ $sourceWithdrawal->maintenance_request_id }}</strong></div>
                            <div class="metric"><small>Items</small><strong>{{ $sourceWithdrawal->items->count() }}</strong></div>
                        </div>
                        <div class="actions" style="margin-top: 16px;">
                            <a href="{{ route('simulation.index', ['simulation_request_id' => $sourceWithdrawal->maintenance_request_id]) }}"><button type="button" class="secondary">Open Original Request</button></a>
                            @if ($request->last_status === 'completed' && $sourceWithdrawal->status === 'follow_up_request_created')
                                <form method="post" action="{{ route('simulation.withdrawals.deliver-to-customer', $sourceWithdrawal) }}">
                                    @csrf
                                    <button type="submit">Technician Delivered Device To Customer</button>
                                </form>
                            @endif
                            @if ($sourceWithdrawal->status === 'delivered_to_customer')
                                <form method="post" action="{{ route('simulation.withdrawals.confirm-customer-receipt', $sourceWithdrawal) }}">
                                    @csrf
                                    <button type="submit" class="success">Customer Confirmed Receipt</button>
                                </form>
                            @endif
                        </div>
                    </section>
                @endif

                @if ($request->last_status === 'in_progress')
                    <section class="card">
                        <h2>Device Withdrawal Cycle</h2>
                        <form method="post" action="{{ route('simulation.withdrawals.create', $request) }}" class="stack">
                            @csrf
                            <div class="grid">
                                <label>Target Branch Optional
                                    <select name="branch_id">
                                        <option value="">Select later while delivering to branch</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name_en ?: $branch->name_ar }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>Serial Number
                                    <input type="text" name="serial_number" placeholder="Optional serial number">
                                </label>
                            </div>
                            <label>Withdrawn Products</label>
                            <div class="checkbox-grid">
                                @foreach ($request->products as $product)
                                    <label>
                                        <input type="checkbox" name="product_ids[]" value="{{ $product->id }}">
                                        <span>{{ $product->name_en ?: $product->name_ar }} x{{ $product->pivot->quantity ?? 1 }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <label>Technician Notes
                                <textarea name="notes" placeholder="Workshop reason or device condition"></textarea>
                            </label>
                            <div class="actions"><button type="submit">Create Withdrawal Request</button></div>
                        </form>
                    </section>
                @endif

                @if ($request->deviceWithdrawalRequests->isNotEmpty())
                    <section class="card">
                        <h2>Withdrawal Requests</h2>
                        <div class="timeline">
                            @foreach ($request->deviceWithdrawalRequests as $withdrawal)
                                <div class="line" style="align-items: flex-start;">
                                    <div style="width: 100%;">
                                        <div class="actions" style="justify-content: space-between;">
                                            <div>
                                                <strong>Withdrawal #{{ $withdrawal->id }}</strong>
                                                <span class="badge">{{ str_replace('_', ' ', $withdrawal->status) }}</span>
                                            </div>
                                            @if ($withdrawal->follow_up_maintenance_request_id)
                                                <a href="{{ route('simulation.index', ['simulation_request_id' => $withdrawal->follow_up_maintenance_request_id]) }}"><button type="button" class="secondary">Open Follow-up #{{ $withdrawal->follow_up_maintenance_request_id }}</button></a>
                                            @endif
                                        </div>
                                        <div class="muted" style="margin-top: 8px;">
                                            Branch: {{ $withdrawal->branch?->name_en ?? '-' }} |
                                            Technician: {{ trim(($withdrawal->technician?->first_name ?? '') . ' ' . ($withdrawal->technician?->last_name ?? '')) ?: '-' }} |
                                            Delivery Tech: {{ trim(($withdrawal->handoffTechnician?->first_name ?? '') . ' ' . ($withdrawal->handoffTechnician?->last_name ?? '')) ?: '-' }}
                                        </div>
                                        <div class="muted" style="margin-top: 8px;">
                                            Items:
                                            @foreach ($withdrawal->items as $item)
                                                {{ $item->product?->name_en ?: $item->product?->name_ar }}@if (! $loop->last), @endif
                                            @endforeach
                                        </div>

                                        <div class="actions" style="margin-top: 14px;">
                                            @if ($withdrawal->status === 'pending_customer_approval')
                                                <form method="post" action="{{ route('simulation.withdrawals.approve', $withdrawal) }}">@csrf<button type="submit" class="success">Customer Approve</button></form>
                                                <form method="post" action="{{ route('simulation.withdrawals.reject', $withdrawal) }}">@csrf<button type="submit" class="danger">Customer Reject</button></form>
                                            @endif

                                            @if ($withdrawal->status === 'approved_by_customer')
                                                <form method="post" action="{{ route('simulation.withdrawals.deliver-to-branch', $withdrawal) }}" class="grid" style="width: 100%; grid-template-columns: 1fr 1fr auto; align-items: end;">
                                                    @csrf
                                                    <label>Branch
                                                        <select name="branch_id" required>
                                                            <option value="">Select branch</option>
                                                            @foreach ($branches as $branch)
                                                                <option value="{{ $branch->id }}" @selected($withdrawal->branch_id === $branch->id)>{{ $branch->name_en ?: $branch->name_ar }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                    <label>Notes<input type="text" name="notes" placeholder="Optional"></label>
                                                    <button type="submit">Deliver To Branch</button>
                                                </form>

                                                <form method="post" action="{{ route('simulation.withdrawals.assign-delivery-technician', $withdrawal) }}" class="grid" style="width: 100%; grid-template-columns: 1fr 1fr 1fr auto; align-items: end;">
                                                    @csrf
                                                    <label>Delivery Technician
                                                        <select name="technician_id" required>
                                                            <option value="">Select technician</option>
                                                            @foreach ($technicians as $technician)
                                                                @if ($technician->id !== $withdrawal->technician_id)
                                                                    <option value="{{ $technician->id }}">{{ trim($technician->first_name . ' ' . $technician->last_name) }}</option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                    <label>Branch Optional
                                                        <select name="branch_id">
                                                            <option value="">Select later</option>
                                                            @foreach ($branches as $branch)
                                                                <option value="{{ $branch->id }}" @selected($withdrawal->branch_id === $branch->id)>{{ $branch->name_en ?: $branch->name_ar }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                    <label>Notes<input type="text" name="notes" placeholder="Optional"></label>
                                                    <button type="submit">Assign Delivery Tech</button>
                                                </form>
                                            @endif

                                            @if ($withdrawal->status === 'assigned_to_delivery_technician')
                                                <form method="post" action="{{ route('simulation.withdrawals.receive-from-technician', $withdrawal) }}">@csrf<button type="submit">Delivery Tech Received Device</button></form>
                                            @endif

                                            @if ($withdrawal->status === 'received_by_delivery_technician')
                                                <form method="post" action="{{ route('simulation.withdrawals.deliver-to-branch', $withdrawal) }}" class="grid" style="width: 100%; grid-template-columns: 1fr 1fr auto; align-items: end;">
                                                    @csrf
                                                    <label>Branch
                                                        <select name="branch_id" required>
                                                            <option value="">Select branch</option>
                                                            @foreach ($branches as $branch)
                                                                <option value="{{ $branch->id }}" @selected($withdrawal->branch_id === $branch->id)>{{ $branch->name_en ?: $branch->name_ar }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                    <label>Notes<input type="text" name="notes" placeholder="Optional"></label>
                                                    <button type="submit">Delivery Tech Delivered To Branch</button>
                                                </form>
                                            @endif

                                            @if ($withdrawal->status === 'delivered_to_branch')
                                                <form method="post" action="{{ route('simulation.withdrawals.branch-receive', $withdrawal) }}">@csrf<button type="submit">Branch Manager Received</button></form>
                                            @endif

                                            @if ($withdrawal->status === 'received_by_branch')
                                                <form method="post" action="{{ route('simulation.withdrawals.start-repair', $withdrawal) }}">@csrf<button type="submit">Start Workshop Repair</button></form>
                                            @endif

                                            @if ($withdrawal->status === 'under_repair')
                                                <form method="post" action="{{ route('simulation.withdrawals.complete-repair', $withdrawal) }}" class="grid" style="width: 100%; grid-template-columns: 1fr auto; align-items: end;">
                                                    @csrf
                                                    <label>Workshop Notes<input type="text" name="workshop_notes" placeholder="Optional"></label>
                                                    <button type="submit">Complete Workshop Repair</button>
                                                </form>
                                            @endif

                                            @if ($withdrawal->status === 'repair_completed')
                                                <form method="post" action="{{ route('simulation.withdrawals.follow-up', $withdrawal) }}" class="grid" style="width: 100%; grid-template-columns: 1fr 1fr auto; align-items: end;">
                                                    @csrf
                                                    <label>Invoice Total<input type="number" min="0" step="0.01" name="invoice_total" value="0" required></label>
                                                    <label>Description<input type="text" name="problem_description" value="Workshop repaired device return and installation." required></label>
                                                    <button type="submit">Create Follow-up Request</button>
                                                </form>
                                            @endif

                                            @if ($withdrawal->status === 'follow_up_request_created' && $withdrawal->followUpMaintenanceRequest?->last_status === 'completed')
                                                <form method="post" action="{{ route('simulation.withdrawals.deliver-to-customer', $withdrawal) }}">@csrf<button type="submit">Technician Delivered To Customer</button></form>
                                            @endif

                                            @if ($withdrawal->status === 'delivered_to_customer')
                                                <form method="post" action="{{ route('simulation.withdrawals.confirm-customer-receipt', $withdrawal) }}">@csrf<button type="submit" class="success">Customer Confirm Receipt</button></form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($request->last_status === 'in_progress')
                    <section class="card">
                        <h2>Complete Service</h2>
                        <div class="grid">
                            <form method="post" action="{{ route('simulation.final-invoice', $request) }}" class="stack">
                                @csrf
                                <h3>Create Final Invoice</h3>
                                <label>Service
                                    <select name="services[]">
                                        <option value="">No service</option>
                                        @foreach ($services as $service)
                                            <option value="{{ $service->id }}">{{ $service->name_en ?: $service->name_ar }} - {{ number_format((float) $service->price, 2) }} SAR</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>Spare Part
                                    <select name="spare_parts[0][spare_part_id]">
                                        <option value="">No spare part</option>
                                        @foreach ($spareParts as $part)
                                            <option value="{{ $part->id }}">{{ $part->name_en ?: $part->name_ar }} - {{ number_format((float) $part->price, 2) }} SAR</option>
                                        @endforeach
                                    </select>
                                </label>
                                <div class="grid">
                                    <label>Quantity<input type="number" min="1" name="spare_parts[0][quantity]" value="1"></label>
                                    <label>Custom Price<input type="number" min="0" step="0.01" name="spare_parts[0][price]"></label>
                                </div>
                                <label>Note<input type="text" name="note" placeholder="Optional status note"></label>
                                <button type="submit">Create Final Invoice</button>
                            </form>

                            <form method="post" action="{{ route('simulation.complete-without-payment', $request) }}" class="stack">
                                @csrf
                                <h3>Complete Without Payment</h3>
                                <p class="muted">Creates a zero-service invoice and closes this request immediately.</p>
                                <label>Note<input type="text" name="note" placeholder="Optional status note"></label>
                                <div class="actions"><button type="submit" class="secondary">Create Zero Invoice and Complete</button></div>
                            </form>
                        </div>
                    </section>
                @endif

                @if ($request->last_status === 'waiting_for_payment')
                    <section class="card">
                        <h2>Final Payment</h2>
                        <form method="post" action="{{ route('simulation.pay-final', $request) }}" class="grid" style="grid-template-columns: 1fr auto; align-items: end;">
                            @csrf
                            <label>Note<input type="text" name="note" placeholder="Optional status note"></label>
                            <button type="submit" class="success">Pay Final Invoice and Complete Request</button>
                        </form>
                    </section>
                @endif

                <section class="card">
                    <h2>Invoice Timeline</h2>
                    <div class="timeline">
                        @forelse ($request->invoices as $invoice)
                            <div class="line">
                                <div>
                                    <strong>#{{ $invoice->id }} - {{ str_replace('_', ' ', $invoice->invoice_type) }}</strong>
                                    <div class="muted">{{ ucfirst($invoice->status) }} | {{ $invoice->payment_method ?: 'No payment method' }}</div>
                                </div>
                                <strong>{{ number_format((float) $invoice->total, 2) }} SAR</strong>
                            </div>
                        @empty
                            <div class="muted">No invoices created yet.</div>
                        @endforelse
                    </div>
                </section>
            @endif
        </div>
    </main>

    <script>
        const customerSelect = document.getElementById('customer_id');
        const addressSelect = document.getElementById('address_id');

        function filterAddresses() {
            const customerId = customerSelect.value;
            for (const option of addressSelect.options) {
                if (!option.value) continue;
                option.hidden = Boolean(customerId) && option.dataset.customer !== customerId;
            }
            if (addressSelect.selectedOptions[0] && addressSelect.selectedOptions[0].hidden) addressSelect.value = '';
        }

        customerSelect.addEventListener('change', filterAddresses);
        filterAddresses();
    </script>
</body>
</html>
