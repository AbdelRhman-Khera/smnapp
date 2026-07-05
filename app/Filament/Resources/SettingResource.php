<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_any_setting') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create_setting') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('update_setting') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete_setting') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete_any_setting') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Setting')
                    ->schema([
                        Forms\Components\TextInput::make('group')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('key')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('label')
                            ->maxLength(150),

                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'payment_methods' => 'Payment Methods',
                                'technician_fees' => 'Technician Fees',
                                'json' => 'JSON',
                            ])
                            ->default('json')
                            ->live(),

                        Forms\Components\Toggle::make('is_public')
                            ->label('Public API'),

                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Payment Methods')
                    ->description('Mobile app uses active methods only.')
                    ->schema([
                        Forms\Components\Repeater::make('value')
                            ->hiddenLabel()
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->maxLength(50),

                                Forms\Components\TextInput::make('label_en')
                                    ->label('English Label')
                                    ->required()
                                    ->maxLength(100),

                                Forms\Components\TextInput::make('label_ar')
                                    ->label('Arabic Label')
                                    ->maxLength(100),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),

                                Forms\Components\TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                            ])
                            ->columns(5)
                            ->reorderable()
                            ->defaultItems(0),
                    ])
                    ->visible(fn (Forms\Get $get): bool => $get('type') === 'payment_methods'),

                Forms\Components\Section::make('Technician Maintenance Fees')
                    ->description('Fee credited to the technician wallet for each completed request, by request type.')
                    ->schema([
                        Forms\Components\Repeater::make('value')
                            ->hiddenLabel()
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->required()
                                    ->options([
                                        'new_installation' => 'New Installation',
                                        'regular_maintenance' => 'Regular Maintenance',
                                        'emergency_maintenance' => 'Emergency Maintenance',
                                        'warranty' => 'Warranty',
                                    ])
                                    ->distinct(),

                                Forms\Components\TextInput::make('label_en')
                                    ->label('English Label')
                                    ->maxLength(100),

                                Forms\Components\TextInput::make('label_ar')
                                    ->label('Arabic Label')
                                    ->maxLength(100),

                                Forms\Components\TextInput::make('fee')
                                    ->label('Fee (SAR)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0),
                            ])
                            ->columns(4)
                            ->reorderable()
                            ->defaultItems(0),
                    ])
                    ->visible(fn (Forms\Get $get): bool => $get('type') === 'technician_fees'),

                Forms\Components\Section::make('JSON Value')
                    ->schema([
                        Forms\Components\KeyValue::make('value')
                            ->hiddenLabel()
                            ->keyLabel('Key')
                            ->valueLabel('Value'),
                    ])
                    ->visible(fn (Forms\Get $get): bool => ! in_array($get('type'), ['payment_methods', 'technician_fees'], true)),

                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('label')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('Public')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('Y-m-d h:i A')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}
