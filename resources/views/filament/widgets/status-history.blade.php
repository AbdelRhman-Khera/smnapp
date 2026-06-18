<x-filament::widget>
    <x-filament::card>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="mb-4">
                <h3 class="text-lg font-bold text-gray-950 dark:text-white">Status History</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Request timeline and technician/customer updates.</p>
            </div>

            {{ $this->table }}
        </div>
    </x-filament::card>
</x-filament::widget>
