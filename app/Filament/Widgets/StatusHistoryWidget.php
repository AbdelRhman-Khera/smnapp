<?php

namespace App\Filament\Widgets;

use App\Models\RequestStatus;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;

class StatusHistoryWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public ?int $recordId = null;

    public function mount($record)
    {
        $this->recordId = $record->id;
    }

    protected function getTableQuery(): Builder
    {
        return RequestStatus::query()
            ->where('maintenance_request_id', $this->recordId)
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('status')->label('Status')->sortable(),
            Tables\Columns\TextColumn::make('notes')->label('Notes')->limit(50),
            Tables\Columns\TextColumn::make('created_at')->label('Updated At')->dateTime()->sortable(),

            // Tables\Columns\TextColumn::make('location')
            //     ->label('Location')
            //     ->formatStateUsing(
            //         fn(RequestStatus $record) => (!empty($record->latitude) && !empty($record->longitude))
            //             ? '<a href="https://www.google.com/maps?q=' . trim($record->latitude) . ',' . trim($record->longitude) . '"
            //     target="_blank" class="font-semibold text-blue-600 underline">View on Map</a>'
            //             : '<span class="text-gray-500">No Location</span>'
            //     )
            //     ->html(),
        ];
    }
}
