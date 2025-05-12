<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaintenanceRequestResource\Pages;
use App\Filament\Resources\MaintenanceRequestResource\RelationManagers;
use App\Http\Controllers\MaintenanceRequestController;
use App\Models\MaintenanceRequest;
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

class MaintenanceRequestResource extends Resource
{
    protected static ?string $model = MaintenanceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('customer_id')
                    ->relationship('customer', 'first_name')
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

                // Repeater::make('statuses')
                // ->label('Statuses')
                // ->relationship('statuses') // Uses the statuses() relationship
                // ->schema([
                //     TextInput::make('status')
                //         ->default('pending')  // Default status is 'pending'
                //         ->disabled(),         // User cannot change it
                // ])
                // // ->hidden() // Hide from the user
                // ->disableItemCreation() // Prevent adding new statuses manually
                // ->disableItemDeletion()
                // ->disableItemMovement(),

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
                TextColumn::make('customer.name')->searchable()->label('Customer'),
                TextColumn::make('customer.phone')->sortable()->searchable()->label('Phone'),
                TextColumn::make('type')->sortable()->searchable()->label('Type'),
                TextColumn::make('last_status')->sortable()->searchable()->label('Status'),
                // TextColumn::make('created_at')->dateTime()->sortable(),
            ])->defaultSort('id', 'desc')
            ->filters([
                Filter::make('New Installation')
                    ->query(fn(Builder $query) => $query->where('type', 'new_installation')),
                Filter::make('Regular Maintenance')
                    ->query(fn(Builder $query) => $query->where('type', 'regular_maintenance')),
                Filter::make('Emergency Maintenance')
                    ->query(fn(Builder $query) => $query->where('type', 'emergency_maintenance')),
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
