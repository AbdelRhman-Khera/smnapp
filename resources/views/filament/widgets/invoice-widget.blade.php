<x-filament::widget>
    <x-filament::card>
        @php
            $invoices = $this->invoices ?? collect();
            $record = $this->record;

            $typeLabels = [
                'visit_fee' => 'Visit Fee',
                'final' => 'Final Invoice',
                'zero_service' => 'Zero Service',
            ];

            $typeStyles = [
                'visit_fee' => 'bg-info-100 text-info-700 dark:bg-info-500/20 dark:text-info-300',
                'final' => 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300',
                'zero_service' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
            ];
        @endphp

        @if ($invoices->isNotEmpty())
            <div class="space-y-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                            Invoices
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Maintenance Request #{{ $record?->id }} &middot; {{ $invoices->count() }} invoice{{ $invoices->count() > 1 ? 's' : '' }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-5">
                    @foreach ($invoices as $invoice)
                        @php
                            $paymentMethod = strtolower((string) $invoice->payment_method);
                            $machinePic = $invoice->machine_pic;
                            $machinePicUrl = $machinePic ? asset('storage/' . $machinePic) : null;
                            $machinePicExtension = $machinePic ? strtolower(pathinfo($machinePic, PATHINFO_EXTENSION)) : null;
                            $isMachineImage = in_array($machinePicExtension, ['jpg', 'jpeg', 'png', 'webp']);
                            $remittance = $invoice->payment_details['remittance_file'] ?? $invoice->remittance ?? null;
                            $remittanceUrl = $remittance ? asset('storage/' . $remittance) : null;
                            $remittanceExtension = $remittance ? strtolower(pathinfo($remittance, PATHINFO_EXTENSION)) : null;
                            $isRemittanceImage = in_array($remittanceExtension, ['jpg', 'jpeg', 'png', 'webp']);
                            $invoiceType = $invoice->invoice_type ?: 'final';
                        @endphp

                        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-200 bg-gray-50 px-6 py-5 dark:border-gray-700 dark:bg-gray-800/70">
                                <div class="min-w-0">
                                    <div class="mb-2 flex flex-wrap items-center gap-2">
                                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $typeStyles[$invoiceType] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                            {{ $typeLabels[$invoiceType] ?? ucfirst(str_replace('_', ' ', $invoiceType)) }}
                                        </span>
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $invoice->status === 'completed' ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300' : 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300' }}">
                                            {{ ucfirst($invoice->status ?: '-') }}
                                        </span>
                                    </div>

                                    <h3 class="text-2xl font-bold text-gray-950 dark:text-white">
                                        Invoice #{{ $invoice->id }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Created {{ $invoice->created_at?->format('Y-m-d h:i A') }}
                                    </p>
                                </div>

                                <a
                                    href="{{ route('admin.sales-invoices.print', $invoice) }}"
                                    target="_blank"
                                    class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500"
                                >
                                    Print Invoice
                                </a>
                            </div>

                            <div class="grid gap-4 p-6 sm:grid-cols-2 xl:grid-cols-4">
                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                                    <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total Amount</div>
                                    <div class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">
                                        {{ number_format((float) $invoice->total, 2) }} SAR
                                    </div>
                                </div>

                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                                    <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Payment Method</div>
                                    <div class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">
                                        {{ $invoice->payment_method ? ucfirst($invoice->payment_method) : '-' }}
                                    </div>
                                </div>

                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                                    <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">SAP Sales Order</div>
                                    <div class="mt-2 break-all text-lg font-semibold text-gray-950 dark:text-white">
                                        {{ $record?->sap_sales_order_no ?: '-' }}
                                    </div>
                                </div>

                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                                    <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Invoice Date</div>
                                    <div class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">
                                        {{ $invoice->created_at?->format('Y-m-d h:i A') }}
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-5 px-6 pb-6 lg:grid-cols-3">
                                <div class="space-y-5 lg:col-span-2">
                                    <div class="grid gap-5 xl:grid-cols-2">
                                        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                                            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/70">
                                                <h4 class="font-bold text-gray-950 dark:text-white">Spare Parts</h4>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-sm">
                                                    <thead class="text-xs uppercase text-gray-500 dark:text-gray-400">
                                                        <tr>
                                                            <th class="px-4 py-3 text-start">Name</th>
                                                            <th class="px-4 py-3 text-center">Qty</th>
                                                            <th class="px-4 py-3 text-end">Price</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                                        @forelse ($invoice->spareParts as $part)
                                                            <tr>
                                                                <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $part->name }}</td>
                                                                <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ $part->pivot->quantity }}</td>
                                                                <td class="px-4 py-3 text-end font-semibold text-gray-950 dark:text-white">{{ number_format((float) $part->pivot->price, 2) }} SAR</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="3" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No spare parts.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                                            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/70">
                                                <h4 class="font-bold text-gray-950 dark:text-white">Services</h4>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-sm">
                                                    <thead class="text-xs uppercase text-gray-500 dark:text-gray-400">
                                                        <tr>
                                                            <th class="px-4 py-3 text-start">Name</th>
                                                            <th class="px-4 py-3 text-end">Price</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                                        @forelse ($invoice->services as $service)
                                                            <tr>
                                                                <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $service->name }}</td>
                                                                <td class="px-4 py-3 text-end font-semibold text-gray-950 dark:text-white">{{ number_format((float) $service->price, 2) }} SAR</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="2" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No services.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    @if ($paymentMethod === 'online' && $invoice->payment_details)
                                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                                            <h4 class="mb-3 font-bold text-gray-950 dark:text-white">Online Payment</h4>
                                            <div class="grid gap-4 sm:grid-cols-2">
                                                <div>
                                                    <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Transaction Ref</div>
                                                    <div class="mt-1 break-all font-semibold text-gray-950 dark:text-white">{{ $invoice->payment_details['tran_ref'] ?? $invoice->payment_details['reference'] ?? '-' }}</div>
                                                </div>
                                                <div>
                                                    <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Result</div>
                                                    <div class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $invoice->payment_details['payment_result']['response_message'] ?? '-' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($paymentMethod === 'remittance' && $remittanceUrl)
                                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                                            <div class="mb-3 flex items-center justify-between gap-3">
                                                <h4 class="font-bold text-gray-950 dark:text-white">Remittance File</h4>
                                                <a href="{{ $remittanceUrl }}" target="_blank" class="text-sm font-semibold text-primary-600 hover:text-primary-500">Open</a>
                                            </div>
                                            @if ($isRemittanceImage)
                                                <img src="{{ $remittanceUrl }}" class="max-h-56 w-full rounded-lg border border-gray-200 object-contain dark:border-gray-700" alt="Remittance file">
                                            @else
                                                <a href="{{ $remittanceUrl }}" target="_blank" class="text-primary-600 hover:text-primary-500">View uploaded remittance file</a>
                                            @endif
                                        </div>
                                    @endif

                                    @if ($paymentMethod === 'machine')
                                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                                            <div class="mb-3 flex items-center justify-between gap-3">
                                                <h4 class="font-bold text-gray-950 dark:text-white">Machine Payment Picture</h4>
                                                @if ($machinePicUrl)
                                                    <a href="{{ $machinePicUrl }}" target="_blank" class="text-sm font-semibold text-primary-600 hover:text-primary-500">Open</a>
                                                @endif
                                            </div>
                                            @if ($machinePicUrl && $isMachineImage)
                                                <img src="{{ $machinePicUrl }}" class="max-h-64 w-full rounded-lg border border-gray-200 object-contain dark:border-gray-700" alt="Machine payment picture">
                                            @elseif ($machinePicUrl)
                                                <a href="{{ $machinePicUrl }}" target="_blank" class="text-primary-600 hover:text-primary-500">View uploaded machine payment file</a>
                                            @else
                                                <div class="rounded-lg border border-dashed border-gray-300 p-5 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">No machine payment picture uploaded.</div>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <aside class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                                    <h4 class="mb-4 text-center font-bold text-gray-950 dark:text-white">QR Code</h4>

                                    @if ($invoice->qr_code)
                                        <div class="flex justify-center">
                                            <img
                                                src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($invoice->qr_code) }}"
                                                class="h-44 w-44 rounded-lg border border-gray-200 object-contain p-2 dark:border-gray-700"
                                                alt="Invoice QR Code"
                                            >
                                        </div>
                                        <div class="mt-4 max-h-24 overflow-auto break-all rounded-lg bg-gray-50 p-3 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                            {{ $invoice->qr_code }}
                                        </div>
                                    @else
                                        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                            No QR code available.
                                        </div>
                                    @endif
                                </aside>
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>
        @else
            <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-gray-600 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-300">
                No invoice found.
            </div>
        @endif
    </x-filament::card>
</x-filament::widget>
