<x-filament::widget>
    <x-filament::card>
        <h3 class="mb-4 text-lg font-bold">Invoice Details</h3>

        @if ($this->invoice)
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <strong>Invoice ID:</strong> {{ $this->invoice->id }}
                </div>
                <div>
                    <strong>Total Amount:</strong> {{ number_format($this->invoice->total, 2) }} $
                </div>
                <div>
                    <strong>Payment Method:</strong> {{ ucfirst($this->invoice->payment_method) }}
                </div>
                <div>
                    <strong>Status:</strong>
                    <span class="px-2 py-1 text-white rounded-md" style="background: {{ $this->invoice->status === 'completed' ? 'green' : 'orange' }}">
                        {{ ucfirst($this->invoice->status) }}
                    </span>
                </div>
                {{-- <div class="col-span-2">
                    <strong>Payment Details:</strong>
                    <pre class="p-2 bg-gray-100 rounded">{{ json_encode($this->invoice->payment_details, JSON_PRETTY_PRINT) }}</pre>
                </div> --}}
            </div>

            <hr class="my-4">

            <h4 class="mb-2 text-lg font-bold">Spare Parts</h4>
            <table class="w-full border border-collapse border-gray-300">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2 border">Name</th>
                        <th class="p-2 border">Quantity</th>
                        <th class="p-2 border">Price</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->invoice->spareParts as $part)
                        <tr>
                            <td class="p-2 border">{{ $part->name }}</td>
                            <td class="p-2 text-center border">{{ $part->pivot->quantity }}</td>
                            <td class="p-2 border">{{ number_format($part->pivot->price, 2) }} sar</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="p-2 text-center">No Spare Parts</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <hr class="my-4">

            <h4 class="mb-2 text-lg font-bold">Services</h4>
            <table class="w-full border border-collapse border-gray-300">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2 border">Name</th>
                        <th class="p-2 border">Price</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->invoice->services as $service)
                        <tr>
                            <td class="p-2 border">{{ $service->name }}</td>
                            <td class="p-2 border">{{ number_format($service->price, 2) }} sar</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="p-2 text-center">No Services</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @else
            <p class="text-gray-500">No invoice found for this request.</p>
        @endif
    </x-filament::card>
</x-filament::widget>
