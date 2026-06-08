<x-filament-panels::page
    @class([
        'fi-resource-list-records-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
    ])
>
    @php($summary = $this->getSalesSummary())

    <div class="flex flex-col gap-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Sales</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                    {{ $this->formatCurrency($summary['totalRevenue']) }}
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Invoices</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                    {{ number_format($summary['invoiceCount']) }}
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Paid / Pending</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                    {{ number_format($summary['paidInvoices']) }} / {{ number_format($summary['pendingInvoices']) }}
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Average Invoice</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                    {{ $this->formatCurrency($summary['averageInvoice']) }}
                </div>
            </x-filament::section>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">SAP Sync</div>
                <div class="mt-3 grid grid-cols-2 gap-3">
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Success</div>
                        <div class="text-lg font-semibold text-success-600">{{ number_format($summary['syncedInvoices']) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Failed</div>
                        <div class="text-lg font-semibold text-danger-600">{{ number_format($summary['failedSyncInvoices']) }}</div>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Sales By Payment Type</div>
                <div class="mt-3 space-y-2">
                    @forelse ($summary['paymentBreakdown'] as $row)
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="capitalize text-gray-700 dark:text-gray-200">{{ $row->payment_method }}</span>
                            <span class="font-medium text-gray-950 dark:text-white">
                                {{ number_format($row->invoices_count) }} | {{ $this->formatCurrency($row->revenue) }}
                            </span>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500 dark:text-gray-400">No invoices found.</div>
                    @endforelse
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Sales By Maintenance Type</div>
                <div class="mt-3 space-y-2">
                    @forelse ($summary['typeBreakdown'] as $row)
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="text-gray-700 dark:text-gray-200">{{ $this->formatMaintenanceType($row->type) }}</span>
                            <span class="font-medium text-gray-950 dark:text-white">
                                {{ number_format($row->invoices_count) }} | {{ $this->formatCurrency($row->revenue) }}
                            </span>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500 dark:text-gray-400">No invoices found.</div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <x-filament-panels::resources.tabs />

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE, scopes: $this->getRenderHookScopes()) }}

        {{ $this->table }}

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER, scopes: $this->getRenderHookScopes()) }}
    </div>
</x-filament-panels::page>
