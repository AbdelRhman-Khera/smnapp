<x-filament::widget>
    <style>
        .smn-widget-panel {
            background: #ffffff;
            border-color: #e5e7eb;
        }

        .smn-widget-card {
            background: #f9fafb;
            border-color: #e5e7eb;
        }

        .smn-empty-panel {
            background: #f9fafb;
            border-color: #d1d5db;
        }

        .dark .smn-widget-panel {
            background: #111827;
            border-color: #374151;
        }

        .dark .smn-widget-card,
        .dark .smn-empty-panel {
            background: rgba(31, 41, 55, 0.82);
            border-color: #374151;
        }
    </style>

    <x-filament::card>
        <div class="smn-widget-panel rounded-xl border p-6 shadow-sm sm:p-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">Appointment Details</h2>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Assigned technician visit information.</p>
                </div>

                <span class="rounded-full px-3.5 py-1.5 text-xs font-semibold {{ $this->slot ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                    {{ $this->slot ? 'Assigned' : 'Not Assigned' }}
                </span>
            </div>

            @if ($this->slot)
                <div class="grid gap-5 md:grid-cols-3">
                    <div class="smn-widget-card rounded-lg border p-5">
                        <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Date</div>
                        <div class="mt-3 text-lg font-bold text-gray-950 dark:text-white">
                            {{ \Carbon\Carbon::parse($this->slot->date)->format('F d, Y') }}
                        </div>
                    </div>

                    <div class="smn-widget-card rounded-lg border p-5">
                        <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Time</div>
                        <div class="mt-3 text-lg font-bold text-gray-950 dark:text-white">
                            {{ \Carbon\Carbon::parse($this->slot->time)->format('h:i A') }}
                        </div>
                    </div>

                    <div class="smn-widget-card rounded-lg border p-5">
                        <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Technician</div>
                        <div class="mt-3 text-lg font-bold text-gray-950 dark:text-white">
                            {{ ($this->slot->technician?->first_name . ' ' . $this->slot->technician?->last_name) ?: 'Not Assigned' }}
                        </div>
                    </div>
                </div>
            @else
                <div class="smn-empty-panel rounded-lg border border-dashed p-6 text-sm text-gray-600 dark:text-gray-300">
                    No appointment assigned for this request.
                </div>
            @endif
        </div>
    </x-filament::card>
</x-filament::widget>
