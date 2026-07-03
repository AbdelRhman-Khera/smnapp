<x-filament::widget>
    <style>
        .smn-status-panel {
            background: #ffffff;
            border-color: #e5e7eb;
        }

        .smn-status-header {
            background: #f9fafb;
            border-color: #e5e7eb;
        }

        .dark .smn-status-panel {
            background: #111827;
            border-color: #374151;
        }

        .dark .smn-status-header {
            background: rgba(31, 41, 55, 0.82);
            border-color: #374151;
        }
    </style>

    <x-filament::card>
        <div class="smn-status-panel overflow-hidden rounded-xl border shadow-sm">
            <div class="smn-status-header border-b px-6 py-5 sm:px-8 sm:py-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="min-w-0">
                        <h3 class="text-xl font-bold text-gray-950 dark:text-white">Status History</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Full request timeline with complete notes.</p>
                    </div>
                </div>
            </div>

            <div class="p-6 sm:p-8">
                {{ $this->table }}
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>
