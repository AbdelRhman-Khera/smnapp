<x-filament::widget>
    <x-filament::card>
        @if ($this->invoice)
            @php
                $invoice = $this->invoice;
                $record = $this->record;
                $paymentMethod = strtolower((string) $invoice->payment_method);
                $machinePic = $invoice->machine_pic;
                $machinePicUrl = $machinePic ? asset('storage/' . $machinePic) : null;
                $machinePicExtension = $machinePic ? strtolower(pathinfo($machinePic, PATHINFO_EXTENSION)) : null;
                $isMachineImage = in_array($machinePicExtension, ['jpg', 'jpeg', 'png', 'webp']);
                $remittance = $invoice->payment_details['remittance_file'] ?? $invoice->remittance ?? null;
                $remittanceUrl = $remittance ? asset('storage/' . $remittance) : null;
                $remittanceExtension = $remittance ? strtolower(pathinfo($remittance, PATHINFO_EXTENSION)) : null;
                $isRemittanceImage = in_array($remittanceExtension, ['jpg', 'jpeg', 'png', 'webp']);
            @endphp

            <style>
                @media print {
                    body * {
                        visibility: hidden !important;
                    }

                    .invoice-print-area,
                    .invoice-print-area * {
                        visibility: visible !important;
                    }

                    .invoice-print-area {
                        position: absolute !important;
                        inset: 0 auto auto 0 !important;
                        width: 100% !important;
                        padding: 0 !important;
                        background: #fff !important;
                    }

                    .invoice-no-print {
                        display: none !important;
                    }

                    .invoice-print-card {
                        border: 1px solid #d1d5db !important;
                        box-shadow: none !important;
                        break-inside: avoid;
                    }
                }
            </style>

            <div class="invoice-print-area space-y-6">
                <div class="invoice-no-print flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                            Invoice #{{ $invoice->id }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Maintenance Request #{{ $record?->id }}
                        </p>
                    </div>

                    <button
                        type="button"
                        onclick="window.print()"
                        class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                    >
                        Print Invoice
                    </button>
                </div>

                <div class="invoice-print-card overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-200 bg-gray-50 px-5 py-4 dark:border-gray-700 dark:bg-gray-800">
                        <div>
                            <h3 class="text-xl font-bold text-gray-950 dark:text-white">
                                Invoice Summary
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $invoice->created_at?->format('Y-m-d h:i A') }}
                            </p>
                        </div>

                        <span class="rounded-full px-3 py-1 text-sm font-semibold
                            {{ $invoice->status === 'completed'
                                ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300'
                                : 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300' }}">
                            {{ ucfirst($invoice->status) }}
                        </span>
                    </div>

                    <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total Amount</div>
                            <div class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">
                                {{ number_format($invoice->total, 2) }} SAR
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Payment Method</div>
                            <div class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">
                                {{ ucfirst($invoice->payment_method) }}
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">SAP Sales Order</div>
                            <div class="mt-1 break-all text-lg font-semibold text-gray-950 dark:text-white">
                                {{ $record?->sap_sales_order_no ?: '-' }}
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Invoice Date</div>
                            <div class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">
                                {{ $invoice->created_at?->format('Y-m-d h:i A') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="space-y-6 lg:col-span-2">
                        @if ($paymentMethod === 'online' && $invoice->payment_details)
                            <div class="invoice-print-card rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                <h4 class="mb-4 text-lg font-bold text-gray-950 dark:text-white">Online Payment Details</h4>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Transaction Ref</div>
                                        <div class="mt-1 break-all font-semibold text-gray-950 dark:text-white">
                                            {{ $invoice->payment_details['tran_ref'] ?? '-' }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Payment Result</div>
                                        <div class="mt-1 font-semibold text-gray-950 dark:text-white">
                                            {{ $invoice->payment_details['payment_result']['response_message'] ?? '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($paymentMethod === 'remittance' && $remittanceUrl)
                            <div class="invoice-print-card rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                <div class="mb-4 flex items-center justify-between gap-3">
                                    <h4 class="text-lg font-bold text-gray-950 dark:text-white">Remittance File</h4>
                                    <a href="{{ $remittanceUrl }}" target="_blank" class="invoice-no-print text-sm font-semibold text-primary-600 hover:text-primary-500">
                                        Open File
                                    </a>
                                </div>

                                @if ($isRemittanceImage)
                                    <img src="{{ $remittanceUrl }}" class="max-h-80 rounded-lg border border-gray-200 object-contain dark:border-gray-700" alt="Remittance file">
                                @else
                                    <a href="{{ $remittanceUrl }}" target="_blank" class="text-primary-600 hover:text-primary-500">
                                        View uploaded remittance file
                                    </a>
                                @endif
                            </div>
                        @endif

                        @if ($paymentMethod === 'machine')
                            <div class="invoice-print-card rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                <div class="mb-4 flex items-center justify-between gap-3">
                                    <h4 class="text-lg font-bold text-gray-950 dark:text-white">Machine Payment Picture</h4>
                                    @if ($machinePicUrl)
                                        <a href="{{ $machinePicUrl }}" target="_blank" class="invoice-no-print text-sm font-semibold text-primary-600 hover:text-primary-500">
                                            Open File
                                        </a>
                                    @endif
                                </div>

                                @if ($machinePicUrl && $isMachineImage)
                                    <img src="{{ $machinePicUrl }}" class="max-h-96 rounded-lg border border-gray-200 object-contain dark:border-gray-700" alt="Machine payment picture">
                                @elseif ($machinePicUrl)
                                    <a href="{{ $machinePicUrl }}" target="_blank" class="text-primary-600 hover:text-primary-500">
                                        View uploaded machine payment file
                                    </a>
                                @else
                                    <div class="rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                        No machine payment picture uploaded.
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="invoice-print-card rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <h4 class="mb-4 text-center text-lg font-bold text-gray-950 dark:text-white">
                            ZATCA QR Code
                        </h4>

                        @if ($invoice->qr_code)
                            <div class="flex justify-center">
                                <img
                                    src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($invoice->qr_code) }}"
                                    class="rounded-lg border border-gray-200 dark:border-gray-700"
                                    alt="ZATCA QR Code"
                                >
                            </div>

                            <div class="mt-4 break-all text-xs text-gray-500 dark:text-gray-400">
                                {{ $invoice->qr_code }}
                            </div>
                        @else
                            <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                No QR code available.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <div class="invoice-print-card overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                            <h4 class="text-lg font-bold text-gray-950 dark:text-white">Spare Parts</h4>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
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
                                            <td class="px-4 py-3 text-center">{{ $part->pivot->quantity }}</td>
                                            <td class="px-4 py-3 text-end font-semibold">{{ number_format($part->pivot->price, 2) }} SAR</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-4 py-6 text-center text-gray-500">No Spare Parts</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="invoice-print-card overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                            <h4 class="text-lg font-bold text-gray-950 dark:text-white">Services</h4>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3 text-start">Name</th>
                                        <th class="px-4 py-3 text-end">Price</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @forelse ($invoice->services as $service)
                                        <tr>
                                            <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $service->name }}</td>
                                            <td class="px-4 py-3 text-end font-semibold">{{ number_format($service->price, 2) }} SAR</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="px-4 py-6 text-center text-gray-500">No Services</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-gray-500 dark:border-gray-700 dark:text-gray-400">
                No invoice found.
            </div>
        @endif
    </x-filament::card>
</x-filament::widget>
