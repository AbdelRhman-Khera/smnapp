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
use Filament\Tables\Filters\SelectFilter;

class MaintenanceRequestResource extends Resource
{
    protected static ?string $model = MaintenanceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // TextInput::make('entry_sap_id')
                //     ->default(fn() => auth()->user()?->sap_id)
                //     ->disabled()
                //     ->hidden()
                //     ->dehydrated(false),

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
                    ->collapsed()
                    ->visible(fn($operation) => in_array($operation, ['view', 'edit'])),

                Select::make('type')
                    ->options([
                        'new_installation' => 'New Installation',
                        'regular_maintenance' => 'Regular Maintenance',
                        'emergency_maintenance' => 'Emergency Maintenance',
                        'warranty' => 'Warranty',
                    ])
                    ->live()
                    ->required(),

                Select::make('warranty_source_request_id')
                    ->label('Warranty Source Request')
                    ->options(fn ($get) => \App\Models\MaintenanceRequest::query()
                        ->where('customer_id', $get('customer_id'))
                        ->where('last_status', 'completed')
                        ->where(function (Builder $query) {
                            $query->where(function (Builder $query) {
                                $query->whereIn('type', ['regular_maintenance', 'emergency_maintenance'])
                                    ->whereHas('statuses', fn (Builder $query) => $query
                                        ->where('status', 'completed')
                                        ->where('created_at', '>=', now()->subMonth()));
                            })->orWhere(function (Builder $query) {
                                $query->where('type', 'new_installation')
                                    ->whereHas('statuses', fn (Builder $query) => $query
                                        ->where('status', 'completed')
                                        ->where('created_at', '>=', now()->subYears(2)));
                            });
                        })
                        ->latest('id')
                        ->limit(50)
                        ->pluck('id', 'id'))
                    ->searchable()
                    ->required(fn ($get) => $get('type') === 'warranty')
                    ->visible(fn ($get) => $get('type') === 'warranty'),

