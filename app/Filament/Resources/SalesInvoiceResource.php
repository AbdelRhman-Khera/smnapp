<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaintenanceRequestResource;
use App\Filament\Resources\SalesInvoiceResource\Pages;
use App\Http\Controllers\SapController;
use App\Models\District;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Setting;
use App\Models\SparePart;
use App\Models\Technician;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalesInvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Business Management';

    protected static ?string $navigationLabel = 'Sales Invoices';

    protected static ?string $modelLabel = 'Sales Invoice';

    protected static ?string $pluralModelLabel = 'Sales Invoices';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_any_sales::invoice') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view_sales::invoice') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('update_sales::invoice') ?? false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Details')
                    ->schema([
                        Forms\Components\Placeholder::make('maintenance_request_id')
                            ->label('Request #')
                            ->content(fn (?Invoice $record): string => $record?->maintenance_request_id ? "#{$record->maintenance_request_id}" : '-'),

                        Forms\Components\Placeholder::make('current_total')
                            ->label('Current Total')
                            ->content(fn (?Invoice $record): string => number_format((float) ($record?->total ?? 0), 2) . ' SAR'),

                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options(fn (): array => static::paymentMethodOptions())
                            ->searchable()
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Services')
                    ->schema([
                        Forms\Components\Repeater::make('services_items')
                            ->hiddenLabel()
                            ->schema([
                                Forms\Components\Select::make('service_id')
                                    ->label('Service')
                                    ->options(fn (): array => Service::query()
                                        ->orderBy('name_en')
                                        ->pluck('name_en', 'id')
                                        ->toArray())
                                    ->searchable()
                                    ->required(),
                            ])
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->columns(1),
                    ]),

                Forms\Components\Section::make('Spare Parts')
                    ->schema([
                        Forms\Components\Repeater::make('spare_parts_items')
                            ->hiddenLabel()
                            ->schema([
                                Forms\Components\Select::make('spare_part_id')
                                    ->label('Spare Part')
                                    ->options(fn (): array => SparePart::query()
                                        ->orderBy('name_en')
                                        ->pluck('name_en', 'id')
                                        ->toArray())
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set): void {
                                        if ($state && $part = SparePart::find($state)) {
                                            $set('price', $part->price);
                                        }
                                    }),

                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),

                                Forms\Components\TextInput::make('price')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),
                            ])
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->columns(3),
                    ]),

                Forms\Components\Textarea::make('edit_note')
                    ->label('Edit Note')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull()
                    ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('maintenance_request_id')
                    ->label('Request #')
                    ->sortable()
                    ->searchable()
                    ->url(fn (Invoice $record): ?string => $record->maintenance_request_id
                        ? MaintenanceRequestResource::getUrl('view', ['record' => $record->maintenance_request_id])
                        : null)
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->state(fn (Invoice $record): string => trim(
                        ($record->maintenanceRequest?->customer?->first_name ?? '') . ' ' .
                        ($record->maintenanceRequest?->customer?->last_name ?? '')
                    ) ?: '-')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('maintenanceRequest.customer', function (Builder $query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),

                Tables\Columns\TextColumn::make('maintenanceRequest.customer.phone')
                    ->label('Customer Phone')
                    ->formatStateUsing(fn (?string $state): ?string => \App\Support\CustomerPhone::display($state))
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('maintenanceRequest.address.district.name_ar')
                    ->label('District')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('technician_name')
                    ->label('Technician')
                    ->state(fn (Invoice $record): string => trim(
                        ($record->maintenanceRequest?->technician?->first_name ?? '') . ' ' .
                        ($record->maintenanceRequest?->technician?->last_name ?? '')
                    ) ?: '-')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('maintenanceRequest.technician', function (Builder $query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('sap_id', 'like', "%{$search}%");
                        });
                    }),

                Tables\Columns\TextColumn::make('maintenanceRequest.technician.sap_id')
                    ->label('Tech SAP ID')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('maintenanceRequest.type')
                    ->label('Maintenance Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => static::formatMaintenanceType($state))
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('total')
                    ->label('Invoice Total')
                    ->money('SAR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoice_type')
                    ->label('Invoice Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'visit_fee' => 'Visit Fee',
                        'final' => 'Final',
                        'workshop' => 'Workshop',
                        'zero_service' => 'Zero Service',
                        default => ucfirst((string) ($state ?: '-')),
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'visit_fee' => 'info',
                        'workshop' => 'warning',
                        'zero_service' => 'gray',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Payment Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?: '-'))
                    ->color(fn (?string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?: '-'))
                    ->placeholder('-')
                    ->searchable(),

                Tables\Columns\TextColumn::make('latest_note')
                    ->label('Notes')
                    ->state(fn (Invoice $record): ?string => collect($record->notes ?? [])->last()['note'] ?? null)
                    ->limit(50)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('maintenanceRequest.sap_sync_status')
                    ->label('SAP Sync Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?: '-'))
                    ->color(fn (?string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        'queued' => 'info',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('maintenanceRequest.sap_sales_order_no')
                    ->label('SAP Sales Order No')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Invoice Date')
                    ->dateTime('Y-m-d h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('created_at')
                    ->label('Invoice Date')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('invoices.created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('invoices.created_at', '<=', $date))),

                SelectFilter::make('payment_method')
                    ->label('Payment Type')
                    ->options([
                        'cash' => 'Cash',
                        'online' => 'Online',
                        'machine' => 'Machine',
                    ]),

                SelectFilter::make('status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                    ]),

                SelectFilter::make('invoice_type')
                    ->label('Invoice Type')
                    ->options([
                        'visit_fee' => 'Visit Fee',
                        'final' => 'Final',
                        'workshop' => 'Workshop',
                        'zero_service' => 'Zero Service',
                    ]),

                SelectFilter::make('sap_sync_status')
                    ->label('SAP Sync Status')
                    ->options([
                        'pending' => 'Pending',
                        'queued' => 'Queued',
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('maintenanceRequest', fn (Builder $query) => $query->where('sap_sync_status', $data['value']))
                        : $query),

                SelectFilter::make('maintenance_type')
                    ->label('Maintenance Type')
                    ->options([
                        'new_installation' => 'New Installation',
                        'regular_maintenance' => 'Regular Maintenance',
                        'emergency_maintenance' => 'Emergency Maintenance',
                        'warranty' => 'Warranty',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('maintenanceRequest', fn (Builder $query) => $query->where('type', $data['value']))
                        : $query),

                SelectFilter::make('district_id')
                    ->label('District')
                    ->options(fn (): array => District::query()->orderBy('name_ar')->pluck('name_ar', 'id')->toArray())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('maintenanceRequest.address', fn (Builder $query) => $query->where('district_id', $data['value']))
                        : $query),

                SelectFilter::make('technician_id')
                    ->label('Technician')
                    ->options(fn (): array => Technician::query()
                        ->orderBy('first_name')
                        ->get()
                        ->mapWithKeys(fn (Technician $technician): array => [
                            $technician->id => trim($technician->first_name . ' ' . $technician->last_name) . ($technician->sap_id ? " ({$technician->sap_id})" : ''),
                        ])
                        ->toArray())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('maintenanceRequest', fn (Builder $query) => $query->where('technician_id', $data['value']))
                        : $query),

                SelectFilter::make('technician_type')
                    ->label('Technician Type')
                    ->options([
                        'employee' => 'Employee',
                        'freelancer' => 'Freelancer',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'employee' => $query->whereHas('maintenanceRequest.technician', fn (Builder $query) => $query->where('is_freelancer', false)),
                        'freelancer' => $query->whereHas('maintenanceRequest.technician', fn (Builder $query) => $query->where('is_freelancer', true)),
                        default => $query,
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('send_to_sap')
                    ->label('Send to SAP')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Send invoice to SAP?')
                    ->modalDescription('This will create a SAP sales order for this maintenance request.')
                    ->visible(fn (Invoice $record): bool => static::canEdit($record)
                        && in_array($record->maintenanceRequest?->sap_sync_status, ['pending', 'failed'], true)
                        && ($record->status === 'completed' || $record->maintenanceRequest?->last_status === 'completed'))
                    ->action(function (Invoice $record): void {
                        $record->loadMissing([
                            'maintenanceRequest.invoice',
                            'maintenanceRequest.invoice.services',
                            'maintenanceRequest.invoice.spareParts',
                            'maintenanceRequest.customer',
                            'maintenanceRequest.technician',
                            'maintenanceRequest.address.city',
                            'maintenanceRequest.address.district',
                        ]);

                        $result = app(SapController::class)->createSalesOrder(
                            $record->maintenanceRequest,
                            static::formatPaymentMethodForSap((string) $record->payment_method),
                        );

                        $success = (bool) ($result['success'] ?? false);

                        Notification::make()
                            ->title($success ? 'Sent to SAP successfully' : 'SAP sync failed')
                            ->body($result['sap_desc'] ?? $result['message'] ?? null)
                            ->color($success ? 'success' : 'danger')
                            ->send();
                    }),
                Tables\Actions\Action::make('print_invoice')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Invoice $record): string => route('admin.sales-invoices.print', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesInvoices::route('/'),
            'edit' => Pages\EditSalesInvoice::route('/{record}/edit'),
        ];
    }

    public static function getTableQuery(): Builder
    {
        return Invoice::query()
            ->with([
                'maintenanceRequest.customer',
                'maintenanceRequest.address.district',
                'maintenanceRequest.technician',
            ]);
    }

    public static function formatMaintenanceType(?string $state): string
    {
        return match ($state) {
            'new_installation' => 'New Installation',
            'regular_maintenance' => 'Regular Maintenance',
            'emergency_maintenance' => 'Emergency Maintenance',
            'warranty' => 'Warranty',
            default => $state ?: '-',
        };
    }

    public static function paymentMethodOptions(): array
    {
        return collect(Setting::paymentMethods())
            ->filter(fn (array $method): bool => (bool) ($method['is_active'] ?? false))
            ->mapWithKeys(fn (array $method): array => [
                $method['code'] => $method['label_en'] ?? $method['code'],
            ])
            ->toArray();
    }

    public static function formatPaymentMethodForSap(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'online' => 'Online',
            'machine' => 'Machine',
            'cash' => 'Cash',
            'remittance' => 'Remittance',
            default => ucfirst($paymentMethod),
        };
    }
}
