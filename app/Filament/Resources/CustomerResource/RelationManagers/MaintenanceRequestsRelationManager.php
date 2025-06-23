<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Http\Controllers\MaintenanceRequestController;
use App\Models\MaintenanceRequest;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class MaintenanceRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenance_requests';

    protected static ?string $title = 'Maintenance Requests';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('customer_id')
                    ->relationship('customer', 'phone')
                    ->required()
                    ->default(fn ($livewire) => $livewire->ownerRecord->id)
                    ->disabled(),
                Select::make('address_id')
                    ->label('Address')
                    ->options(
                        fn ($get, $livewire) =>
                        $get('customer_id')
                            ? \App\Models\Address::where('customer_id', $livewire->ownerRecord->id)->pluck('name', 'id')
                            : []
                    )
                    ->searchable()
                    ->reactive()
                    ->required()
                    ->disabled(fn ($get) => !$get('customer_id')),
                Select::make('type')
                    ->options([
                        'new_installation' => 'New Installation',
                        'regular_maintenance' => 'Regular Maintenance',
                        'emergency_maintenance' => 'Emergency Maintenance',
                    ])
                    ->required(),
                Select::make('products')
                    ->relationship('products', 'name_ar')
                    ->multiple()
                    ->preload()
                    ->required(),
                TextInput::make('sap_order_id')
                    ->rules([
                        fn ($get) => $get('type') === 'new_installation' ? 'required' : 'nullable',
                    ]),
                DatePicker::make('last_maintenance_date')
                    ->nullable(),
                Textarea::make('problem_description')
                    ->columnSpanFull(),
                FileUpload::make('photos')
                    ->multiple()
                    ->directory('maintenance_requests')
                    ->nullable()
                    ->columnSpanFull(),
                Repeater::make('notes')
                    ->label('Notes')
                    ->schema([
                        TextInput::make('user')
                            ->default(fn () => auth()->user()->name)
                            ->disabled(),
                        Textarea::make('note')
                            ->label('Note')
                            ->required(),
                        Select::make('flag_color')
                            ->label('Status Flag')
                            ->options([
                                'green' => 'Resolved',
                                'yellow' => 'Pending',
                                'red' => 'Critical',
                            ])
                            ->required(),
                    ])
                    ->default([])
                    ->disableItemDeletion()
                    ->disableItemMovement()
                    ->addable(true)
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('customer_full_name')
                    ->label('Customer')
                    ->getStateUsing(function ($record) {
                        return $record->customer ? ($record->customer->first_name . ' ' . $record->customer->last_name) : '';
                    })
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->whereHas('customer', function (Builder $customerQuery) use ($search) {
                            $customerQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('customer.phone')
                    ->label('Phone')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->sortable()
                    ->searchable()
                    ->label('Type')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'new_installation' => 'New Installation',
                            'regular_maintenance' => 'Regular Maintenance',
                            'emergency_maintenance' => 'Emergency Maintenance',
                            default => $state,
                        };
                    }),
                TextColumn::make('last_status')
                    ->sortable()
                    ->searchable()
                    ->label('Status')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'pending' => 'Pending',
                            'technician_assigned' => 'Technician Assigned',
                            'technician_on_the_way' => 'Technician On The Way',
                            'technician_arrived' => 'Technician Arrived',
                            'in_progress' => 'In Progress',
                            'waiting_for_payment' => 'Waiting For Payment',
                            'waiting_for_technician_confirm_payment' => 'Waiting For Technician Confirm Payment',
                            'completed' => 'Completed',
                            'canceled' => 'Canceled',
                            default => $state,
                        };
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('new_installation')
                    ->label('New Installation')
                    ->query(fn (Builder $query) => $query->where('type', 'new_installation')),
                Tables\Filters\Filter::make('regular_maintenance')
                    ->label('Regular Maintenance')
                    ->query(fn (Builder $query) => $query->where('type', 'regular_maintenance')),
                Tables\Filters\Filter::make('emergency_maintenance')
                    ->label('Emergency Maintenance')
                    ->query(fn (Builder $query) => $query->where('type', 'emergency_maintenance')),
                Tables\Filters\Filter::make('pending')
                    ->label('Pending')
                    ->query(fn (Builder $query) => $query->where('last_status', 'pending')),
                Tables\Filters\Filter::make('technician_assigned')
                    ->label('Technician Assigned')
                    ->query(fn (Builder $query) => $query->where('last_status', 'technician_assigned')),
                Tables\Filters\Filter::make('technician_on_the_way')
                    ->label('Technician On The Way')
                    ->query(fn (Builder $query) => $query->where('last_status', 'technician_on_the_way')),
                Tables\Filters\Filter::make('technician_arrived')
                    ->label('Technician Arrived')
                    ->query(fn (Builder $query) => $query->where('last_status', 'technician_arrived')),
                Tables\Filters\Filter::make('in_progress')
                    ->label('In Progress')
                    ->query(fn (Builder $query) => $query->where('last_status', 'in_progress')),
                Tables\Filters\Filter::make('waiting_for_payment')
                    ->label('Waiting For Payment')
                    ->query(fn (Builder $query) => $query->where('last_status', 'waiting_for_payment')),
                Tables\Filters\Filter::make('waiting_for_technician_confirm_payment')
                    ->label('Waiting For Technician Confirm Payment')
                    ->query(fn (Builder $query) => $query->where('last_status', 'waiting_for_technician_confirm_payment')),
                Tables\Filters\Filter::make('completed')
                    ->label('Completed')
                    ->query(fn (Builder $query) => $query->where('last_status', 'completed')),
                Tables\Filters\Filter::make('canceled')
                    ->label('Canceled')
                    ->query(fn (Builder $query) => $query->where('last_status', 'canceled')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Book Appointment')
                    ->label('Book Appointment')
                    ->icon('heroicon-o-calendar')
                    ->disabled(fn ($record) => !in_array($record->last_status, ['pending', 'technician_assigned']))
                    ->form([
                        DatePicker::make('selected_date')
                            ->label('Select Date')
                            ->reactive()
                            ->required(),
                        Select::make('slot_id')
                            ->label('Available Slots')
                            ->options(function ($get, $record) {
                                if (!$get('selected_date')) {
                                    return [];
                                }
                                $controller = app(MaintenanceRequestController::class);
                                $slots = $controller->getAvailableSlots(new \Illuminate\Http\Request([
                                    'request_id' => $record->id,
                                    'date' => $get('selected_date'),
                                ]));
                                return collect($slots->original['data'] ?? [])->mapWithKeys(fn ($slot) => [
                                    $slot['id'] => $slot['technician']['first_name'] . ' ' . $slot['technician']['last_name'] . ' - ' . $slot['time'],
                                ])->toArray();
                            })
                            ->required()
                            ->reactive(),
                    ])
                    ->action(function ($data, $record) {
                        $controller = app(MaintenanceRequestController::class);
                        $response = $controller->assignSlot(new \Illuminate\Http\Request([
                            'request_id' => $record->id,
                            'slot_id' => $data['slot_id'],
                        ]));
                        if ($response->original['status'] == 200) {
                            return redirect()->with('success', 'Appointment assigned successfully.');
                        }
                        return redirect()->with('danger', 'Failed to assign slot.');
                    })
                    ->modalHeading('Book an Appointment')
                    ->modalSubmitActionLabel('Assign Slot'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
