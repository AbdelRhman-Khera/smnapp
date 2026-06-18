<x-filament::widget>
    <x-filament::card>
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="mb-5 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">Appointment Details</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Assigned technician visit information.</p>
                </div>

                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $this->slot ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                    {{ $this->slot ? 'Assigned' : 'Not Assigned' }}
                </span>
            </div>

            @if ($this->slot)
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                        <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Date</div>
                        <div class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">
                            {{ \Carbon\Carbon::parse($this->slot->date)->format('F d, Y') }}
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                        <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Time</div>
                        <div class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">
                            {{ \Carbon\Carbon::parse($this->slot->time)->format('h:i A') }}
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                        <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Technician</div>
                        <div class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">
                            {{ ($this->slot->technician?->first_name . ' ' . $this->slot->technician?->last_name) ?: 'Not Assigned' }}
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-5 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-300">
                    No appointment assigned for this request.
                </div>
            @endif
        </div>
    </x-filament::card>
</x-filament::widget>
