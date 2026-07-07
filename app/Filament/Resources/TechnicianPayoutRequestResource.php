<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TechnicianPayoutRequestResource\Pages;
use App\Models\TechnicianPayoutRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TechnicianPayoutRequestResource extends Resource
{
    protected static ?string $model = TechnicianPayoutRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Technicians Management';

    protected static ?string $navigationLabel = 'Technician Payout Requests';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::where('status', 'pending')->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                TechnicianPayoutRequest::query()->with(['technician', 'processedBy'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('technician.first_name')
                    ->label('Technician')
                    ->formatStateUsing(fn ($record) =>
                        $record->technician?->first_name . ' ' .
                        $record->technician?->last_name
                    )
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount (SAR)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('requests_count')
                    ->label('Requests'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('processedBy.name')
                    ->label('Processed By')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('processed_at')
                    ->dateTime('Y-m-d h:i A')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('Y-m-d h:i A')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

                Tables\Filters\SelectFilter::make('technician_id')
                    ->relationship('technician', 'first_name')
                    ->label('Technician'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (TechnicianPayoutRequest $record): bool => $record->status === 'pending' && (auth()->user()?->can('update', $record) ?? false))
                    ->requiresConfirmation()
                    ->modalDescription('The payout amount will be deducted from the technician wallet balance automatically.')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Notes'),
                    ])
                    ->action(fn (TechnicianPayoutRequest $record, array $data) => $record->approve(auth()->id(), $data['admin_notes'] ?? null)),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (TechnicianPayoutRequest $record): bool => $record->status === 'pending' && (auth()->user()?->can('update', $record) ?? false))
                    ->requiresConfirmation()
                    ->modalDescription('The amount will return to the technician wallet balance.')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Rejection Reason')
                            ->required(),
                    ])
                    ->action(fn (TechnicianPayoutRequest $record, array $data) => $record->reject(auth()->id(), $data['admin_notes'] ?? null)),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Payout Request Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id'),

                                TextEntry::make('technician.first_name')
                                    ->label('Technician')
                                    ->formatStateUsing(fn ($record) =>
                                        $record->technician?->first_name . ' ' .
                                        $record->technician?->last_name
                                    ),

                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        default => 'gray',
                                    }),

                                TextEntry::make('total_amount')
                                    ->label('Total Amount (SAR)')
                                    ->numeric(decimalPlaces: 2),

                                TextEntry::make('requests_count')
                                    ->label('Maintenance Requests'),

                                TextEntry::make('created_at')
                                    ->label('Requested At')
                                    ->dateTime('Y-m-d h:i A'),

                                TextEntry::make('processedBy.name')
                                    ->label('Processed By')
                                    ->placeholder('-'),

                                TextEntry::make('processed_at')
                                    ->dateTime('Y-m-d h:i A')
                                    ->placeholder('-'),
                            ]),

                        TextEntry::make('notes')
                            ->label('Technician Notes')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('admin_notes')
                            ->label('Admin Notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Maintenance Requests Included')
                    ->schema([
                        RepeatableEntry::make('earnings')
                            ->hiddenLabel()
                            ->schema([
                                Grid::make(5)
                                    ->schema([
                                        TextEntry::make('maintenance_request_id')
                                            ->label('Request #'),

                                        TextEntry::make('request_type')
                                            ->label('Type')
                                            ->badge(),

                                        TextEntry::make('devices_count')
                                            ->label('Devices'),

                                        TextEntry::make('amount')
                                            ->label('Amount (SAR)')
                                            ->numeric(decimalPlaces: 2),

                                        TextEntry::make('status')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'pending' => 'warning',
                                                'requested' => 'info',
                                                'paid' => 'success',
                                                default => 'gray',
                                            }),
                                    ]),
                            ])
                            ->contained(false),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTechnicianPayoutRequests::route('/'),
            'view' => Pages\ViewTechnicianPayoutRequest::route('/{record}'),
        ];
    }
}
