<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TechnicianSparePartRequestResource\Pages;
use App\Models\TechnicianSparePartRequest;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

class TechnicianSparePartRequestResource extends Resource
{
    protected static ?string $model = TechnicianSparePartRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Business Management';

    protected static ?string $navigationLabel = 'Technician Spare Part Requests';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                TechnicianSparePartRequest::query()
                    ->with([
                        'branch',
                        'technician',
                        'items.sparePart',
                    ])
            )

            ->columns([

                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('branch.name_en')
                    ->label('Branch')
                    ->searchable(),

                Tables\Columns\TextColumn::make('technician.first_name')
                    ->label('Technician')
                    ->formatStateUsing(fn ($record) =>
                        $record->technician?->first_name . ' ' .
                        $record->technician?->last_name
                    )
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {

                        'pending' => 'warning',

                        'created' => 'info',

                        'failed' => 'danger',

                        'ready_to_deliver' => 'primary',

                        'delivered' => 'success',

                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('sap_ref')
                    ->label('SAP Ref')
                    ->searchable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('Y-m-d h:i A')
                    ->sortable(),

            ])

            ->defaultSort('id', 'desc')

            ->filters([

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'created' => 'Created',
                        'failed' => 'Failed',
                        'ready_to_deliver' => 'Ready To Deliver',
                        'delivered' => 'Delivered',
                    ]),

                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name_en')
                    ->label('Branch'),

                Tables\Filters\SelectFilter::make('technician_id')
                    ->relationship('technician', 'first_name')
                    ->label('Technician'),

            ])

            ->actions([

                Tables\Actions\ViewAction::make(),

            ])

            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([

                Section::make('Request Information')

                    ->schema([

                        Grid::make(3)

                            ->schema([

                                TextEntry::make('id'),

                                TextEntry::make('status')
                                    ->badge(),

                                TextEntry::make('sap_ref')
                                    ->label('SAP Reference'),

                                TextEntry::make('branch.name_en')
                                    ->label('Branch'),

                                TextEntry::make('technician.first_name')
                                    ->label('Technician')
                                    ->formatStateUsing(fn ($record) =>
                                        $record->technician?->first_name . ' ' .
                                        $record->technician?->last_name
                                    ),

                                TextEntry::make('created_at')
                                    ->dateTime('Y-m-d h:i A'),

                            ]),

                        TextEntry::make('notes')
                            ->columnSpanFull(),

                    ]),

                Section::make('Requested Items')

                    ->schema([

                        RepeatableEntry::make('items')

                            ->schema([

                                Grid::make(5)

                                    ->schema([

                                        TextEntry::make('sparePart.name_en')
                                            ->label('Spare Part'),

                                        TextEntry::make('sparePart.sap_id')
                                            ->label('SAP ID'),

                                        TextEntry::make('quantity')
                                            ->label('Requested Qty'),

                                        TextEntry::make('approved_quantity')
                                            ->label('Approved Qty')
                                            ->default('-'),

                                        TextEntry::make('item_no')
                                            ->label('PO Item'),

                                    ]),

                            ])

                            ->contained(false),

                    ]),

                Section::make('SAP Response')

                    ->collapsed()

                    ->schema([

                        TextEntry::make('response')
                            ->state(fn (TechnicianSparePartRequest $record): string => static::formatJsonState($record->response))
                            ->fontFamily('mono')
                            ->columnSpanFull(),

                    ]),

                Section::make('GR Response')

                    ->collapsed()

                    ->schema([

                        TextEntry::make('gr_response')
                            ->state(fn (TechnicianSparePartRequest $record): string => static::formatJsonState($record->gr_response))
                            ->fontFamily('mono')
                            ->columnSpanFull(),

                        // TextEntry::make('gr_sent_at')
                        //     ->dateTime('Y-m-d h:i A'),

                        TextEntry::make('delivered_at')
                            ->dateTime('Y-m-d h:i A'),

                    ]),

            ]);
    }

    protected static function formatJsonState(mixed $state): string
    {
        if (blank($state)) {
            return '-';
        }

        if (is_string($state)) {
            $decoded = json_decode($state, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $state;
            }

            $state = $decoded;
        }

        return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '-';
    }

    public static function getPages(): array
    {
        return [

            'index' => Pages\ListTechnicianSparePartRequests::route('/'),

            'view' => Pages\ViewTechnicianSparePartRequest::route('/{record}'),

        ];
    }
}
