<x-filament::widget>
    <x-filament::card class="w-full">
        <div class="w-full p-6 bg-white rounded-lg shadow-md">
            <h2 class="mb-4 text-xl font-bold">Appointment Details</h2>

            @if ($this->slot)
                <div class="grid grid-cols-2 gap-4 text-lg">
                    <div>
                        <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($this->slot->date)->format('F d, Y') }}</p>
                        <p><strong>Time:</strong> {{ \Carbon\Carbon::parse($this->slot->time)->format('h:i A') }}</p>
                        <p><strong>Technician:</strong> {{ $this->slot->technician->first_name .' '. $this->slot->technician->last_name ?? 'Not Assigned' }}</p>
                    </div>

                </div>
            @else
                <p class="text-gray-500">No appointment assigned for this request.</p>
            @endif
        </div>
    </x-filament::card>
</x-filament::widget>
