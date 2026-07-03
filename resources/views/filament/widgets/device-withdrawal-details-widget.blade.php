<x-filament::widget>
    @php
        $withdrawalRequests = $this->withdrawalRequests ?? collect();
        $sourceWithdrawal = $this->workshopWithdrawalSource;
    @endphp

    @if ($withdrawalRequests->isNotEmpty() || $sourceWithdrawal)
        <x-filament::card>
            <div class="space-y-5">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">Device Withdrawal Details</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Original and follow-up withdrawal links for this maintenance request.
                    </p>
                </div>

                @if ($sourceWithdrawal)
                    <section class="rounded-xl border border-primary-200 bg-primary-50 p-5 dark:border-primary-500/30 dark:bg-primary-500/10">
                        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase text-primary-700 dark:text-primary-300">This request is a follow-up</div>
                                <h3 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">
                                    Source Withdrawal #{{ $sourceWithdrawal->id }}
                                </h3>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <a
                                    href="{{ \App\Filament\Resources\DeviceWithdrawalRequestResource::getUrl('view', ['record' => $sourceWithdrawal->id]) }}"
                                    class="rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white hover:bg-primary-500"
                                >
                                    Open Withdrawal
                                </a>
                                @if ($sourceWithdrawal->maintenance_request_id)
                                    <a
                                        href="{{ \App\Filament\Resources\MaintenanceRequestResource::getUrl('view', ['record' => $sourceWithdrawal->maintenance_request_id]) }}"
                                        class="rounded-lg border border-primary-300 px-3 py-2 text-sm font-semibold text-primary-700 hover:bg-primary-100 dark:border-primary-500/40 dark:text-primary-300 dark:hover:bg-primary-500/20"
                                    >
                                        Original Request #{{ $sourceWithdrawal->maintenance_request_id }}
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="grid gap-3 md:grid-cols-4">
                            <div class="rounded-lg bg-white p-3 dark:bg-gray-900">
                                <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Status</div>
                                <div class="mt-1 font-semibold text-gray-950 dark:text-white">
                                    {{ \App\Models\DeviceWithdrawalRequest::statuses()[$sourceWithdrawal->status] ?? $sourceWithdrawal->status }}
                                </div>
                            </div>
                            <div class="rounded-lg bg-white p-3 dark:bg-gray-900">
                                <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Branch</div>
                                <div class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $sourceWithdrawal->branch?->name_en ?: '-' }}</div>
                            </div>
                            <div class="rounded-lg bg-white p-3 dark:bg-gray-900">
                                <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Technician</div>
                                <div class="mt-1 font-semibold text-gray-950 dark:text-white">
                                    {{ trim(($sourceWithdrawal->technician?->first_name ?? '') . ' ' . ($sourceWithdrawal->technician?->last_name ?? '')) ?: '-' }}
                                </div>
                            </div>
                            <div class="rounded-lg bg-white p-3 dark:bg-gray-900">
                                <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Devices</div>
                                <div class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $sourceWithdrawal->items->count() }}</div>
                            </div>
                        </div>
                    </section>
                @endif

                @if ($withdrawalRequests->isNotEmpty())
                    <div class="grid gap-4">
                        @foreach ($withdrawalRequests as $withdrawal)
                            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Withdrawal Request</div>
                                        <h3 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">Withdrawal #{{ $withdrawal->id }}</h3>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <a
                                            href="{{ \App\Filament\Resources\DeviceWithdrawalRequestResource::getUrl('view', ['record' => $withdrawal->id]) }}"
                                            class="rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white hover:bg-primary-500"
                                        >
                                            Open Withdrawal
                                        </a>
                                        @if ($withdrawal->follow_up_maintenance_request_id)
                                            <a
                                                href="{{ \App\Filament\Resources\MaintenanceRequestResource::getUrl('view', ['record' => $withdrawal->follow_up_maintenance_request_id]) }}"
                                                class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                            >
                                                Follow-up #{{ $withdrawal->follow_up_maintenance_request_id }}
                                            </a>
                                        @endif
                                    </div>
                                </div>

                                <div class="grid gap-3 md:grid-cols-5">
                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/70">
                                        <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Status</div>
                                        <div class="mt-1 font-semibold text-gray-950 dark:text-white">
                                            {{ \App\Models\DeviceWithdrawalRequest::statuses()[$withdrawal->status] ?? $withdrawal->status }}
                                        </div>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/70">
                                        <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Branch</div>
                                        <div class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $withdrawal->branch?->name_en ?: '-' }}</div>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/70">
                                        <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Technician</div>
                                        <div class="mt-1 font-semibold text-gray-950 dark:text-white">
                                            {{ trim(($withdrawal->technician?->first_name ?? '') . ' ' . ($withdrawal->technician?->last_name ?? '')) ?: '-' }}
                                        </div>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/70">
                                        <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Delivery Tech</div>
                                        <div class="mt-1 font-semibold text-gray-950 dark:text-white">
                                            {{ trim(($withdrawal->handoffTechnician?->first_name ?? '') . ' ' . ($withdrawal->handoffTechnician?->last_name ?? '')) ?: '-' }}
                                        </div>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/70">
                                        <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Devices</div>
                                        <div class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $withdrawal->items->count() }}</div>
                                    </div>
                                </div>

                                <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                                    <table class="w-full text-sm">
                                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                            <tr>
                                                <th class="px-4 py-3 text-start">Product</th>
                                                <th class="px-4 py-3 text-start">Serial</th>
                                                <th class="px-4 py-3 text-start">Status</th>
                                                <th class="px-4 py-3 text-start">Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                            @foreach ($withdrawal->items as $item)
                                                <tr>
                                                    <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $item->product?->name ?: '-' }}</td>
                                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item->serial_number ?: '-' }}</td>
                                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item->status ?: '-' }}</td>
                                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item->notes ?: '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-filament::card>
    @endif
</x-filament::widget>
