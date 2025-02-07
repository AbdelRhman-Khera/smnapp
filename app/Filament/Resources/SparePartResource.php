<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SparePartResource\Pages;
use App\Filament\Resources\SparePartResource\RelationManagers;
use App\Models\SparePart;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;

class SparePartResource extends Resource
{
    protected static ?string $model = SparePart::class;
    protected static ?string $navigationGroup = 'Business Management';
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Spare Part Details')->tabs([
                    Tab::make('Arabic')->schema([
                        TextInput::make('name_ar')->label('Name (AR)')->required(),
                        Textarea::make('description_ar')->label('Description (AR)')->nullable(),
                    ]),
                    Tab::make('English')->schema([
                        TextInput::make('name_en')->label('Name (EN)')->required(),
                        Textarea::make('description_en')->label('Description (EN)')->nullable(),
                    ]),
                ])->columnSpanFull(),
                TextInput::make('sap_id')->label('SAP ID')->required()->unique(),
                TextInput::make('price')->label('Price')->numeric()->required(),
                TextInput::make('stock')->label('Stock Quantity')->numeric()->default(0),
                FileUpload::make('image')->label('Image')->image()->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sap_id')->label('SAP ID')->sortable()->searchable(),
                TextColumn::make('name_ar')->label('Name (AR)')->sortable()->searchable(),
                TextColumn::make('name_en')->label('Name (EN)')->sortable()->searchable(),
                TextColumn::make('price')->label('Price')->sortable(),
                TextColumn::make('stock')->label('Stock Quantity')->sortable(),
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
            'index' => Pages\ListSpareParts::route('/'),
            'create' => Pages\CreateSparePart::route('/create'),
            'edit' => Pages\EditSparePart::route('/{record}/edit'),
        ];
    }
}
