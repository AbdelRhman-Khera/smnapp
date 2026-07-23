<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductHandoverResource\Pages;
use App\Models\MaintenanceRequest;
use App\Models\ProductHandover;
use App\Services\NotificationService;
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
use Illuminate\Database\Eloquent\Builder;

class ProductHandoverResource extends Resource
{
    protected static ?string $model = ProductHandover::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Technicians Management';

    protected static ?string $navigationLabel = 'Product Handovers';

    protected static ?int $navigationSort = 6;

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::where('status', 'pending')->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function eligibleRequestsQuery(): Builder
    {
        return MaintenanceRequest::query()
            ->where('type', 'new_installation')
            ->where(fn (Builder $query) => $query
                ->where('is_product_delivered', 0)
                ->orWhereNull('is_product_delivered'))
            ->where('technician_received_products', false)
            ->whereNotNull('technician_id')
            ->whereNotIn('last_status', ['completed', 'canceled'])
            ->whereDoesntHave('productHandovers', fn (Builder $query) => $query
                ->whereIn('status', ['pending', 'accepted']));
    }

    public static function requestOptionLabel(MaintenanceRequest $maintenanceRequest): string
    {
        $customerName = trim(
            ($maintenanceRequest->customer?->first_name ?? '') . ' ' .
            ($maintenanceRequest->customer?->last_name ?? '')
        ) ?: 'No customer';

        return "#{$maintenanceRequest->id} | {$customerName} | " . ucwords(str_replace('_', ' ', (string) $maintenanceRequest->last_status));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Installation Request')
                ->schema([
                    Forms\Components\Select::make('maintenance_request_id')
                        ->label('Maintenance Request')
                        ->required()
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search): array => static::eligibleRequestsQuery()
                            ->with('customer')
                            ->where(fn (Builder $query) => $query
                                ->where('maintenance_requests.id', 'like', "%{$search}%")
                                ->orWhereHas('customer', fn (Builder $customerQuery) => $customerQuery
                                    ->where('phone', 'like', "%{$search}%")))
                            ->limit(20)
                            ->get()
                            ->mapWithKeys(fn (MaintenanceRequest $maintenanceRequest): array => [
                                $maintenanceRequest->id => static::requestOptionLabel($maintenanceRequest),
                            ])
                            ->all())
                        ->getOptionLabelUsing(function ($value): ?string {
                            $maintenanceRequest = MaintenanceRequest::with('customer')->find($value);

                            return $maintenanceRequest ? static::requestOptionLabel($maintenanceRequest) : null;
                        })
                        ->helperText('Only new installation requests with undelivered products, an assigned technician, and no active handover are listed.')
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set): void {
                            $maintenanceRequest = $state
                                ? static::eligibleRequestsQuery()->with(['products', 'technician'])->find($state)
                                : null;

                            if (! $maintenanceRequest) {
                                $set('technician_name', null);
                                $set('sap_order_id', null);
                                $set('items', []);

                                return;
                            }

                            $set('technician_name', trim(
                                ($maintenanceRequest->technician?->first_name ?? '') . ' ' .
                                ($maintenanceRequest->technician?->last_name ?? '')
                            ) ?: '-');

                            $set('sap_order_id', $maintenanceRequest->sap_order_id ?: '-');

                            $items = [];

                            foreach ($maintenanceRequest->products as $product) {
                                $quantity = max(1, (int) ($product->pivot->quantity ?? 1));
                                $productName = $product->name_en ?: $product->name_ar;

                                for ($unit = 1; $unit <= $quantity; $unit++) {
                                    $items[] = [
                                        'product_id' => $product->id,
                                        'product_name' => $quantity > 1 ? "{$productName} (unit {$unit} of {$quantity})" : $productName,
                                        'serial_number' => null,
                                    ];
                                }
                            }

                            $set('items', $items);
                        }),

                    Forms\Components\TextInput::make('technician_name')
                        ->label('Assigned Technician')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Select a request first'),

                    Forms\Components\TextInput::make('sap_order_id')
                        ->label('SAP Order ID')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Select a request first'),

                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Products & Serial Numbers')
                ->description('Enter the serial number for every unit being handed to the technician.')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->hiddenLabel()
                        ->schema([
                            Forms\Components\Hidden::make('product_id'),
                            Forms\Components\TextInput::make('product_name')
                                ->label('Product')
                                ->disabled()
                                ->dehydrated(false),
                            Forms\Components\TextInput::make('serial_number')
                                ->label('Serial Number')
                                ->required()
                                ->maxLength(100)
                                ->distinct(),
                        ])
                        ->columns(2)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->defaultItems(0)
                        ->required(),
                ])
                ->visible(fn (Forms\Get $get): bool => filled($get('maintenance_request_id'))),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['technician', 'createdBy'])->withCount('items'))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('maintenance_request_id')
                    ->label('Request #')
                    ->sortable()
                    ->searchable()
                    ->url(fn (ProductHandover $record): string => MaintenanceRequestResource::getUrl('view', ['record' => $record->maintenance_request_id])),

                Tables\Columns\TextColumn::make('technician_full_name')
                    ->label('Technician')
                    ->getStateUsing(fn (ProductHandover $record): string => trim(
                        ($record->technician?->first_name ?? '') . ' ' . ($record->technician?->last_name ?? '')
                    ) ?: '-')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('technician', fn (Builder $technicianQuery) => $technicianQuery
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%"));
                    }),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Units')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        'canceled' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('Y-m-d h:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Processed At')
                    ->dateTime('Y-m-d h:i A')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                        'canceled' => 'Canceled',
                    ]),

                Tables\Filters\SelectFilter::make('technician_id')
                    ->relationship('technician', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => trim($record->first_name . ' ' . $record->last_name))
                    ->searchable()
                    ->preload()
                    ->label('Technician'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ProductHandover $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel product handover?')
                    ->modalDescription('The technician will be notified that this handover was canceled.')
                    ->action(function (ProductHandover $record): void {
                        $record->cancel(auth()->id());

                        NotificationService::notifyTechnicianTranslated(
                            $record->technician_id,
                            'notifications.technician.product_handover_canceled',
                            ['id' => $record->maintenance_request_id],
                            $record->maintenance_request_id
                        );
                    }),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Handover Information')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('id')
                            ->label('Handover #'),

                        TextEntry::make('maintenance_request_id')
                            ->label('Request #')
                            ->url(fn (ProductHandover $record): string => MaintenanceRequestResource::getUrl('view', ['record' => $record->maintenance_request_id])),

                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => ucfirst($state))
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'accepted' => 'success',
                                'rejected' => 'danger',
                                'canceled' => 'gray',
                                default => 'gray',
                            }),

                        TextEntry::make('technician.first_name')
                            ->label('Technician')
                            ->formatStateUsing(fn (ProductHandover $record): string => trim(
                                ($record->technician?->first_name ?? '') . ' ' . ($record->technician?->last_name ?? '')
                            ) ?: '-'),

                        TextEntry::make('createdBy.name')
                            ->label('Created By')
                            ->placeholder('-'),

                        TextEntry::make('created_at')
                            ->dateTime('Y-m-d h:i A'),

                        TextEntry::make('processed_at')
                            ->label('Processed At (by technician)')
                            ->dateTime('Y-m-d h:i A')
                            ->placeholder('-'),

                        TextEntry::make('canceledBy.name')
                            ->label('Canceled By')
                            ->placeholder('-')
                            ->visible(fn (ProductHandover $record): bool => $record->status === 'canceled'),

                        TextEntry::make('canceled_at')
                            ->label('Canceled At')
                            ->dateTime('Y-m-d h:i A')
                            ->placeholder('-')
                            ->visible(fn (ProductHandover $record): bool => $record->status === 'canceled'),
                    ]),

                    TextEntry::make('notes')
                        ->label('Employee Notes')
                        ->placeholder('-')
                        ->columnSpanFull(),

                    TextEntry::make('technician_notes')
                        ->label('Technician Notes')
                        ->placeholder('-')
                        ->columnSpanFull(),
                ]),

            Section::make('Products & Serial Numbers')
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->schema([
                            Grid::make(2)->schema([
                                TextEntry::make('product.name_en')
                                    ->label('Product')
                                    ->formatStateUsing(fn ($state, $record) => $record->product?->name_en ?: $record->product?->name_ar ?: '-'),

                                TextEntry::make('serial_number')
                                    ->label('Serial Number')
                                    ->copyable(),
                            ]),
                        ])
                        ->contained(false),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductHandovers::route('/'),
            'create' => Pages\CreateProductHandover::route('/create'),
            'view' => Pages\ViewProductHandover::route('/{record}'),
        ];
    }
}
