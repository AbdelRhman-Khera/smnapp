<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TechnicianSparePartRequestResource\Pages;
use App\Filament\Resources\TechnicianSparePartRequestResource\RelationManagers;
use App\Models\TechnicianSparePartRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class TechnicianSparePartRequestResource extends Resource
{
    protected static ?string $model = TechnicianSparePartRequest::class;
    protected static ?string $navigationGroup = 'Business Management';

    protected static ?string $navigationLabel = 'Technician Material Requests';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name_ar')
                    ->required(),
                Textarea::make('notes')->label('Notes')->nullable(),
                Repeater::make('items')
                    ->label('Spare Parts')
                    ->relationship('items')
                    ->schema([
                        Select::make('spare_part_id')
                            ->label('Spare Part')
                            ->relationship('sparePart', 'name_ar')
                            ->required(),
                        TextInput::make('quantity')->label('Quantity')->numeric()->minValue(1)->required(),
                    ])
                    ->minItems(1)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('branch.name_ar')->label('Branch')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('technician.name')->label('Technician')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Created At')->dateTime()->sortable(),
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
            'index' => Pages\ListTechnicianSparePartRequests::route('/'),
            'create' => Pages\CreateTechnicianSparePartRequest::route('/create'),
            'edit' => Pages\EditTechnicianSparePartRequest::route('/{record}/edit'),
            // 'view' => Pages\ViewTechnicianSparePartRequest::route('/{record}'),
        ];
    }
}
