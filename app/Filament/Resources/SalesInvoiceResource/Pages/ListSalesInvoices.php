<?php

namespace App\Filament\Resources\SalesInvoiceResource\Pages;

use App\Filament\Resources\SalesInvoiceResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListSalesInvoices extends ListRecords
{
    protected static string $resource = SalesInvoiceResource::class;

    protected static string $view = 'filament.resources.sales-invoice-resource.pages.list-sales-invoices';

    public function getSalesSummary(): array
    {
        $query = $this->getFilteredTableQuery();

        $totalRevenue = (clone $query)->sum('total');
        $invoiceCount = (clone $query)->count();
        $paidInvoices = (clone $query)->where('status', 'completed')->count();
        $pendingInvoices = (clone $query)->where('status', 'pending')->count();
        $syncedInvoices = (clone $query)->where('invoices.sap_sync_status', 'success')->count();
        $failedSyncInvoices = (clone $query)->where('invoices.sap_sync_status', 'failed')->count();
        $averageInvoice = $invoiceCount > 0 ? $totalRevenue / $invoiceCount : 0;

        $paymentBreakdown = (clone $query)
            ->selectRaw("COALESCE(payment_method, 'unknown') as payment_method, COUNT(*) as invoices_count, SUM(total) as revenue")
            ->groupByRaw("COALESCE(payment_method, 'unknown')")
            ->orderByDesc('revenue')
            ->get();

        $typeBreakdown = (clone $query)
            ->join('maintenance_requests', 'maintenance_requests.id', '=', 'invoices.maintenance_request_id')
            ->selectRaw('maintenance_requests.type, COUNT(*) as invoices_count, SUM(invoices.total) as revenue')
            ->groupBy('maintenance_requests.type')
            ->orderByDesc('revenue')
            ->get();

        return [
            'totalRevenue' => $totalRevenue,
            'invoiceCount' => $invoiceCount,
            'paidInvoices' => $paidInvoices,
            'pendingInvoices' => $pendingInvoices,
            'syncedInvoices' => $syncedInvoices,
            'failedSyncInvoices' => $failedSyncInvoices,
            'averageInvoice' => $averageInvoice,
            'paymentBreakdown' => $paymentBreakdown,
            'typeBreakdown' => $typeBreakdown,
        ];
    }

    public function formatCurrency(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 2) . ' SAR';
    }

    public function formatMaintenanceType(?string $type): string
    {
        return SalesInvoiceResource::formatMaintenanceType($type);
    }
}
