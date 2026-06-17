<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AreaResource\Pages;
use App\Models\Area;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AreaResource extends Resource
{
    protected static ?string $model = Area::class;

    protected static ?string $navigationGroup = 'Geographical Locations';
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Language')
                ->tabs([
                    Tab::make('Arabic')->schema([
                        TextInput::make('name_ar')
                            ->label('Name (Arabic)')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description_ar')
                            ->label('Description (Arabic)')
                            ->nullable(),
                    ]),
                    Tab::make('English')->schema([
                        TextInput::make('name_en')
                            ->label('Name (English)')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description_en')
                            ->label('Description (English)')
                            ->nullable(),
                    ]),
                ])
                ->columnSpanFull(),
            TextInput::make('maintenance_fee')
                ->label('Area Maintenance Fee')
                ->numeric()
                ->step(0.01)
                ->default(0)
                ->required(),
            TextInput::make('extra_hours')
                ->label('Extra Hours')
                ->numeric()
                ->step(0.25)
                ->default(0)
                ->required(),
            Select::make('is_active')
                ->label('Status')
                ->options([
                    1 => 'Active',
                    0 => 'Inactive',
                ])
                ->default(1)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_ar')->label('Name (Arabic)')->sortable()->searchable(),
                TextColumn::make('name_en')->label('Name (English)')->sortable()->searchable(),
                TextColumn::make('maintenance_fee')->label('Area Maintenance Fee')->money('SAR')->sortable(),
                TextColumn::make('extra_hours')->label('Extra Hours')->sortable(),
                TextColumn::make('districts_count')->counts('districts')->label('Districts')->sortable(),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'Active' : 'Inactive')
                    ->color(fn ($state): string => $state ? 'success' : 'danger'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
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
            'index' => Pages\ListAreas::route('/'),
            'create' => Pages\CreateArea::route('/create'),
            'edit' => Pages\EditArea::route('/{record}/edit'),
        ];
    }
}
