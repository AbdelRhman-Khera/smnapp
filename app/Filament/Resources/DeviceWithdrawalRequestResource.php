<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeviceWithdrawalRequestResource\Pages;
use App\Models\Branch;
use App\Models\DeviceWithdrawalRequest;
use App\Models\MaintenanceRequest;
use App\Models\Service;
use App\Models\SparePart;
use App\Models\Technician;
use App\Services\NotificationService;
use Filament\Actions as PageActions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DeviceWithdrawalRequestResource extends Resource
{
    protected static ?string $model = DeviceWithdrawalRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box-arrow-down';

    protected static ?string $navigationGroup = 'Maintenance Management';

    protected static ?string $navigationLabel = 'Device Withdrawals';

    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Withdrawal Details')
                ->schema([
                    Forms\Components\Select::make('branch_id')
                        ->label('Branch')
                        ->relationship('branch', 'name_en')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->options(DeviceWithdrawalRequest::statuses())
                        ->required(),
                    Forms\Components\Textarea::make('branch_notes')
                        ->rows(3),
                    Forms\Components\Textarea::make('workshop_notes')
                        ->rows(3),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::scopedQuery())
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('maintenance_request_id')
                    ->label('Original Request')
                    ->url(fn (DeviceWithdrawalRequest $record): string => MaintenanceRequestResource::getUrl('view', ['record' => $record->maintenance_request_id]))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('follow_up_maintenance_request_id')
                    ->label('Follow-up Request')
                    ->placeholder('-')
                    ->url(fn (DeviceWithdrawalRequest $record): ?string => $record->follow_up_maintenance_request_id
                        ? MaintenanceRequestResource::getUrl('view', ['record' => $record->follow_up_maintenance_request_id])
                        : null)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.first_name')
                    ->label('Customer')
                    ->formatStateUsing(fn ($record) => trim(($record->customer?->first_name ?? '') . ' ' . ($record->customer?->last_name ?? '')) ?: '-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('technician.first_name')
                    ->label('Technician')
                    ->formatStateUsing(fn ($record) => trim(($record->technician?->first_name ?? '') . ' ' . ($record->technician?->last_name ?? '')) ?: '-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('handoffTechnician.first_name')
                    ->label('Delivery Technician')
                    ->formatStateUsing(fn ($record) => trim(($record->handoffTechnician?->first_name ?? '') . ' ' . ($record->handoffTechnician?->last_name ?? '')) ?: '-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('branch.name_en')
                    ->label('Branch')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => DeviceWithdrawalRequest::statuses()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        DeviceWithdrawalRequest::STATUS_PENDING_CUSTOMER_APPROVAL => 'warning',
                        DeviceWithdrawalRequest::STATUS_APPROVED_BY_CUSTOMER => 'info',
                        DeviceWithdrawalRequest::STATUS_REJECTED_BY_CUSTOMER => 'danger',
                        DeviceWithdrawalRequest::STATUS_ASSIGNED_TO_DELIVERY_TECHNICIAN => 'warning',
                        DeviceWithdrawalRequest::STATUS_RECEIVED_BY_DELIVERY_TECHNICIAN => 'info',
                        DeviceWithdrawalRequest::STATUS_DELIVERED_TO_BRANCH => 'primary',
                        DeviceWithdrawalRequest::STATUS_RECEIVED_BY_BRANCH => 'info',
                        DeviceWithdrawalRequest::STATUS_UNDER_REPAIR => 'warning',
                        DeviceWithdrawalRequest::STATUS_REPAIR_COMPLETED => 'success',
                        DeviceWithdrawalRequest::STATUS_FOLLOW_UP_REQUEST_CREATED => 'success',
                        DeviceWithdrawalRequest::STATUS_DELIVERED_TO_CUSTOMER => 'info',
                        DeviceWithdrawalRequest::STATUS_COMPLETED => 'success',
                        DeviceWithdrawalRequest::STATUS_CANCELED => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Devices'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('Y-m-d h:i A')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(DeviceWithdrawalRequest::statuses()),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name_en')
                    ->label('Branch'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                static::assignDeliveryTechnicianAction(),
                static::receiveAtBranchAction(),
                static::startRepairAction(),
                static::completeRepairAction(),
                static::createFollowUpRequestAction(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Request Information')
                ->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('id'),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => DeviceWithdrawalRequest::statuses()[$state] ?? $state),
                        TextEntry::make('maintenance_request_id')
                            ->label('Original Request')
                            ->url(fn (DeviceWithdrawalRequest $record): string => MaintenanceRequestResource::getUrl('view', ['record' => $record->maintenance_request_id])),
                        TextEntry::make('follow_up_maintenance_request_id')
                            ->label('Follow-up Request')
                            ->url(fn (DeviceWithdrawalRequest $record): ?string => $record->follow_up_maintenance_request_id
                                ? MaintenanceRequestResource::getUrl('view', ['record' => $record->follow_up_maintenance_request_id])
                                : null)
                            ->placeholder('-'),
                        TextEntry::make('branch.name_en')
                            ->label('Branch'),
                        TextEntry::make('handoffTechnician.first_name')
                            ->label('Delivery Technician')
                            ->formatStateUsing(fn ($record) => trim(($record->handoffTechnician?->first_name ?? '') . ' ' . ($record->handoffTechnician?->last_name ?? '')) ?: '-'),
                        TextEntry::make('receivedBy.name')
                            ->label('Received By')
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->dateTime('Y-m-d h:i A'),
                        TextEntry::make('customer_decision_at')
                            ->dateTime('Y-m-d h:i A')
                            ->placeholder('-'),
                    ]),
                    TextEntry::make('technician_notes')->placeholder('-')->columnSpanFull(),
                    TextEntry::make('handoff_notes')->placeholder('-')->columnSpanFull(),
                    TextEntry::make('branch_notes')->placeholder('-')->columnSpanFull(),
                    TextEntry::make('workshop_notes')->placeholder('-')->columnSpanFull(),
                ]),
            Section::make('Withdrawn Devices')
                ->schema([
                    RepeatableEntry::make('items')
                        ->schema([
                            Grid::make(4)->schema([
                                TextEntry::make('product.name_en')
                                    ->label('Product'),
                                TextEntry::make('serial_number')
                                    ->placeholder('-'),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('notes')
                                    ->placeholder('-'),
                            ]),
                            ImageEntry::make('photos')
                                ->height(90)
                                ->square()
                                ->stacked(),
                        ])
                        ->contained(false),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeviceWithdrawalRequests::route('/'),
            'view' => Pages\ViewDeviceWithdrawalRequest::route('/{record}'),
        ];
    }

    protected static function scopedQuery(): Builder
    {
        $query = DeviceWithdrawalRequest::query()
            ->with([
                'branch',
                'customer',
                'technician',
                'handoffTechnician',
                'items.product',
                'followUpMaintenanceRequest',
            ]);

        $user = auth()->user();

        if (
            $user?->branch_id
            && ! $user->hasRole(['super_admin', 'Super Admin'])
        ) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }

    protected static function receiveAtBranchAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('receive_at_branch')
            ->label('Receive')
            ->icon('heroicon-o-check-circle')
            ->color('info')
            ->visible(fn (DeviceWithdrawalRequest $record): bool =>
                $record->status === DeviceWithdrawalRequest::STATUS_DELIVERED_TO_BRANCH
                && static::canManageBranch($record)
            )
            ->form([
                Forms\Components\Textarea::make('branch_notes')
                    ->label('Branch Notes')
                    ->rows(3),
            ])
            ->action(function (DeviceWithdrawalRequest $record, array $data): void {
                $record->update([
                    'status' => DeviceWithdrawalRequest::STATUS_RECEIVED_BY_BRANCH,
                    'received_by_user_id' => auth()->id(),
                    'received_by_branch_at' => now(),
                    'branch_notes' => $data['branch_notes'] ?? $record->branch_notes,
                ]);

                $record->items()->update(['status' => DeviceWithdrawalRequest::STATUS_RECEIVED_BY_BRANCH]);

                Notification::make()
                    ->title('Device withdrawal received at branch')
                    ->success()
                    ->send();
            });
    }

    protected static function assignDeliveryTechnicianAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('assign_delivery_technician')
            ->label('Assign Delivery Tech')
            ->icon('heroicon-o-user-plus')
            ->color('primary')
            ->visible(fn (DeviceWithdrawalRequest $record): bool =>
                $record->status === DeviceWithdrawalRequest::STATUS_APPROVED_BY_CUSTOMER
                && static::canManageBranch($record)
            )
            ->form([
                Forms\Components\Select::make('technician_id')
                    ->label('Delivery Technician')
                    ->options(fn (DeviceWithdrawalRequest $record): array =>
                        Technician::query()
                            ->where('authorized', true)
                            ->where('activated', true)
                            ->whereKeyNot($record->technician_id)
                            ->orderBy('first_name')
                            ->orderBy('last_name')
                            ->get()
                            ->mapWithKeys(fn (Technician $technician): array => [
                                $technician->id => trim($technician->first_name . ' ' . $technician->last_name),
                            ])
                            ->all()
                    )
                    ->searchable()
                    ->required(),
                Forms\Components\Textarea::make('handoff_notes')
                    ->label('Handoff Notes')
                    ->rows(3),
            ])
            ->action(function (DeviceWithdrawalRequest $record, array $data): void {
                $record->update([
                    'handoff_technician_id' => $data['technician_id'],
                    'handoff_notes' => $data['handoff_notes'] ?? null,
                    'status' => DeviceWithdrawalRequest::STATUS_ASSIGNED_TO_DELIVERY_TECHNICIAN,
                    'assigned_to_handoff_technician_at' => now(),
                ]);

                $record->items()->update([
                    'status' => DeviceWithdrawalRequest::STATUS_ASSIGNED_TO_DELIVERY_TECHNICIAN,
                ]);

                NotificationService::notifyTechnicianTranslated(
                    $data['technician_id'],
                    'notifications.technician.device_withdrawal_assigned_by_branch',
                    ['id' => $record->id],
                    $record->maintenance_request_id
                );

                Notification::make()
                    ->title('Delivery technician assigned')
                    ->success()
                    ->send();
            });
    }

    protected static function startRepairAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('start_repair')
            ->label('Start Repair')
            ->icon('heroicon-o-wrench-screwdriver')
            ->color('warning')
            ->visible(fn (DeviceWithdrawalRequest $record): bool =>
                $record->status === DeviceWithdrawalRequest::STATUS_RECEIVED_BY_BRANCH
                && static::canManageBranch($record)
            )
            ->action(function (DeviceWithdrawalRequest $record): void {
                $record->update([
                    'status' => DeviceWithdrawalRequest::STATUS_UNDER_REPAIR,
                    'repair_started_at' => now(),
                ]);

                $record->items()->update(['status' => DeviceWithdrawalRequest::STATUS_UNDER_REPAIR]);

                Notification::make()
                    ->title('Repair started')
                    ->success()
                    ->send();
            });
    }

    protected static function completeRepairAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('complete_repair')
            ->label('Complete Repair')
            ->icon('heroicon-o-clipboard-document-check')
            ->color('success')
            ->visible(fn (DeviceWithdrawalRequest $record): bool =>
                $record->status === DeviceWithdrawalRequest::STATUS_UNDER_REPAIR
                && static::canManageBranch($record)
            )
            ->form([
                Forms\Components\Textarea::make('workshop_notes')
                    ->label('Workshop Notes')
                    ->rows(3),
            ])
            ->action(function (DeviceWithdrawalRequest $record, array $data): void {
                $record->update([
                    'status' => DeviceWithdrawalRequest::STATUS_REPAIR_COMPLETED,
                    'repair_completed_at' => now(),
                    'workshop_notes' => $data['workshop_notes'] ?? $record->workshop_notes,
                ]);

                $record->items()->update(['status' => DeviceWithdrawalRequest::STATUS_REPAIR_COMPLETED]);

                Notification::make()
                    ->title('Repair completed')
                    ->success()
                    ->send();
            });
    }

    protected static function createFollowUpRequestAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('create_follow_up_request')
            ->label('Create Follow-up')
            ->icon('heroicon-o-document-plus')
            ->color('primary')
            ->visible(fn (DeviceWithdrawalRequest $record): bool =>
                $record->status === DeviceWithdrawalRequest::STATUS_REPAIR_COMPLETED
                && blank($record->follow_up_maintenance_request_id)
                && static::canManageBranch($record)
            )
            ->form(static::followUpRequestForm())
            ->action(function (DeviceWithdrawalRequest $record, array $data): void {
                $followUp = static::createFollowUpRequest($record, $data);

                NotificationService::notifyCustomerTranslated(
                    $record->customer_id,
                    'notifications.customer.device_withdrawal_follow_up_created',
                    ['id' => $followUp->id],
                    $followUp->id
                );

                Notification::make()
                    ->title('Follow-up maintenance request created')
                    ->success()
                    ->send();
            });
    }

    public static function assignDeliveryTechnicianPageAction(): PageActions\Action
    {
        return PageActions\Action::make('assign_delivery_technician')
            ->label('Assign Delivery Tech')
            ->icon('heroicon-o-user-plus')
            ->color('primary')
            ->visible(fn (DeviceWithdrawalRequest $record): bool =>
                $record->status === DeviceWithdrawalRequest::STATUS_APPROVED_BY_CUSTOMER
                && static::canManageBranch($record)
            )
            ->form([
                Forms\Components\Select::make('technician_id')
                    ->label('Delivery Technician')
                    ->options(fn (DeviceWithdrawalRequest $record): array =>
                        Technician::query()
                            ->where('authorized', true)
                            ->where('activated', true)
                            ->whereKeyNot($record->technician_id)
                            ->orderBy('first_name')
                            ->orderBy('last_name')
                            ->get()
                            ->mapWithKeys(fn (Technician $technician): array => [
                                $technician->id => trim($technician->first_name . ' ' . $technician->last_name),
                            ])
                            ->all()
                    )
                    ->searchable()
                    ->required(),
                Forms\Components\Textarea::make('handoff_notes')
                    ->label('Handoff Notes')
                    ->rows(3),
            ])
            ->action(function (DeviceWithdrawalRequest $record, array $data): void {
                $record->update([
                    'handoff_technician_id' => $data['technician_id'],
                    'handoff_notes' => $data['handoff_notes'] ?? null,
                    'status' => DeviceWithdrawalRequest::STATUS_ASSIGNED_TO_DELIVERY_TECHNICIAN,
                    'assigned_to_handoff_technician_at' => now(),
                ]);

                $record->items()->update([
                    'status' => DeviceWithdrawalRequest::STATUS_ASSIGNED_TO_DELIVERY_TECHNICIAN,
                ]);

                NotificationService::notifyTechnicianTranslated(
                    $data['technician_id'],
                    'notifications.technician.device_withdrawal_assigned_by_branch',
                    ['id' => $record->id],
                    $record->maintenance_request_id
                );

                Notification::make()->title('Delivery technician assigned')->success()->send();
            });
    }

    public static function receiveAtBranchPageAction(): PageActions\Action
    {
        return PageActions\Action::make('receive_at_branch')
            ->label('Receive')
            ->icon('heroicon-o-check-circle')
            ->color('info')
            ->visible(fn (DeviceWithdrawalRequest $record): bool =>
                $record->status === DeviceWithdrawalRequest::STATUS_DELIVERED_TO_BRANCH
                && static::canManageBranch($record)
            )
            ->form([
                Forms\Components\Textarea::make('branch_notes')
                    ->label('Branch Notes')
                    ->rows(3),
            ])
            ->action(function (DeviceWithdrawalRequest $record, array $data): void {
                $record->update([
                    'status' => DeviceWithdrawalRequest::STATUS_RECEIVED_BY_BRANCH,
                    'received_by_user_id' => auth()->id(),
                    'received_by_branch_at' => now(),
                    'branch_notes' => $data['branch_notes'] ?? $record->branch_notes,
                ]);

                $record->items()->update(['status' => DeviceWithdrawalRequest::STATUS_RECEIVED_BY_BRANCH]);

                Notification::make()->title('Device withdrawal received at branch')->success()->send();
            });
    }

    public static function startRepairPageAction(): PageActions\Action
    {
        return PageActions\Action::make('start_repair')
            ->label('Start Repair')
            ->icon('heroicon-o-wrench-screwdriver')
            ->color('warning')
            ->visible(fn (DeviceWithdrawalRequest $record): bool =>
                $record->status === DeviceWithdrawalRequest::STATUS_RECEIVED_BY_BRANCH
                && static::canManageBranch($record)
            )
            ->action(function (DeviceWithdrawalRequest $record): void {
                $record->update([
                    'status' => DeviceWithdrawalRequest::STATUS_UNDER_REPAIR,
                    'repair_started_at' => now(),
                ]);

                $record->items()->update(['status' => DeviceWithdrawalRequest::STATUS_UNDER_REPAIR]);

                Notification::make()->title('Repair started')->success()->send();
            });
    }

    public static function completeRepairPageAction(): PageActions\Action
    {
        return PageActions\Action::make('complete_repair')
            ->label('Complete Repair')
            ->icon('heroicon-o-clipboard-document-check')
            ->color('success')
            ->visible(fn (DeviceWithdrawalRequest $record): bool =>
                $record->status === DeviceWithdrawalRequest::STATUS_UNDER_REPAIR
                && static::canManageBranch($record)
            )
            ->form([
                Forms\Components\Textarea::make('workshop_notes')
                    ->label('Workshop Notes')
                    ->rows(3),
            ])
            ->action(function (DeviceWithdrawalRequest $record, array $data): void {
                $record->update([
                    'status' => DeviceWithdrawalRequest::STATUS_REPAIR_COMPLETED,
                    'repair_completed_at' => now(),
                    'workshop_notes' => $data['workshop_notes'] ?? $record->workshop_notes,
                ]);

                $record->items()->update(['status' => DeviceWithdrawalRequest::STATUS_REPAIR_COMPLETED]);

                Notification::make()->title('Repair completed')->success()->send();
            });
    }

    public static function createFollowUpRequestPageAction(): PageActions\Action
    {
        return PageActions\Action::make('create_follow_up_request')
            ->label('Create Follow-up')
            ->icon('heroicon-o-document-plus')
            ->color('primary')
            ->visible(fn (DeviceWithdrawalRequest $record): bool =>
                $record->status === DeviceWithdrawalRequest::STATUS_REPAIR_COMPLETED
                && blank($record->follow_up_maintenance_request_id)
                && static::canManageBranch($record)
            )
            ->form(static::followUpRequestForm())
            ->action(function (DeviceWithdrawalRequest $record, array $data): void {
                $followUp = static::createFollowUpRequest($record, $data);

                NotificationService::notifyCustomerTranslated(
                    $record->customer_id,
                    'notifications.customer.device_withdrawal_follow_up_created',
                    ['id' => $followUp->id],
                    $followUp->id
                );

                Notification::make()->title('Follow-up maintenance request created')->success()->send();
            });
    }

    protected static function followUpRequestForm(): array
    {
        return [
            Forms\Components\Select::make('follow_up_kind')
                ->label('Follow-up Type')
                ->options([
                    'paid' => 'Regular request with invoice',
                    'warranty' => 'Warranty request without invoice',
                ])
                ->default('paid')
                ->live()
                ->required(),
            Forms\Components\Textarea::make('problem_description')
                ->label('Follow-up Request Description')
                ->default('Workshop repaired device return and installation.')
                ->required()
                ->rows(3)
                ->columnSpanFull(),
            Forms\Components\Repeater::make('services_items')
                ->label('Services')
                ->schema([
                    Forms\Components\Select::make('service_id')
                        ->label('Service')
                        ->options(fn (): array => Service::query()
                            ->active()
                            ->orderBy('name_en')
                            ->get()
                            ->mapWithKeys(fn (Service $service): array => [
                                $service->id => ($service->name_en ?: $service->name_ar) . ' - ' . number_format((float) $service->price, 2) . ' SAR',
                            ])
                            ->all())
                        ->searchable()
                        ->required(),
                ])
                ->defaultItems(0)
                ->addActionLabel('Add Service')
                ->visible(fn (Forms\Get $get): bool => $get('follow_up_kind') === 'paid')
                ->columns(1)
                ->columnSpanFull(),
            Forms\Components\Repeater::make('spare_parts_items')
                ->label('Spare Parts')
                ->schema([
                    Forms\Components\Select::make('spare_part_id')
                        ->label('Spare Part')
                        ->options(fn (): array => SparePart::query()
                            ->active()
                            ->orderBy('name_en')
                            ->get()
                            ->mapWithKeys(fn (SparePart $part): array => [
                                $part->id => ($part->name_en ?: $part->name_ar) . ' - ' . number_format((float) $part->price, 2) . ' SAR',
                            ])
                            ->all())
                        ->searchable()
                        ->required(),
                    Forms\Components\TextInput::make('quantity')
                        ->label('Qty')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required(),
                    Forms\Components\TextInput::make('price')
                        ->label('Unit Price')
                        ->numeric()
                        ->minValue(0)
                        ->helperText('Leave empty to use the spare part default price.'),
                ])
                ->addActionLabel('Add Spare Part')
                ->visible(fn (Forms\Get $get): bool => $get('follow_up_kind') === 'paid')
                ->columns(3)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('invoice_notes')
                ->label('Invoice Notes')
                ->rows(3)
                ->visible(fn (Forms\Get $get): bool => $get('follow_up_kind') === 'paid')
                ->columnSpanFull(),
        ];
    }

    protected static function createFollowUpRequest(DeviceWithdrawalRequest $record, array $data): MaintenanceRequest
    {
        return DB::transaction(function () use ($record, $data): MaintenanceRequest {
            $record->loadMissing(['maintenanceRequest', 'items.product']);

            $isWarranty = ($data['follow_up_kind'] ?? 'paid') === 'warranty';
            $status = $isWarranty ? 'pending' : 'waiting_for_payment';

            $followUp = MaintenanceRequest::create([
                'customer_id' => $record->customer_id,
                'type' => $isWarranty ? 'warranty' : 'regular_maintenance',
                'warranty_source_request_id' => $isWarranty ? $record->maintenance_request_id : null,
                'address_id' => $record->maintenanceRequest?->address_id,
                'problem_description' => $data['problem_description'],
                'last_status' => $status,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $sync = $record->items
                ->pluck('product_id')
                ->unique()
                ->mapWithKeys(fn ($productId): array => [$productId => ['quantity' => 1]])
                ->all();

            $followUp->products()->sync($sync);
            $followUp->recalculateHours();

            $followUp->statuses()->create([
                'status' => $status,
                'notes' => $isWarranty
                    ? 'Workshop warranty follow-up created from device withdrawal request #' . $record->id
                    : 'Workshop repair invoice created from device withdrawal request #' . $record->id,
            ]);

            if (! $isWarranty) {
                $invoice = $followUp->invoices()->create([
                    'invoice_type' => 'workshop',
                    'total' => static::calculateFollowUpInvoiceTotal($data),
                    'status' => 'pending',
                    'notes' => [[
                        'note' => $data['invoice_notes'] ?? null,
                        'source' => 'device_withdrawal_request',
                        'device_withdrawal_request_id' => $record->id,
                        'created_at' => now()->toDateTimeString(),
                    ]],
                ]);

                $invoice->services()->sync(
                    collect($data['services_items'] ?? [])
                        ->pluck('service_id')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all()
                );

                $invoice->spareParts()->sync(
                    collect($data['spare_parts_items'] ?? [])
                        ->filter(fn (array $item): bool => filled($item['spare_part_id'] ?? null))
                        ->mapWithKeys(function (array $item): array {
                            $part = SparePart::find($item['spare_part_id']);
                            $price = filled($item['price'] ?? null)
                                ? (float) $item['price']
                                : (float) ($part?->price ?? 0);

                            return [
                                $item['spare_part_id'] => [
                                    'quantity' => (int) ($item['quantity'] ?? 1),
                                    'price' => $price,
                                ],
                            ];
                        })
                        ->all()
                );

                $followUp->update(['invoice_number' => $invoice->id]);
            }

            $record->update([
                'status' => DeviceWithdrawalRequest::STATUS_FOLLOW_UP_REQUEST_CREATED,
                'follow_up_maintenance_request_id' => $followUp->id,
            ]);

            $record->items()->update(['status' => DeviceWithdrawalRequest::STATUS_FOLLOW_UP_REQUEST_CREATED]);

            return $followUp;
        });
    }

    protected static function calculateFollowUpInvoiceTotal(array $data): float
    {
        $servicesTotal = Service::query()
            ->whereIn(
                'id',
                collect($data['services_items'] ?? [])
                    ->pluck('service_id')
                    ->filter()
                    ->unique()
                    ->all()
            )
            ->sum('price');

        $sparePartsTotal = collect($data['spare_parts_items'] ?? [])
            ->filter(fn (array $item): bool => filled($item['spare_part_id'] ?? null))
            ->sum(function (array $item): float {
                $part = SparePart::find($item['spare_part_id']);
                $price = filled($item['price'] ?? null)
                    ? (float) $item['price']
                    : (float) ($part?->price ?? 0);
                $quantity = (int) ($item['quantity'] ?? 1);

                return $price * max(1, $quantity);
            });

        return (float) $servicesTotal + (float) $sparePartsTotal;
    }

    protected static function canManageBranch(DeviceWithdrawalRequest $record): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole(['super_admin', 'Super Admin'])) {
            return true;
        }

        return filled($record->branch_id)
            && (int) $user->branch_id === (int) $record->branch_id;
    }
}
