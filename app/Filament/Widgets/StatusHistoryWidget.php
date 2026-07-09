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

    public static function canView(): bool
    {
        // dd(request()->routeIs('filament.admin.resources.maintenance-requests.view'), request()->route()->getName());
        return request()->routeIs('filament.admin.resources.maintenance-requests.view');
    }
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
            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (?string $state): string => match ($state) {
                    'pending' => 'gray',
                    'visit_payment_pending' => 'warning',
                    'service_paid' => 'success',
                    'technician_assigned' => 'info',
                    'technician_on_the_way' => 'warning',
                    'technician_arrived' => 'primary',
                    'in_progress' => 'warning',
                    'waiting_for_payment' => 'danger',
                    'waiting_for_technician_confirm_payment' => 'danger',
                    'completed' => 'success',
                    'canceled' => 'danger',
                    default => 'gray',
                })
                ->formatStateUsing(fn (?string $state): string => $state
                    ? str($state)->replace('_', ' ')->title()->toString()
                    : '-')
                ->sortable(),
            Tables\Columns\TextColumn::make('notes')
                ->label('Notes')
                ->wrap()
                ->placeholder('No notes')
                ->extraCellAttributes([
                    'class' => 'max-w-xl whitespace-normal break-words',
                ]),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Updated At')
                ->dateTime('Y-m-d h:i A')
                ->sortable(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('view_location')
                ->label('Location')
                ->icon('heroicon-o-map-pin')
                ->color('info')
                ->modalHeading('Status Location')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalWidth('3xl')
                ->visible(fn (RequestStatus $record): bool => filled($record->latitude) && filled($record->longitude))
                ->modalContent(fn (RequestStatus $record) => view('filament.widgets.status-location-modal', [
                    'latitude' => trim((string) $record->latitude),
                    'longitude' => trim((string) $record->longitude),
                ])),
        ];
    }
}
