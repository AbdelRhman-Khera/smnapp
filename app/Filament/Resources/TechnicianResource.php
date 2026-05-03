<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TechnicianResource\Pages;
use App\Filament\Resources\TechnicianResource\RelationManagers;
use App\Models\Slot;
use App\Models\Technician;
use Dom\Text;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use App\Filament\Resources\MaintenanceRequestResource;
use Filament\Tables\Filters\SelectFilter;

class TechnicianResource extends Resource
{
    protected static ?string $model = Technician::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('first_name')->required(),
                TextInput::make('last_name')->required(),
                TextInput::make('phone')
                    ->unique(Technician::class, 'phone', ignoreRecord: true)
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->unique(Technician::class, 'email', ignoreRecord: true)
                    ->required(),
                TextInput::make('sap_id')->required(),
                TextInput::make('site_id')->required(),
                TextInput::make('password')
                    ->password()
                    ->required(fn($context) => $context === 'create')
                    ->dehydrated(fn($state) => filled($state))
                    ->afterStateUpdated(fn($state, $set) => $set('password', Hash::make($state)))
                    ->hiddenOn('view'),
                Select::make('manager_id')
                    ->relationship('manager', 'email')
                    ->required(),
                Select::make('districts')
                    ->relationship('districts', 'name_ar')
                    ->multiple()
                    ->preload(),
                Select::make('products')
                    ->relationship('products', 'name_ar')
                    ->multiple()
                    ->preload(),
                Toggle::make('authorized'),
                Toggle::make('activated'),
                Toggle::make('is_freelancer')
                    ->label('Freelancer Technician'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('id')->sortable(),
                TextColumn::make('first_name')->sortable()->searchable(),
                TextColumn::make('last_name')->sortable()->searchable(),
                TextColumn::make('sap_id')->sortable()->searchable(),
                // TextColumn::make('email')->sortable()->searchable(),
                TextColumn::make('phone')->sortable()->searchable(),
                TextColumn::make('rating')->sortable(),
                TextColumn::make('is_freelancer')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Freelancer' : 'Employee')
                    ->color(fn($state) => $state ? 'warning' : 'success'),
            ])->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('rating')
                    ->label('Rating')
                    ->options([
                        1 => '1 Star',
                        2 => '2 Stars',
                        3 => '3 Stars',
                        4 => '4 Stars',
                        5 => '5 Stars',
                    ]),

                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        '1' => 'Employee',
                        '2' => 'Freelancer',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('Generate Slots')
                    ->label('Generate Slots')
                    ->icon('heroicon-o-clock')
                    ->form([
                        Repeater::make('selected_dates')
                            ->label('Select Date(s)')
                            ->schema([
                                DatePicker::make('date')
                                    ->label('Select Date')
                                    ->required(),
                            ])
                            ->minItems(1)
                            ->columns(1)
                            ->addable(true)
                            ->deletable(true),
                    ])
                    ->action(fn($data, $record) => self::generateSlots($record, collect($data['selected_dates'])->pluck('date')->toArray()))
                    ->modalHeading('Generate Slots for Technician')
                    ->successNotificationTitle('Slots generated successfully')
                    ->modalButton('Create Slots'),
                Tables\Actions\Action::make('appointments')
                    ->label('Appointments')
                    ->icon('heroicon-o-calendar-days')
                    ->color('info')
                    ->url(fn($record) => MaintenanceRequestResource::getUrl('technician-appointments', [
                        'technician' => $record->id,
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('generate_slots')
                        ->label('Generate Slots')
                        ->icon('heroicon-o-clock')
                        ->form([
                            Repeater::make('selected_dates')
                                ->label('Select Date(s)')
                                ->schema([
                                    DatePicker::make('date')
                                        ->label('Select Date')
                                        ->required(),
                                ])
                                ->minItems(1)
                                ->columns(1)
                                ->addable(true)
                                ->deletable(true),
                        ])
                        ->action(function ($data, $records) {
                            foreach ($records as $technician) {
                                self::generateSlots($technician, collect($data['selected_dates'])->pluck('date')->toArray());
                            }
                        })
                        ->modalHeading('Generate Slots for Selected Technicians')
                        ->successNotificationTitle('Slots generated successfully')
                        ->modalButton('Create Slots'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    protected static function generateSlots($technician, $selectedDates)
    {
        $fixedTimes = ['08:00:00', '09:00:00', '10:00:00', '11:00:00', '16:00:00', '17:00:00', '18:00:00', '19:00:00'];

        foreach ($selectedDates as $date) {
            foreach ($fixedTimes as $time) {
                // Check if the slot already exists
                $existingSlot = Slot::where('technician_id', $technician->id)
                    ->where('date', $date)
                    ->where('time', $time)
                    ->exists();

                if (!$existingSlot) {
                    Slot::create([
                        'technician_id' => $technician->id,
                        'date' => $date,
                        'time' => $time,
                        'is_booked' => false,
                    ]);
                }
            }
        }

        return redirect()->with('success', 'Slots created successfully.');
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTechnicians::route('/'),
            'create' => Pages\CreateTechnician::route('/create'),
            'view' => Pages\ViewTechnician::route('/{record}'),
            'edit' => Pages\EditTechnician::route('/{record}/edit'),
        ];
    }
}
