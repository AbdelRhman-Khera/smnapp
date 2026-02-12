<?php

namespace App\Filament\Resources\MaintenanceRequestResource\Pages;

use App\Filament\Resources\MaintenanceRequestResource;
use App\Models\MaintenanceRequest;
use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\CreateRecord;

class CreateMaintenanceRequest extends CreateRecord
{
    protected static string $resource = MaintenanceRequestResource::class;

    /**
     * @var array<int, array{product_id:int|string|null, quantity:int|string|null}>
     */
    protected array $productsToSync = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default status to 'pending'
        $data['last_status'] = 'pending';

        // Products are stored on the pivot table (maintenance_request_product)
        $this->productsToSync = $data['products_items'] ?? [];
        unset($data['products_items']);


        return $data;
    }

    protected function afterCreate(): void
    {

        $maintenanceRequest = $this->getRecord();

        // Sync products with quantity on pivot
        if (!empty($this->productsToSync)) {
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
            }
        }


        $maintenanceRequest->statuses()->create([
            'status' => 'pending',
        ]);
    }




}
