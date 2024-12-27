<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['otp'] = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        return $data;
    }
}
