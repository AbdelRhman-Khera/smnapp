<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    {{-- LEFT INFO --}}
    <div class="lg:col-span-2">

        <div class="h-full p-6 bg-white border shadow-sm rounded-2xl">

            <div class="flex items-center justify-between mb-6">

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

            <div class="grid grid-cols-2 gap-6">

                <div>
                    <div class="mb-1 text-sm text-gray-500">
                        Total Amount
                    </div>

                    <div class="text-2xl font-bold text-primary-600">
                        {{ number_format($this->invoice->total, 2) }} SAR
                    </div>
                </div>

                <div>
                    <div class="mb-1 text-sm text-gray-500">
                        Payment Method
                    </div>

                    <div class="text-lg font-bold">
                        {{ ucfirst($this->invoice->payment_method) }}
                    </div>
                </div>

                <div>
                    <div class="mb-1 text-sm text-gray-500">
                        SAP Sales Order
                    </div>

                    <div class="font-semibold">
                        {{ $this->record->sap_sales_order_no ?? '-' }}
                    </div>
                </div>

                <div>
                    <div class="mb-1 text-sm text-gray-500">
                        Invoice Date
                    </div>

                    <div class="font-semibold">
                        {{ $this->invoice->created_at?->format('Y-m-d h:i A') }}
                    </div>
                </div>

            </div>

        </div>

    </div>

    {{-- QR CODE --}}
    <div>

        <div class="h-full p-6 text-center bg-white border shadow-sm rounded-2xl">

            <h4 class="mb-4 text-lg font-bold">
                ZATCA QR Code
            </h4>

            <div class="flex justify-center">

                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($this->invoice->qr_code) }}"
                    class="border shadow-sm rounded-xl"
                >

            </div>

        </div>

    </div>

</div>

{{-- PAYMENT DETAILS --}}
@if ($this->invoice->payment_details)

    <div class="p-6 mt-6 bg-white border shadow-sm rounded-2xl">

        <h4 class="mb-5 text-xl font-bold">
            Payment Details
        </h4>

        {{-- ONLINE --}}
        @if ($this->invoice->payment_method === 'online')

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

                <div class="p-4 border rounded-xl">
                    <div class="mb-1 text-sm text-gray-500">
                        Transaction Ref
                    </div>

                    <div class="font-bold">
                        {{ $this->invoice->payment_details['tran_ref'] ?? '-' }}
                    </div>
                </div>

                <div class="p-4 border rounded-xl">
                    <div class="mb-1 text-sm text-gray-500">
                        Payment Result
                    </div>

                    <div class="font-bold">
                        {{ $this->invoice->payment_details['payment_result']['response_message'] ?? '-' }}
                    </div>
                </div>

                <div class="p-4 border rounded-xl">
                    <div class="mb-1 text-sm text-gray-500">
                        Payment Date
                    </div>

                    <div class="font-bold">
                        {{ $this->invoice->updated_at?->format('Y-m-d h:i A') }}
                    </div>
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

                <div class="flex flex-col items-start gap-6 lg:flex-row">

                    <div>

                        <a href="{{ asset('storage/' . $remittance) }}"
                           target="_blank"
                           class="inline-flex items-center px-5 py-2 text-sm font-bold text-white transition rounded-xl bg-primary-600 hover:bg-primary-700">

                            Open Remittance File
                        </a>

                    </div>

                    @php
                        $extension = pathinfo($remittance, PATHINFO_EXTENSION);
                    @endphp

                    @if (in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'webp']))

                        <div>

                            <img
                                src="{{ asset('storage/' . $remittance) }}"
                                class="object-cover w-40 border shadow-sm rounded-xl"
                            >

                        </div>

                    @endif

                </div>

            @endif

        @endif

    </div>

@endif
