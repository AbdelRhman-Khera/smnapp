<?php

namespace App\Filament\Resources\SalesInvoiceResource\Pages;

use App\Filament\Resources\SalesInvoiceResource;
use App\Http\Controllers\SapController;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\SparePart;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditSalesInvoice extends EditRecord
{
    protected static string $resource = SalesInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('admin.sales-invoices.print', $this->record))
                ->openUrlInNewTab(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Invoice $invoice */
        $invoice = $this->record->loadMissing(['services', 'spareParts']);

        $data['services_items'] = $invoice->services
            ->map(fn (Service $service): array => [
                'service_id' => $service->id,
            ])
            ->values()
            ->all();

        $data['spare_parts_items'] = $invoice->spareParts
            ->map(fn (SparePart $part): array => [
                'spare_part_id' => $part->id,
                'quantity' => (int) ($part->pivot->quantity ?? 1),
                'price' => (float) ($part->pivot->price ?? $part->price ?? 0),
            ])
            ->values()
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Invoice $record */
        $note = trim((string) ($this->form->getRawState()['edit_note'] ?? ''));

        $before = $this->snapshot($record->loadMissing(['services', 'spareParts']));
        $total = $this->calculateTotal($data);
        $user = auth()->user();

        DB::transaction(function () use ($record, $data, $note, $total, $user, $before): void {
            $record->services()->sync(
                collect($data['services_items'] ?? [])
                    ->pluck('service_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all()
            );

            $record->spareParts()->sync(
                collect($data['spare_parts_items'] ?? [])
                    ->filter(fn (array $item): bool => filled($item['spare_part_id'] ?? null))
                    ->mapWithKeys(fn (array $item): array => [
                        $item['spare_part_id'] => [
                            'quantity' => (int) ($item['quantity'] ?? 1),
                            'price' => (float) ($item['price'] ?? 0),
                        ],
                    ])
                    ->all()
            );

            $record->refresh()->load(['services', 'spareParts']);

            $record->update([
                'payment_method' => $data['payment_method'] ?? null,
                'total' => $total,
            ]);

            $record->refresh()->load(['services', 'spareParts']);

            $record->update([
                'notes' => collect($record->notes ?? [])
                    ->push([
                        'type' => 'invoice_updated',
                        'user_id' => $user?->id,
                        'user_name' => $user?->name ?? 'System',
                        'note' => $note,
                        'before' => $before,
                        'after' => $this->snapshot($record),
                        'created_at' => now()->toDateTimeString(),
                    ])
                    ->values()
                    ->all(),
            ]);
        });

        $record->refresh()->load([
            'maintenanceRequest.invoice',
            'maintenanceRequest.invoice.services',
            'maintenanceRequest.invoice.spareParts',
            'maintenanceRequest.customer',
            'maintenanceRequest.technician',
            'maintenanceRequest.address.city',
            'maintenanceRequest.address.district',
        ]);

        $sapResult = app(SapController::class)->createSalesOrder(
            $record->maintenanceRequest,
            $this->formatPaymentMethodForSap((string) $record->payment_method),
        );

        $success = (bool) ($sapResult['success'] ?? false);

        Notification::make()
            ->title($success ? 'Invoice updated and resent to SAP' : 'Invoice updated, but SAP resend failed')
            ->body($sapResult['sap_desc'] ?? $sapResult['message'] ?? null)
            ->color($success ? 'success' : 'warning')
            ->send();

        return $record;
    }

    private function calculateTotal(array $data): float
    {
        $servicesTotal = Service::query()
            ->whereIn(
                'id',
                collect($data['services_items'] ?? [])
                    ->pluck('service_id')
                    ->filter()
                    ->unique()
                    ->all()
            )
            ->sum('price');

        $sparePartsTotal = collect($data['spare_parts_items'] ?? [])
            ->sum(fn (array $item): float => ((float) ($item['price'] ?? 0)) * ((int) ($item['quantity'] ?? 1)));

        return (float) $servicesTotal + (float) $sparePartsTotal;
    }

    private function snapshot(Invoice $invoice): array
    {
        return [
            'payment_method' => $invoice->payment_method,
            'total' => (float) $invoice->total,
            'services' => $invoice->services
                ->map(fn (Service $service): array => [
                    'id' => $service->id,
                    'name' => $service->name_en ?? $service->name_ar,
                    'price' => (float) ($service->price ?? 0),
                ])
                ->values()
                ->all(),
            'spare_parts' => $invoice->spareParts
                ->map(fn (SparePart $part): array => [
                    'id' => $part->id,
                    'name' => $part->name_en ?? $part->name_ar,
                    'quantity' => (int) ($part->pivot->quantity ?? 1),
                    'price' => (float) ($part->pivot->price ?? $part->price ?? 0),
                ])
                ->values()
                ->all(),
        ];
    }

    private function formatPaymentMethodForSap(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'online' => 'Online',
            'machine' => 'Machine',
            'cash' => 'Cash',
            'remittance' => 'Remittance',
            default => ucfirst($paymentMethod),
        };
    }
}
