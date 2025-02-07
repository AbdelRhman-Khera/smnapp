<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AddressResource\Pages;
use App\Filament\Resources\AddressResource\RelationManagers;
use App\Models\Address;
use App\Models\District;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Form;
use Filament\Resources\Resource;
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

class AddressResource extends Resource
{
    protected static ?string $model = Address::class;

    protected static ?string $navigationGroup = 'Geographical Locations';
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('customer_id')
                ->relationship('customer', 'first_name')
                ->required(),
            TextInput::make('name')->required(),
            Select::make('city_id')
                ->relationship('city', 'name_ar')
                ->required()
                ->reactive()
                ->afterStateUpdated(fn(Set $set) => $set('district_id', null)), // إعادة تعيين حقل الأحياء عند تغيير المدينة
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.first_name')->label('Customer')->sortable(),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('city.name_ar')->label('City (AR)')->sortable(),
                TextColumn::make('district.name_ar')->label('District (AR)')->sortable(),
                TextColumn::make('street')->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListAddresses::route('/'),
            'create' => Pages\CreateAddress::route('/create'),
            'edit' => Pages\EditAddress::route('/{record}/edit'),
        ];
    }
}
