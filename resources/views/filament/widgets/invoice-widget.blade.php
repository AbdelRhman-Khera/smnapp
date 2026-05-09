<x-filament::widget>
    <x-filament::card>

        @if ($this->invoice)

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- LEFT SIDE --}}
                <div class="space-y-4 lg:col-span-2">

                    <div class="flex items-center justify-between">
                        <h3 class="text-2xl font-bold">
                            Invoice #{{ $this->invoice->id }}
                        </h3>

                        <span
                            class="px-4 py-1 text-sm font-bold text-white rounded-xl
                            {{ $this->invoice->status === 'completed'
                                ? 'bg-success-600'
                                : 'bg-warning-600' }}">
                            {{ ucfirst($this->invoice->status) }}
                        </span>
                    </div>

                    {{-- BASIC INFO --}}
                    <div class="grid grid-cols-2 gap-4 p-4 border rounded-xl">

                        <div>
                            <div class="text-sm text-gray-500">
                                Total Amount
                            </div>

                            <div class="text-xl font-bold">
                                {{ number_format($this->invoice->total, 2) }} SAR
                            </div>
                        </div>

                        <div>
                            <div class="text-sm text-gray-500">
                                Payment Method
                            </div>

                            <div class="font-bold">
                                {{ ucfirst($this->invoice->payment_method) }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm text-gray-500">
                                SAP Sales Order
                            </div>

                            <div class="font-bold">
                                {{ $this->record->sap_sales_order_no ?? '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm text-gray-500">
                                Invoice Date
                            </div>

                            <div class="font-bold">
                                {{ $this->invoice->created_at?->format('Y-m-d h:i A') }}
                            </div>
                        </div>

                    </div>

                    {{-- PAYMENT DETAILS --}}
                    @if ($this->invoice->payment_details)

                        <div class="p-4 border rounded-xl">

                            <h4 class="mb-4 text-lg font-bold">
                                Payment Details
                            </h4>

                            {{-- ONLINE --}}
                            @if ($this->invoice->payment_method === 'online')

                                <div class="space-y-2">

                                    <div>
                                        <span class="font-bold">Transaction Ref:</span>

                                        {{ $this->invoice->payment_details['tran_ref'] ?? '-' }}
                                    </div>

                                    <div>
                                        <span class="font-bold">Payment Result:</span>

                                        {{ $this->invoice->payment_details['payment_result']['response_message'] ?? '-' }}
                                    </div>

                                </div>

                            @endif

                            {{-- REMITTANCE --}}
                            @if ($this->invoice->payment_method === 'remittance')

                                @php
                                    $remittance =
                                        $this->invoice->payment_details['remittance_file'] ?? null;
                                @endphp

                                @if ($remittance)

                                    <div class="space-y-4">

                                        <a href="{{ asset('storage/' . $remittance) }}"
                                           target="_blank"
                                           class="inline-flex items-center px-4 py-2 text-white rounded-lg bg-primary-600">

                                            Open Remittance File
                                        </a>

                                        @php
                                            $extension = pathinfo($remittance, PATHINFO_EXTENSION);
                                        @endphp

                                        {{-- IMAGE --}}
                                        @if (in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'webp']))

                                            <img
                                                src="{{ asset('storage/' . $remittance) }}"
                                                class="w-64 border rounded-xl"
                                            >

                                        @endif

                                    </div>

                                @endif

                            @endif

                        </div>

                    @endif

                </div>

                {{-- RIGHT SIDE --}}
                <div>

                    <div class="sticky p-4 border rounded-xl top-4">

                        <h4 class="mb-4 text-lg font-bold text-center">
                            ZATCA QR Code
                        </h4>

                        <div class="flex justify-center">

                            <img
                                src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($this->invoice->qr_code) }}"
                                class="border rounded-lg"
                            >

                        </div>

                        <div class="mt-4 text-xs text-gray-500 break-all">
                            {{ $this->invoice->qr_code }}
                        </div>

                    </div>

                </div>

            </div>

            {{-- SPARE PARTS --}}
            <div class="mt-8">

                <h4 class="mb-3 text-xl font-bold">
                    Spare Parts
                </h4>

                <div class="overflow-hidden border rounded-xl">

                    <table class="w-full">

                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-3 text-left">Name</th>
                                <th class="p-3 text-center">Qty</th>
                                <th class="p-3 text-right">Price</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse ($this->invoice->spareParts as $part)

                                <tr class="border-t">

                                    <td class="p-3">
                                        {{ $part->name }}
                                    </td>

                                    <td class="p-3 text-center">
                                        {{ $part->pivot->quantity }}
                                    </td>

                                    <td class="p-3 text-right">
                                        {{ number_format($part->pivot->price, 2) }} SAR
                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="3" class="p-4 text-center text-gray-500">
                                        No Spare Parts
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

            {{-- SERVICES --}}
            <div class="mt-8">

                <h4 class="mb-3 text-xl font-bold">
                    Services
                </h4>

                <div class="overflow-hidden border rounded-xl">

                    <table class="w-full">

                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-3 text-left">Name</th>
                                <th class="p-3 text-right">Price</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse ($this->invoice->services as $service)

                                <tr class="border-t">

                                    <td class="p-3">
                                        {{ $service->name }}
                                    </td>

                                    <td class="p-3 text-right">
                                        {{ number_format($service->price, 2) }} SAR
                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="2" class="p-4 text-center text-gray-500">
                                        No Services
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        @else

            <div class="text-gray-500">
                No invoice found.
            </div>

        @endif

    </x-filament::card>
</x-filament::widget>
