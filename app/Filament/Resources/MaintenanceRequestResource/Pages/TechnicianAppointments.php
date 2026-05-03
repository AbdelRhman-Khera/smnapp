<?php

namespace App\Filament\Resources\MaintenanceRequestResource\Pages;

use App\Filament\Resources\MaintenanceRequestResource;
use App\Filament\Resources\TechnicianResource;
use App\Models\MaintenanceRequest;
use App\Models\Technician;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class TechnicianAppointments extends ListRecords
{
    protected static string $resource = MaintenanceRequestResource::class;

    public ?Technician $technician = null;

    public function mount(): void
    {
        parent::mount();

        $routeTechnician = request()->route('technician');

        $this->technician = $routeTechnician instanceof Technician
            ? $routeTechnician
            : Technician::query()->findOrFail($routeTechnician);
    }

    public function getTitle(): string
    {
        $name = trim(($this->technician?->first_name ?? '') . ' ' . ($this->technician?->last_name ?? ''));

        return 'Appointments - ' . ($name ?: 'Technician #' . $this->technician?->id);
    }

    public function getSubheading(): ?string
    {
        return 'Scheduled maintenance requests for this technician.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back_to_technicians')
                ->label('Back to Technicians')
                ->icon('heroicon-o-arrow-left')
                ->url(TechnicianResource::getUrl('index')),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return MaintenanceRequest::query()
            ->with([
                'customer:id,first_name,last_name,phone',
                'slot:id,technician_id,date,time,is_booked',
                'technician:id,first_name,last_name',
                'address:id,city_id,district_id',
            ])
            ->where('technician_id', $this->technician->id)
            ->whereNotNull('slot_id');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('id')->sortable(),

                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->state(fn ($record) => $record->customer
                        ? $record->customer->first_name . ' ' . $record->customer->last_name
                        : '-'),

                TextColumn::make('customer.phone')->label('Phone'),

                TextColumn::make('address->city->name_ar')->label('city'),
                TextColumn::make('address->district->name_ar')->label('District'),

                TextColumn::make('slot.date')
                    ->label('Appointment Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('slot.time')
                    ->label('Appointment Time')
                    ->sortable(),

                TextColumn::make('last_status')
                    ->label('Status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('last_status')
                    ->options([
                        'pending' => 'Pending',
                        'technician_assigned' => 'Technician Assigned',
                        'technician_on_the_way' => 'Technician On The Way',
                        'technician_arrived' => 'Technician Arrived',
                        'in_progress' => 'In Progress',
                        'waiting_for_payment' => 'Waiting For Payment',
                        'waiting_for_technician_confirm_payment' => 'Waiting For Technician Confirm Payment',
                        'completed' => 'Completed',
                        'canceled' => 'Canceled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),

            'today' => Tab::make('Today')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->whereHas('slot', fn (Builder $q) => $q->whereDate('date', today()))
                ),

            'upcoming' => Tab::make('Upcoming')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->whereHas('slot', fn (Builder $q) => $q->whereDate('date', '>', today()))
                ),

            'past' => Tab::make('Past')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->whereHas('slot', fn (Builder $q) => $q->whereDate('date', '<', today()))
                ),

            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('last_status', 'completed')
                ),

            'canceled' => Tab::make('Canceled')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('last_status', 'canceled')
                ),
        ];
    }
}
