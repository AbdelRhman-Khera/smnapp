<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\District;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Dotswan\FilamentMapPicker\Forms\Components\MapPicker;
use Dotswan\MapPicker\Facades\MapPicker as FacadesMapPicker;
use Dotswan\MapPicker\Fields\Map;
use Filament\Forms\Set;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    public function form(Form $form): Form
    {
       return $form->schema([
            Select::make('customer_id')
                ->relationship('customer', 'phone')
                ->required()
                ->default(fn ($livewire) => $livewire->ownerRecord->id)
                ->disabled(),
            TextInput::make('name')->required(),
            Select::make('city_id')
                ->relationship('city', 'name_ar')
                ->required()
                ->reactive()
                ->afterStateUpdated(fn(Set $set) => $set('district_id', null)),
            Select::make('district_id')
                ->label('District')
                ->required()
                ->reactive()
                ->options(
                    fn(Set $set, callable $get) =>
                    $get('city_id')
                        ? District::where('city_id', $get('city_id'))->pluck('name_ar', 'id')->toArray()
                        : []
                )
                ->disabled(fn(callable $get) => empty($get('city_id'))),
            TextInput::make('street')->required(),
            TextInput::make('national_address'),
            Textarea::make('details')->columnSpanFull(),
            Map::make('location')
                ->label('Pick Location')
                ->columnSpanFull()
                ->defaultLocation(latitude: 24.7136, longitude: 46.6753)
                ->afterStateUpdated(function (Set $set, ?array $state): void {
                    if ($state) {
                        $set('latitude', $state['lat'] ?? 24.7136);
                        $set('longitude', $state['lng'] ?? 46.6753);
                    }
                })
                ->afterStateHydrated(function ($state, $record, Set $set): void {
                    if ($record && $record->latitude && $record->longitude) {
                        $set('location', [
                            'lat' => (float) $record->latitude,
                            'lng' => (float) $record->longitude,
                        ]);
                    } else {

                        $set('location', [
                            'lat' => 24.7136,
                            'lng' => 46.6753
                        ]);
                    }
                })
                ->draggable()
                ->zoom(15)
                ->showMarker()
                ->tilesUrl("https://tile.openstreetmap.de/{z}/{x}/{y}.png")
                ->liveLocation(true, true, 5000),
        ]);
    }


    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('customer.first_name')->label('Customer')->sortable(),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('city.name_ar')->label('City (AR)')->sortable(),
                TextColumn::make('district.name_ar')->label('District (AR)')->sortable(),
                TextColumn::make('street')->sortable(),
            ])->defaultSort('id', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
