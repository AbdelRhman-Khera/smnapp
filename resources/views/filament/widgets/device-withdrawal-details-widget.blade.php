<x-filament::widget>
    @php
        $withdrawalRequests = $this->withdrawalRequests ?? collect();
        $sourceWithdrawal = $this->workshopWithdrawalSource;
    @endphp

    @if ($withdrawalRequests->isNotEmpty() || $sourceWithdrawal)
        <style>
            .smn-collapse > summary::-webkit-details-marker {
                display: none;
            }

            .smn-collapse[open] .smn-collapse-chevron {
                transform: rotate(180deg);
            }

            .smn-widget-body {
                background: rgba(249, 250, 251, 0.72);
            }

            .smn-outer-panel,
            .smn-widget-summary,
            .smn-panel-card,
            .smn-section-header,
            .smn-muted-card,
            .smn-table-panel,
            .smn-chevron-button,
            .smn-secondary-button {
                background: #ffffff;
                border-color: #e5e7eb;
            }

            .smn-muted-card {
                background: #f9fafb;
            }

            .smn-widget-summary {
                background: rgba(239, 246, 255, 0.78);
                border-color: rgba(37, 99, 235, 0.18);
            }

            .smn-primary-card {
                background: rgba(239, 246, 255, 0.82);
                border-color: rgba(37, 99, 235, 0.18);
            }

            .dark .smn-widget-body {
                background: rgba(3, 7, 18, 0.34);
            }

            .dark .smn-outer-panel,
            .dark .smn-widget-summary,
            .dark .smn-panel-card,
            .dark .smn-section-header,
            .dark .smn-table-panel {
                background: #111827;
                border-color: #374151;
            }

            .dark .smn-widget-summary {
                background: rgba(37, 99, 235, 0.12);
                border-color: rgba(96, 165, 250, 0.22);
            }

            .smn-table-panel thead {
                background: #f3f4f6;
                color: #4b5563;
            }

            .smn-table-panel tbody tr {
                background: #ffffff;
            }

            .smn-secondary-button:hover {
                background: #f9fafb;
            }

            .dark .smn-table-panel thead {
                background: #1f2937;
                color: #d1d5db;
            }

            .dark .smn-table-panel tbody tr {
                background: #111827;
            }

            .dark .smn-chevron-button {
                background: #111827;
                border-color: rgba(96, 165, 250, 0.3);
                color: #93c5fd;
            }

            .dark .smn-secondary-button {
                background: #111827;
                border-color: #4b5563;
                color: #f9fafb;
            }

            .dark .smn-secondary-button:hover {
                background: #1f2937;
            }

            .dark .smn-muted-card {
                background: rgba(31, 41, 55, 0.82);
                border-color: #374151;
            }

            .dark .smn-primary-card {
                background: rgba(37, 99, 235, 0.14);
                border-color: rgba(96, 165, 250, 0.24);
            }
        </style>

        <x-filament::card>
            <details class="smn-collapse smn-outer-panel overflow-hidden rounded-xl border shadow-sm" open>
                <summary class="smn-widget-summary flex cursor-pointer list-none flex-wrap items-center justify-between gap-6 border-b px-6 py-5 transition sm:px-8 sm:py-6">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-primary-600 dark:bg-primary-400"></span>
                            <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">Device Withdrawal Details</h2>
                        </div>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            Original withdrawal, follow-up request, workshop branch, and withdrawn devices.
                        </p>
                    </div>

                    <div class="smn-chevron-button flex h-9 w-9 items-center justify-center rounded-full border text-primary-700 shadow-sm transition">
                        <svg class="smn-collapse-chevron h-5 w-5 transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </summary>

                <div class="smn-widget-body space-y-6 p-6 sm:p-8">
                    @if ($sourceWithdrawal)
                        <section class="smn-panel-card overflow-hidden rounded-xl border border-primary-200 shadow-sm dark:border-primary-500/25">
                            <div class="smn-section-header flex flex-wrap items-start justify-between gap-6 border-b px-6 py-5 sm:px-7">
                                <div class="min-w-0">
                                    <div class="text-xs font-semibold uppercase text-primary-700 dark:text-primary-300">This request is a follow-up</div>
                                    <h3 class="mt-2 text-lg font-bold text-gray-950 dark:text-white">
                                        Source Withdrawal #{{ $sourceWithdrawal->id }}
                                    </h3>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <a
                                        href="{{ \App\Filament\Resources\DeviceWithdrawalRequestResource::getUrl('view', ['record' => $sourceWithdrawal->id]) }}"
                                        class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500"
                                    >
                                        Open Withdrawal
                                    </a>
                                    @if ($sourceWithdrawal->maintenance_request_id)
                                        <a
                                            href="{{ \App\Filament\Resources\MaintenanceRequestResource::getUrl('view', ['record' => $sourceWithdrawal->maintenance_request_id]) }}"
                                            class="smn-secondary-button inline-flex items-center rounded-lg border px-4 py-2.5 text-sm font-semibold transition"
                                        >
                                            Original Request #{{ $sourceWithdrawal->maintenance_request_id }}
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div class="grid gap-4 p-6 md:grid-cols-4 sm:p-7">
                                <div class="smn-muted-card rounded-lg border p-5">
                                    <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Status</div>
                                    <div class="mt-1 text-base font-bold text-gray-950 dark:text-white">
                                        {{ \App\Models\DeviceWithdrawalRequest::statuses()[$sourceWithdrawal->status] ?? $sourceWithdrawal->status }}
                                    </div>
                                </div>
                                <div class="smn-muted-card rounded-lg border p-5">
                                    <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Branch</div>
                                    <div class="mt-1 text-base font-bold text-gray-950 dark:text-white">{{ $sourceWithdrawal->branch?->name_en ?: '-' }}</div>
                                </div>
                                <div class="smn-muted-card rounded-lg border p-5">
                                    <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Technician</div>
                                    <div class="mt-1 text-base font-bold text-gray-950 dark:text-white">
                                        {{ trim(($sourceWithdrawal->technician?->first_name ?? '') . ' ' . ($sourceWithdrawal->technician?->last_name ?? '')) ?: '-' }}
                                    </div>
                                </div>
                                <div class="smn-muted-card rounded-lg border p-5">
                                    <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Devices</div>
                                    <div class="mt-1 text-base font-bold text-gray-950 dark:text-white">{{ $sourceWithdrawal->items->count() }}</div>
                                </div>
                            </div>
                        </section>
                    @endif

                    @if ($withdrawalRequests->isNotEmpty())
                        <div class="grid gap-5">
                            @foreach ($withdrawalRequests as $withdrawal)
                                <section class="smn-panel-card overflow-hidden rounded-xl border shadow-sm">
                                    <div class="smn-section-header flex flex-wrap items-start justify-between gap-6 border-b px-6 py-5 sm:px-7">
                                        <div class="min-w-0">
                                            <div class="text-xs font-semibold uppercase text-primary-700 dark:text-primary-300">Withdrawal Request</div>
                                            <h3 class="mt-2 text-xl font-bold text-gray-950 dark:text-white">Withdrawal #{{ $withdrawal->id }}</h3>
                                        </div>

                                        <div class="flex flex-wrap gap-3">
                                            <a
                                                href="{{ \App\Filament\Resources\DeviceWithdrawalRequestResource::getUrl('view', ['record' => $withdrawal->id]) }}"
                                                class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500"
                                            >
                                                Open Withdrawal
                                            </a>
                                            @if ($withdrawal->follow_up_maintenance_request_id)
                                                <a
                                                    href="{{ \App\Filament\Resources\MaintenanceRequestResource::getUrl('view', ['record' => $withdrawal->follow_up_maintenance_request_id]) }}"
                                                    class="smn-secondary-button inline-flex items-center rounded-lg border px-4 py-2.5 text-sm font-semibold transition"
                                                >
                                                    Follow-up #{{ $withdrawal->follow_up_maintenance_request_id }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="space-y-6 p-6 sm:p-7">
                                        <div class="grid gap-4 md:grid-cols-5">
                                            <div class="smn-primary-card rounded-lg border p-5">
                                                <div class="text-xs font-semibold uppercase text-primary-700 dark:text-primary-300">Status</div>
                                                <div class="mt-1 text-base font-bold text-gray-950 dark:text-white">
                                                    {{ \App\Models\DeviceWithdrawalRequest::statuses()[$withdrawal->status] ?? $withdrawal->status }}
                                                </div>
                                            </div>
                                            <div class="smn-muted-card rounded-lg border p-5">
                                                <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Branch</div>
                                                <div class="mt-1 text-base font-bold text-gray-950 dark:text-white">{{ $withdrawal->branch?->name_en ?: '-' }}</div>
                                            </div>
                                            <div class="smn-muted-card rounded-lg border p-5">
                                                <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Technician</div>
                                                <div class="mt-1 text-base font-bold text-gray-950 dark:text-white">
                                                    {{ trim(($withdrawal->technician?->first_name ?? '') . ' ' . ($withdrawal->technician?->last_name ?? '')) ?: '-' }}
                                                </div>
                                            </div>
                                            <div class="smn-muted-card rounded-lg border p-5">
                                                <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Delivery Tech</div>
                                                <div class="mt-1 text-base font-bold text-gray-950 dark:text-white">
                                                    {{ trim(($withdrawal->handoffTechnician?->first_name ?? '') . ' ' . ($withdrawal->handoffTechnician?->last_name ?? '')) ?: '-' }}
                                                </div>
                                            </div>
                                            <div class="smn-muted-card rounded-lg border p-5">
                                                <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Devices</div>
                                                <div class="mt-1 text-base font-bold text-gray-950 dark:text-white">{{ $withdrawal->items->count() }}</div>
                                            </div>
                                        </div>

                                        <div class="smn-table-panel overflow-hidden rounded-lg border">
                                            <table class="w-full text-sm">
                                                <thead class="text-xs uppercase">
                                                    <tr>
                                                        <th class="px-5 py-3 text-start font-semibold">Product</th>
                                                        <th class="px-5 py-3 text-start font-semibold">Serial</th>
                                                        <th class="px-5 py-3 text-start font-semibold">Status</th>
                                                        <th class="px-5 py-3 text-start font-semibold">Notes</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                                    @foreach ($withdrawal->items as $item)
                                                        <tr>
                                                            <td class="px-5 py-4 font-semibold text-gray-950 dark:text-white">{{ $item->product?->name ?: '-' }}</td>
                                                            <td class="px-5 py-4 text-gray-700 dark:text-gray-300">{{ $item->serial_number ?: '-' }}</td>
                                                            <td class="px-5 py-4 text-gray-700 dark:text-gray-300">{{ $item->status ?: '-' }}</td>
                                                            <td class="px-5 py-4 text-gray-700 dark:text-gray-300">{{ $item->notes ?: '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    @endif
                </div>
            </details>
        </x-filament::card>
    @endif
</x-filament::widget>
