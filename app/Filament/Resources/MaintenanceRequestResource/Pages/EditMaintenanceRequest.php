<?php

namespace App\Filament\Resources\MaintenanceRequestResource\Pages;

use App\Filament\Resources\MaintenanceRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaintenanceRequest extends EditRecord
{
    protected static string $resource = MaintenanceRequestResource::class;

    /**
     * @var array<int, array{product_id:int|string|null, quantity:int|string|null}>
     */
    protected array $productsToSync = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Products are stored on the pivot table (maintenance_request_product)
        $this->productsToSync = $data['products'] ?? [];
        unset($data['products']);

        return $data;
    }

    protected function afterSave(): void
    {
        $maintenanceRequest = $this->getRecord();

        // Sync products with quantity on pivot
        $sync = [];
        foreach ($this->productsToSync as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 1);

            if ($productId > 0) {
                $sync[$productId] = ['quantity' => max(1, $quantity)];
            }
        }

        if (!empty($sync)) {
            $maintenanceRequest->products()->sync($sync);
        } else {
            // If user removed all products, detach.
            $maintenanceRequest->products()->detach();
        }
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('products');

        $data['products_items'] = $this->record->products
            ->map(fn($p) => [
                'product_id' => $p->id,
                'quantity'   => (int) ($p->pivot->quantity ?? 1),
            ])
            ->values()
            ->toArray();

        return $data;
    }

    protected function handleRecordUpdate($record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $items = $data['products_items'] ?? [];
        unset($data['products_items']); // عشان مش column في الجدول

        $record->update($data);

        $sync = [];
        foreach ($items as $item) {
            $pid = (int) ($item['product_id'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 1);

            if ($pid > 0) {
                $sync[$pid] = ['quantity' => max(1, $qty)];
            }
        }

        $record->products()->sync($sync);

        return $record;
    }


    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
