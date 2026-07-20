<?php

namespace App\Filament\Resources\ProductHandoverResource\Pages;

use App\Filament\Resources\ProductHandoverResource;
use App\Models\ProductHandover;
use App\Models\ProductHandoverItem;
use App\Services\NotificationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateProductHandover extends CreateRecord
{
    protected static string $resource = ProductHandoverResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $maintenanceRequest = ProductHandoverResource::eligibleRequestsQuery()
            ->with('technician')
            ->find($data['maintenance_request_id'] ?? null);

        if (! $maintenanceRequest) {
            throw ValidationException::withMessages([
                'data.maintenance_request_id' => 'This request is no longer eligible for a product handover.',
            ]);
        }

        $serials = collect($data['items'] ?? [])
            ->pluck('serial_number')
            ->map(fn ($serial): string => trim((string) $serial))
            ->filter();

        if ($serials->isEmpty()) {
            throw ValidationException::withMessages([
                'data.items' => 'At least one serial number is required.',
            ]);
        }

        $alreadyUsed = ProductHandoverItem::query()
            ->whereIn('serial_number', $serials)
            ->whereHas('handover', fn ($query) => $query->whereIn('status', ['pending', 'accepted']))
            ->pluck('serial_number');

        if ($alreadyUsed->isNotEmpty()) {
            throw ValidationException::withMessages([
                'data.items' => 'These serial numbers are already used in another active handover: ' . $alreadyUsed->implode(', '),
            ]);
        }

        $data['technician_id'] = $maintenanceRequest->technician_id;
        $data['status'] = 'pending';
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): ProductHandover {
            $handover = ProductHandover::create([
                'maintenance_request_id' => $data['maintenance_request_id'],
                'technician_id' => $data['technician_id'],
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'],
            ]);

            foreach ($data['items'] as $item) {
                $handover->items()->create([
                    'product_id' => $item['product_id'],
                    'serial_number' => trim((string) $item['serial_number']),
                ]);
            }

            return $handover;
        });
    }

    protected function afterCreate(): void
    {
        NotificationService::notifyTechnicianTranslated(
            $this->record->technician_id,
            'notifications.technician.product_handover_created',
            ['id' => $this->record->maintenance_request_id],
            $this->record->maintenance_request_id
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
