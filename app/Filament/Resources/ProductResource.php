<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\BelongsToColumn;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationGroup = 'Business Management';
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('sap_id')->label('SAP ID')->required(),
            TextInput::make('name_ar')->label('Name (Arabic)')->required(),
            TextInput::make('name_en')->label('Name (English)')->required(),
            Textarea::make('description_ar')->label('Description (Arabic)'),
            Textarea::make('description_en')->label('Description (English)'),
            FileUpload::make('image')->label('Image'),
            TextInput::make('hours')->label('Hours')->numeric()->required(),
            TextInput::make('maintenance_fee')
                ->label('Product Maintenance Fee')
                ->numeric()
                ->step(0.01)
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
            Select::make('category_id')
                ->label('Category')
                ->relationship('category', 'name_en')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sap_id')->label('SAP ID'),
                TextColumn::make('name_ar')->label('Name (Arabic)'),
                TextColumn::make('name_en')->label('Name (English)'),
                TextColumn::make('category.name_en')->label('Category')->sortable()->searchable(),
                TextColumn::make('maintenance_fee')->label('Maintenance Fee')->money('SAR')->sortable(),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => (int) $state === 1 ? 'Active' : 'Inactive')
                    ->color(fn ($state): string => (int) $state === 1 ? 'success' : 'danger'),
            ])->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
