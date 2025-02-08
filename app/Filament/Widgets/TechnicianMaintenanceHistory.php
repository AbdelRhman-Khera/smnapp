<?php

namespace App\Filament\Widgets;

use App\Models\MaintenanceRequest;
use Filament\Widgets\TableWidget as Widget;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class TechnicianMaintenanceHistory extends Widget
{
    protected int|string|array $columnSpan = 'full';


    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.resources.technicians.view');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MaintenanceRequest::query()
                    ->with(['slot', 'customer', 'feedback'])
                    ->where('technician_id', request()->route('record'))
                    ->latest()
            )
            ->columns([
                TextColumn::make('customer.first_name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->sortable(),

                TextColumn::make('last_status')
                    ->label('Status')
                    ->sortable(),

                TextColumn::make('slot.date')
                    ->label('Slot Date')
                    ->formatStateUsing(fn($record) => $record->slot?->date ?? 'N/A') // ✅ Fix slot date
                    ->sortable(),

                TextColumn::make('slot.time')
                    ->label('Slot Time')
                    ->formatStateUsing(fn($record) => $record->slot?->time ?? 'N/A') // ✅ Fix slot time
                    ->sortable(),

                TextColumn::make('feedback.rating')
                    ->label('Rating')
                    ->formatStateUsing(fn($record) => $record->feedback?->rating ?? 'N/A') // ✅ Fix rating display
                    ->sortable(),
            ])
            ->defaultSort('slot.date', 'desc');
    }
}
