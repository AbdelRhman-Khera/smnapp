<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
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

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;
    protected static ?string $navigationGroup = 'Business Management';
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Service Details')->tabs([
                Tab::make('Arabic')->schema([
                    TextInput::make('name_ar')->label('Name (AR)')->required(),
                    Textarea::make('description_ar')->label('Description (AR)')->nullable(),
                ]),
                Tab::make('English')->schema([
                    TextInput::make('name_en')->label('Name (EN)')->required(),
                    Textarea::make('description_en')->label('Description (EN)')->nullable(),
                ]),
            ])->columnSpanFull(),
            FileUpload::make('image')->label('Image')->image()->nullable(),
            TextInput::make('price')->label('Price')->numeric()->required(),
            Toggle::make('is_active')->label('Active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_ar')->label('Name (AR)')->sortable()->searchable(),
                TextColumn::make('name_en')->label('Name (EN)')->sortable()->searchable(),
                TextColumn::make('price')->label('Price')->sortable(),
                TextColumn::make('is_active')
                ->label('Active')
                ->badge()
                ->colors([
                    'success' => fn ($state) => $state == true,
                    'danger' => fn ($state) => $state == false,
                ])
                ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive'),
            ])->defaultSort('id', 'desc')
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