                Repeater::make('products_items')
                    ->label('Products')
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->options(fn (string $operation) => Product::query()
                                ->when($operation !== 'view', fn (Builder $query) => $query->active())
                                ->pluck('name_ar', 'id'))
                            ->searchable()

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
                TextColumn::make('id')
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $query) use ($search) {
                            $query->where('id', $search)
                                ->orWhere('sap_order_id', 'like', "%{$search}%")
                                ->orWhere('sap_sales_order_no', 'like', "%{$search}%")
                                ->orWhere('invoice_number', 'like', "%{$search}%")
                                ->orWhereHas('customer', function (Builder $customerQuery) use ($search) {
                                    $customerQuery->where('phone', 'like', "%{$search}%");
                                });
                        });
                    }),
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
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('customer', function (Builder $customerQuery) use ($search) {
                            $customerQuery->where('phone', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('type')
                    ->searchable()
                    ->label('Type')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'new_installation' => 'New Installation',
                            'regular_maintenance' => 'Regular Maintenance',
                            'emergency_maintenance' => 'Emergency Maintenance',
                            'warranty' => 'Warranty',
                            default => $state,
                        };
                    }),
                TextColumn::make('last_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
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
                    ->formatStateUsing(fn($state) => match ($state) {
                        'pending' => 'Pending',
                        'visit_payment_pending' => 'Visit Payment Pending',
                        'service_paid' => 'Service Paid',
                        'technician_assigned' => 'Technician Assigned',
                        'technician_on_the_way' => 'Technician On The Way',
                        'technician_arrived' => 'Technician Arrived',
                        'in_progress' => 'In Progress',
                        'waiting_for_payment' => 'Waiting For Payment',
                        'waiting_for_technician_confirm_payment' => 'Waiting For Technician Confirm Payment',
                        'completed' => 'Completed',
                        'canceled' => 'Canceled',
                        default => $state,
                    }),
                TextColumn::make('address.city.name_ar')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('address.district.name_ar')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                // TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('createdBy.name')->label('Created By')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updatedBy.name')->label('Updated By')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('is_open_for_freelancers')
                    ->label('Freelancer Open')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Open' : 'Closed')
                    ->color(fn($state) => $state ? 'warning' : 'gray'),
            ])->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('last_status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'visit_payment_pending' => 'Visit Payment Pending',
                        'service_paid' => 'Service Paid',
                        'technician_assigned' => 'Technician Assigned',
                        'technician_on_the_way' => 'Technician On The Way',
                        'technician_arrived' => 'Technician Arrived',
                        'in_progress' => 'In Progress',
                        'waiting_for_payment' => 'Waiting For Payment',
                        'waiting_for_technician_confirm_payment' => 'Waiting For Technician Confirm Payment',
                        'completed' => 'Completed',
                        'canceled' => 'Canceled',
                    ])
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->label('Created At')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_to'),
                    ])
                    ->query(function ($query, $data) {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn($q) =>
                                $q->whereDate('created_at', '>=', $data['created_from'])
                            )
                            ->when(
                                $data['created_to'],
                                fn($q) =>
                                $q->whereDate('created_at', '<=', $data['created_to'])
                            );
                    }),
                Filter::make('slot_date_range')
                    ->label('Slot Date')
                    ->form([
                        DatePicker::make('slot_from'),
                        DatePicker::make('slot_to'),
                    ])
                    ->query(function ($query, $data) {
                        return $query
                            ->when(
                                $data['slot_from'],
                                fn($q) =>
                                $q->whereHas(
                                    'slot',
                                    fn($qq) =>
                                    $qq->whereDate('date', '>=', $data['slot_from'])
                                )
                            )
                            ->when(
                                $data['slot_to'],
                                fn($q) =>
                                $q->whereHas(
                                    'slot',
                                    fn($qq) =>
                                    $qq->whereDate('date', '<=', $data['slot_to'])
                                )
                            );
                    }),
                // SelectFilter::make('created_by')
                //     ->label('Created By')
                //     ->options(
                //         \App\Models\User::pluck('name', 'id')
                //             ->prepend('From App (Customer)', 'app')
                //             ->toArray()
                //     )
                //     ->query(function (Builder $query, array $data) {

                //         if (!filled($data['value'])) {
                //             return $query;
                //         }

                //         if ($data['value'] === 'app') {
                //             return $query->whereNull('created_by');
                //         }

                //         return $query->where('created_by', $data['value']);
                //     }),

                SelectFilter::make('created_by')
                    ->label('Created By')
                    ->options(
                        \App\Models\User::pluck('name', 'id')
                            ->prepend('From App (Customer)', 'customer')
                            ->toArray()
                    )
                    ->query(function (Builder $query, array $data) {

                        $value = $data['value'] ?? null;

                        if (!filled($value)) {
                            return $query;
                        }

                        if ($value === 'customer') {

                            $userIds = \App\Models\User::pluck('id');

                            return $query->whereNotIn('created_by', $userIds);
                        }

                        return $query->where('created_by', $value);
                    }),
                SelectFilter::make('is_open_for_freelancers')
                    ->label('Open for Freelancers')
                    ->options([
                        1 => 'Yes',
                        0 => 'No',
                    ]),
                SelectFilter::make('type')
                    ->label('Maintenance Type')
                    ->options([
                        'new_installation' => 'New Installation',
                        'regular_maintenance' => 'Regular Maintenance',
                        'emergency_maintenance' => 'Emergency Maintenance',
                        'warranty' => 'Warranty',
                    ]),
                SelectFilter::make('technician_id')
                    ->label('Technician')
                    ->relationship('technician', 'first_name')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(
                        fn($record) =>
                        $record->first_name . ' ' . $record->last_name
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Book Appointment')
                    ->label('Book Appointment')
                    ->icon('heroicon-o-calendar')
                    ->disabled(fn($record) => !in_array($record->last_status, ['pending', 'service_paid', 'technician_assigned']))
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
                Tables\Actions\Action::make('cancel_request')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')

                    ->visible(fn($record) => !in_array($record->last_status, ['completed', 'canceled']))

                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Cancellation Reason')
                            ->required()
                            ->rows(3),
                    ])

                    ->action(function ($data, $record) {

                        $employeeName = auth()->user()->name;

                        $noteWithUser = "Canceled by: {$employeeName} | Reason: {$data['note']}";

                        $record->statuses()->create([
                            'status' => 'canceled',
                            'notes'   => $noteWithUser,
                        ]);

                        $record->update([
                            'last_status' => 'canceled',
                        ]);

                        if ($record->slot_id) {
                            $record->slot?->update([
                                'is_booked' => false,
                            ]);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Request canceled successfully')
                            ->success()
                            ->send();
                    })

                    ->modalHeading('Cancel Request')
                    ->modalSubmitActionLabel('Confirm Cancellation')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
            'technician-appointments' => Pages\TechnicianAppointments::route('/appointments/{technician}'),
        ];
    }

    protected static function fetchAvailableSlots($record, $selectedDate): array
    {
        if (!$selectedDate) {
            return [];
        }

        // Call the controller method directly
        $controller = app(MaintenanceRequestController::class);
        $slots = $controller->getAvailableSlots2(new \Illuminate\Http\Request([
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
