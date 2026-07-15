<x-filament::widget>
    <x-filament::card>
        @php
            $invoices = $this->invoices ?? collect();
            $record = $this->record;

            $typeLabels = [
                'visit_fee' => 'Visit Fee',
                'final' => 'Final Invoice',
                'workshop' => 'Workshop Invoice',
                'zero_service' => 'Zero Service',
            ];

            $typeStyles = [
                'visit_fee' => 'bg-info-100 text-info-800 ring-info-200 dark:bg-info-500/15 dark:text-info-200 dark:ring-info-500/25',
                'final' => 'bg-success-100 text-success-800 ring-success-200 dark:bg-success-500/15 dark:text-success-200 dark:ring-success-500/25',
                'workshop' => 'bg-warning-100 text-warning-800 ring-warning-200 dark:bg-warning-500/15 dark:text-warning-200 dark:ring-warning-500/25',
                'zero_service' => 'bg-gray-100 text-gray-700 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700',
            ];
        @endphp

        <style>
            .smn-collapse > summary::-webkit-details-marker {
                display: none;
            }

            .smn-collapse[open] .smn-collapse-chevron {
                transform: rotate(180deg);
            }

            .smn-widget-body {
                background: rgba(249, 250, 251, 0.72);
            }

            .smn-outer-panel,
            .smn-widget-summary,
            .smn-panel-card,
            .smn-invoice-summary,
            .smn-table-heading,
            .smn-muted-card,
            .smn-table-panel,
            .smn-qr-card,
            .smn-chevron-button {
                background: #ffffff;
                border-color: #e5e7eb;
            }

            .smn-muted-card,
            .smn-qr-card {
                background: #f9fafb;
            }

            .smn-widget-summary {
                background: rgba(239, 246, 255, 0.78);
                border-color: rgba(37, 99, 235, 0.18);
            }

            .smn-primary-card {
                background: rgba(239, 246, 255, 0.82);
                border-color: rgba(37, 99, 235, 0.18);
            }

            .smn-info-card {
                background: rgba(239, 246, 255, 0.9);
                border-color: rgba(14, 165, 233, 0.24);
            }

            .smn-warning-card {
                background: rgba(255, 251, 235, 0.92);
                border-color: rgba(245, 158, 11, 0.24);
            }

            .dark .smn-widget-body {
                background: rgba(3, 7, 18, 0.34);
            }

            .dark .smn-outer-panel,
            .dark .smn-widget-summary,
            .dark .smn-panel-card,
            .dark .smn-invoice-summary,
            .dark .smn-table-heading,
            .dark .smn-table-panel {
                background: #111827;
                border-color: #374151;
            }

            .dark .smn-widget-summary {
                background: rgba(37, 99, 235, 0.12);
                border-color: rgba(96, 165, 250, 0.22);
            }

            .smn-table-panel thead {
                background: #f3f4f6;
                color: #4b5563;
            }

            .smn-table-panel tbody tr {
                background: #ffffff;
            }

            .smn-invoice-summary:hover {
                background: #f9fafb;
            }

            .dark .smn-table-panel thead {
                background: #1f2937;
                color: #d1d5db;
            }

            .dark .smn-table-panel tbody tr {
                background: #111827;
            }

            .dark .smn-chevron-button {
                background: #111827;
                border-color: rgba(96, 165, 250, 0.3);
                color: #93c5fd;
            }

            .dark .smn-muted-card,
            .dark .smn-qr-card {
                background: rgba(31, 41, 55, 0.82);
                border-color: #374151;
            }

            .dark .smn-primary-card {
                background: rgba(37, 99, 235, 0.14);
                border-color: rgba(96, 165, 250, 0.24);
            }

            .dark .smn-info-card {
                background: rgba(14, 165, 233, 0.12);
                border-color: rgba(125, 211, 252, 0.24);
            }

            .dark .smn-warning-card {
                background: rgba(245, 158, 11, 0.12);
                border-color: rgba(251, 191, 36, 0.24);
            }

            .dark .smn-invoice-summary:hover {
                background: #1f2937;
            }

            .smn-table-heading {
                background: #f3f4f6;
                border-color: #e5e7eb;
            }
        </style>

        <details class="smn-collapse smn-outer-panel overflow-hidden rounded-xl border shadow-sm" open>
            <summary class="smn-widget-summary flex cursor-pointer list-none flex-wrap items-center justify-between gap-6 border-b px-6 py-5 transition sm:px-8 sm:py-6">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full bg-primary-600 dark:bg-primary-400"></span>
                        <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">Invoices</h2>
                    </div>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        Maintenance Request #{{ $record?->id }} &middot; {{ $invoices->count() }} invoice{{ $invoices->count() === 1 ? '' : 's' }}
                    </p>
                </div>

                <div class="smn-chevron-button flex h-9 w-9 items-center justify-center rounded-full border text-primary-700 shadow-sm transition">
                    <svg class="smn-collapse-chevron h-5 w-5 transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                    </svg>
                </div>
            </summary>

            <div class="smn-widget-body p-6 sm:p-8">
                @if ($invoices->isNotEmpty())
                    <div class="grid gap-6">
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

                            <details class="smn-collapse smn-panel-card overflow-hidden rounded-xl border shadow-sm" open>
                                <summary class="smn-invoice-summary flex cursor-pointer list-none flex-wrap items-start justify-between gap-6 border-b px-6 py-5 transition sm:px-7">
                                    <div class="min-w-0">
                                        <div class="mb-2 flex flex-wrap items-center gap-2">
                                            <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $typeStyles[$invoiceType] ?? 'bg-gray-100 text-gray-700 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700' }}">
                                                {{ $typeLabels[$invoiceType] ?? ucfirst(str_replace('_', ' ', $invoiceType)) }}
                                            </span>
                                            <span class="rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $invoice->status === 'completed' ? 'bg-success-100 text-success-800 ring-success-200 dark:bg-success-500/15 dark:text-success-200 dark:ring-success-500/25' : 'bg-warning-100 text-warning-800 ring-warning-200 dark:bg-warning-500/15 dark:text-warning-200 dark:ring-warning-500/25' }}">
                                                {{ ucfirst($invoice->status ?: '-') }}
                                            </span>
                                        </div>

                                        <h3 class="text-xl font-bold text-gray-950 dark:text-white">
                                            Invoice #{{ $invoice->id }}
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            Created {{ $invoice->created_at?->format('Y-m-d h:i A') }}
                                        </p>
                                    </div>

                                    <span class="smn-chevron-button flex h-9 w-9 items-center justify-center rounded-full border text-gray-700 transition">
                                        <svg class="smn-collapse-chevron h-5 w-5 transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </summary>

                                <div class="space-y-6 p-6 sm:p-7">
                                    <div class="flex justify-end">
                                        <a
                                            href="{{ route('admin.sales-invoices.print', $invoice) }}"
                                            target="_blank"
                                            class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500"
                                        >
                                            Print Invoice
                                        </a>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                        <div class="smn-primary-card rounded-xl border p-6">
                                            <div class="text-xs font-semibold uppercase text-primary-700 dark:text-primary-300">Total Amount</div>
                                            <div class="mt-4 text-3xl font-bold leading-tight text-gray-950 dark:text-white">
                                                {{ number_format((float) $invoice->total, 2) }} SAR
                                            </div>
                                        </div>

                                        <div class="smn-muted-card rounded-xl border p-6">
                                            <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Payment Method</div>
                                            <div class="mt-4 text-lg font-bold leading-tight text-gray-950 dark:text-white">
                                                {{ $invoice->payment_method ? ucfirst($invoice->payment_method) : '-' }}
                                            </div>
                                        </div>

                                        <div class="smn-muted-card rounded-xl border p-6">
                                            <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">SAP Sales Order</div>
                                            <div class="mt-4 break-all text-lg font-bold leading-tight text-gray-950 dark:text-white">
                                                {{ $invoice->sap_sales_order_no ?: '-' }}
                                            </div>
                                        </div>

                                        <div class="smn-muted-card rounded-xl border p-4">
                                            <div class="text-center text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">ZATCA QR</div>
                                            @if ($invoice->qr_code)
                                                <div class="mt-2 flex justify-center">
                                                    <a href="https://api.qrserver.com/v1/create-qr-code/?size=400x400&data={{ urlencode($invoice->qr_code) }}" target="_blank" title="Open QR code">
                                                        <img
                                                            src="https://api.qrserver.com/v1/create-qr-code/?size=110x110&data={{ urlencode($invoice->qr_code) }}"
                                                            class="h-24 w-24 rounded-md border border-gray-200 bg-white object-contain p-1 shadow-sm dark:border-gray-700"
                                                            alt="Invoice QR Code"
                                                        >
                                                    </a>
                                                </div>
                                            @else
                                                <div class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">No QR yet.</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="grid gap-6">
                                        <div class="space-y-6">
                                            <div class="grid gap-6 xl:grid-cols-2">
                                                <div class="smn-table-panel overflow-hidden rounded-xl border">
                                                    <div class="smn-table-heading border-b px-6 py-5">
                                                        <h4 class="font-bold text-gray-950 dark:text-white">Spare Parts</h4>
                                                    </div>
                                                    <div class="overflow-x-auto">
                                                        <table class="w-full text-sm">
                                                            <thead class="text-xs uppercase">
                                                                <tr>
                                                                    <th class="px-6 py-4 text-start font-semibold">Name</th>
                                                                    <th class="px-6 py-4 text-center font-semibold">Qty</th>
                                                                    <th class="px-6 py-4 text-end font-semibold">Price</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                                                @forelse ($invoice->spareParts as $part)
                                                                    <tr>
                                                                        <td class="px-6 py-5 font-semibold text-gray-950 dark:text-white">{{ $part->name }}</td>
                                                                        <td class="px-6 py-5 text-center text-gray-700 dark:text-gray-300">{{ $part->pivot->quantity }}</td>
                                                                        <td class="px-6 py-5 text-end font-semibold text-gray-950 dark:text-white">{{ number_format((float) $part->pivot->price, 2) }} SAR</td>
                                                                    </tr>
                                                                @empty
                                                                    <tr>
                                                                        <td colspan="3" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No spare parts.</td>
                                                                    </tr>
                                                                @endforelse
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>

                                                <div class="smn-table-panel overflow-hidden rounded-xl border">
                                                    <div class="smn-table-heading border-b px-6 py-5">
                                                        <h4 class="font-bold text-gray-950 dark:text-white">Services</h4>
                                                    </div>
                                                    <div class="overflow-x-auto">
                                                        <table class="w-full text-sm">
                                                            <thead class="text-xs uppercase">
                                                                <tr>
                                                                    <th class="px-6 py-4 text-start font-semibold">Name</th>
                                                                    <th class="px-6 py-4 text-end font-semibold">Price</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                                                @forelse ($invoice->services as $service)
                                                                    <tr>
                                                                        <td class="px-6 py-5 font-semibold text-gray-950 dark:text-white">{{ $service->name }}</td>
                                                                        <td class="px-6 py-5 text-end font-semibold text-gray-950 dark:text-white">{{ number_format((float) $service->price, 2) }} SAR</td>
                                                                    </tr>
                                                                @empty
                                                                    <tr>
                                                                        <td colspan="2" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No services.</td>
                                                                    </tr>
                                                                @endforelse
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>

                                            @if ($paymentMethod === 'online' && $invoice->payment_details)
                                                <div class="smn-info-card rounded-xl border p-5">
                                                    <h4 class="mb-3 font-bold text-gray-950 dark:text-white">Online Payment</h4>
                                                    <div class="grid gap-4 sm:grid-cols-2">
                                                        <div>
                                                            <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Transaction Ref</div>
                                                            <div class="mt-1 break-all font-semibold text-gray-950 dark:text-white">{{ $invoice->payment_details['tran_ref'] ?? $invoice->payment_details['reference'] ?? '-' }}</div>
                                                        </div>
                                                        <div>
                                                            <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Result</div>
                                                            <div class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $invoice->payment_details['payment_result']['response_message'] ?? '-' }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            @if ($paymentMethod === 'remittance' && $remittanceUrl)
                                                <div class="smn-warning-card rounded-xl border p-5">
                                                    <div class="mb-3 flex items-center justify-between gap-3">
                                                        <h4 class="font-bold text-gray-950 dark:text-white">Remittance File</h4>
                                                        <a href="{{ $remittanceUrl }}" target="_blank" class="text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-300">Open</a>
                                                    </div>
                                                    @if ($isRemittanceImage)
                                                        <img src="{{ $remittanceUrl }}" class="max-h-56 w-full rounded-lg border border-gray-200 bg-white object-contain dark:border-gray-700 dark:bg-gray-900" alt="Remittance file">
                                                    @else
                                                        <a href="{{ $remittanceUrl }}" target="_blank" class="text-primary-600 hover:text-primary-500 dark:text-primary-300">View uploaded remittance file</a>
                                                    @endif
                                                </div>
                                            @endif

                                            @if ($paymentMethod === 'machine')
                                                <div class="smn-warning-card rounded-xl border p-5">
                                                    <div class="mb-3 flex items-center justify-between gap-3">
                                                        <h4 class="font-bold text-gray-950 dark:text-white">Machine Payment Picture</h4>
                                                        @if ($machinePicUrl)
                                                            <a href="{{ $machinePicUrl }}" target="_blank" class="text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-300">Open</a>
                                                        @endif
                                                    </div>
                                                    @if ($machinePicUrl && $isMachineImage)
                                                        <img src="{{ $machinePicUrl }}" class="max-h-64 w-full rounded-lg border border-gray-200 bg-white object-contain dark:border-gray-700 dark:bg-gray-900" alt="Machine payment picture">
                                                    @elseif ($machinePicUrl)
                                                        <a href="{{ $machinePicUrl }}" target="_blank" class="text-primary-600 hover:text-primary-500 dark:text-primary-300">View uploaded machine payment file</a>
                                                    @else
                                                        <div class="smn-panel-card rounded-lg border border-dashed p-5 text-sm text-gray-600 dark:text-gray-300">No machine payment picture uploaded.</div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </details>
                        @endforeach
                    </div>
                @else
                    <div class="smn-panel-card rounded-xl border border-dashed p-6 text-center text-gray-600 dark:text-gray-300">
                        No invoice found.
                    </div>
                @endif
            </div>
        </details>
    </x-filament::card>
</x-filament::widget>
