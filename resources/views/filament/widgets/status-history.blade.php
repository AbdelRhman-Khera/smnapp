<x-filament::widget>
    <x-filament::card>
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-200 bg-gray-50 px-5 py-4 dark:border-gray-700 dark:bg-gray-800/70">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-950 dark:text-white">Status History</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Full request timeline with complete notes.</p>
                    </div>
                </div>
            </div>

            <div class="p-5">
                {{ $this->table }}
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>
