<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaintenanceRequestResource\Pages;
use App\Filament\Resources\MaintenanceRequestResource\RelationManagers;
use App\Http\Controllers\MaintenanceRequestController;
use App\Models\MaintenanceRequest;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;

class MaintenanceRequestResource extends Resource
{
    protected static ?string $model = MaintenanceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('customer_id')
                    ->relationship('customer', 'phone')
                    ->searchable()
                    ->required()
                    ->reactive(),

                Select::make('address_id')
                    ->label('Address')
                    ->options(
                        fn($get) =>
                        $get('customer_id')
                            ? \App\Models\Address::where('customer_id', $get('customer_id'))->pluck('name', 'id')
                            : []
                    )
                    ->searchable()
                    ->reactive()
                    ->required()
                    ->disabled(fn($get) => !$get('customer_id')),

                Section::make('Address Details')
                    ->schema([
                        Placeholder::make('address_name')
                            ->label('Address Name')
                            ->content(fn($record) => $record?->address?->name ?? 'N/A'),

                        Placeholder::make('city')
                            ->label('City')
                            ->content(fn($record) => $record?->address?->city?->name_ar ?? 'N/A'),

                        Placeholder::make('district')
                            ->label('District')
                            ->content(fn($record) => $record?->address?->district?->name_ar ?? 'N/A'),

                        Placeholder::make('street')
                            ->label('Street')
                            ->content(fn($record) => $record?->address?->street ?? 'N/A'),

                        Placeholder::make('national_address')
                            ->label('National Address')
                            ->content(fn($record) => $record?->address?->national_address ?? 'N/A'),

                        Placeholder::make('details')
                            ->label('Details')
                            ->content(fn($record) => $record?->address?->details ?? 'N/A'),

                        Placeholder::make('latitude')
                            ->label('Latitude')
                            ->content(fn($record) => $record?->address?->latitude ?? 'N/A'),

                        Placeholder::make('longitude')
                            ->label('Longitude')
                            ->content(fn($record) => $record?->address?->longitude ?? 'N/A'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->visible(fn($operation) => in_array($operation, ['view', 'edit'])),

                Select::make('type')
                    ->options([
                        'new_installation' => 'New Installation',
                        'regular_maintenance' => 'Regular Maintenance',
                        'emergency_maintenance' => 'Emergency Maintenance',
                    ])
                    ->required(),

                Repeater::make('products_items')
                    ->label('Products')
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->options(fn() => Product::query()->pluck('name_ar', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('quantity')
                            ->label('Qty')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                    ])
                    ->columns(2)
                    ->minItems(1)
                    ->defaultItems(1)
                    ->dehydrated(true),

                TextInput::make('sap_order_id')
                    ->rules([
                        fn($get) => $get('type') === 'new_installation' ? 'required' : 'nullable',
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
                            ->default(fn() => auth()->user()->name)
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
                    ->default(fn($record) => is_array($record?->notes) ? $record->notes : [])
                    ->disableItemDeletion()
                    ->disableItemMovement()
                    ->addable(true)
                    ->columns(3)
                    ->columnSpanFull(),

            ]);
    }

    public static function table(Table $table): Table
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
                TextColumn::make('customer.phone')->searchable()->label('Phone'),
                TextColumn::make('type')
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
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])->defaultSort('id', 'desc')
            ->filters([
                Filter::make('new_installation')
                    ->label('New Installation')
                    ->query(fn(Builder $query) => $query->where('type', 'new_installation')),
                Filter::make('regular_maintenance')
                    ->label('Regular Maintenance')
                    ->query(fn(Builder $query) => $query->where('type', 'regular_maintenance')),
                Filter::make('emergency_maintenance')
                    ->label('Emergency Maintenance')
                    ->query(fn(Builder $query) => $query->where('type', 'emergency_maintenance')),
                Filter::make('pending')
                    ->label('Pending')
                    ->query(fn(Builder $query) => $query->where('last_status', 'pending')),
                Filter::make('technician_assigned')
                    ->label('Technician Assigned')
                    ->query(fn(Builder $query) => $query->where('last_status', 'technician_assigned')),
                Filter::make('technician_on_the_way')
                    ->label('Technician On The Way')
                    ->query(fn(Builder $query) => $query->where('last_status', 'technician_on_the_way')),

                Filter::make('in_progress')
                    ->label('In Progress')
                    ->query(fn(Builder $query) => $query->where('last_status', 'in_progress')),
                Filter::make('waiting_for_payment')
                    ->label('Waiting For Payment')
                    ->query(fn(Builder $query) => $query->where('last_status', 'waiting_for_payment')),
                Filter::make('waiting_for_technician_confirm_payment')
                    ->label('Waiting For Technician Confirm Payment')
                    ->query(fn(Builder $query) => $query->where('last_status', 'waiting_for_technician_confirm_payment')),
                Filter::make('completed')
                    ->label('Completed')
                    ->query(fn(Builder $query) => $query->where('last_status', 'completed')),
                Filter::make('canceled')
                    ->label('Canceled')
                    ->query(fn(Builder $query) => $query->where('last_status', 'canceled')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Book Appointment')
                    ->label('Book Appointment')
                    ->icon('heroicon-o-calendar')
                    ->disabled(fn($record) => !in_array($record->last_status, ['pending', 'technician_assigned']))
                    ->form([
                        DatePicker::make('selected_date')
                            ->label('Select Date')
                            ->reactive()
                            ->required(),

                        Select::make('slot_id')
                            ->label('Available Slots')
                            ->options(fn($get, $record) => self::fetchAvailableSlots($record, $get('selected_date')))
                            ->required()
                            ->reactive(),
                    ])
                    ->action(fn($data, $record) => self::assignSlot($record, $data['slot_id']))
                    ->modalHeading('Book an Appointment')
                    ->modalButton('Assign Slot'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaintenanceRequests::route('/'),
            'create' => Pages\CreateMaintenanceRequest::route('/create'),
            'view' => Pages\ViewMaintenanceRequest::route('/{record}'),
            'edit' => Pages\EditMaintenanceRequest::route('/{record}/edit'),
        ];
    }

    protected static function fetchAvailableSlots($record, $selectedDate): array
    {
        if (!$selectedDate) {
            return [];
        }

        // Call the controller method directly
        $controller = app(MaintenanceRequestController::class);
        $slots = $controller->getAvailableSlots(new \Illuminate\Http\Request([
            'request_id' => $record->id,
            'date' => $selectedDate,
        ]));
        // dd($slots);
        // Convert the response to an array of options
        return collect($slots->original['data'] ?? [])->mapWithKeys(fn($slot) => [
            $slot['id'] => $slot['technician']['first_name'] . ' ' . $slot['technician']['last_name'] . ' - ' . $slot['time'],
        ])->toArray();
    }

    /**
     * Assign Slot to Maintenance Request Without Modifying the Controller
     */
    protected static function assignSlot($record, $slotId)
    {
        // Call the controller method directly
        $controller = app(MaintenanceRequestController::class);
        $response = $controller->assignSlot(new \Illuminate\Http\Request([
            'request_id' => $record->id,
            'slot_id' => $slotId,
        ]));

        // Check if assignment was successful
        if ($response->original['status'] == 200) {
            return redirect()->with('success', 'Appointment assigned successfully.');
        }

        return redirect()->with('danger', 'Failed to assign slot.');
    }
}
