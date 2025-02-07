<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SlotResource\Pages;
use App\Models\Slot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;

class SlotResource extends Resource
{
    protected static ?string $model = Slot::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('technician_id')
                    ->label('Technician')
                    ->options(
                        \App\Models\Technician::query()
                            ->selectRaw("id, CONCAT(first_name, ' ', last_name) as full_name")
                            ->pluck('full_name', 'id')
                    )
                    ->searchable()
                    ->required(),

                DatePicker::make('date')
                    ->label('Date')
                    ->required(),

                TimePicker::make('time')
                    ->label('Time')
                    ->required(),

                Toggle::make('is_booked')
                    ->label('Is Booked')
                    ->default(false)
                    ->disabled(fn ($get) => $get('is_booked')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('technician.first_name')->label('Technician')->sortable(),
                TextColumn::make('date')->label('Date')->sortable(),
                TextColumn::make('time')->label('Time')->sortable(),
                BooleanColumn::make('is_booked')->label('Booked'),
            ])
            ->filters([
                Tables\Filters\Filter::make('Available Slots')
                    ->query(fn ($query) => $query->where('is_booked', false)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSlots::route('/'),
            'create' => Pages\CreateSlot::route('/create'),
            'edit' => Pages\EditSlot::route('/{record}/edit'),
        ];
    }
}
