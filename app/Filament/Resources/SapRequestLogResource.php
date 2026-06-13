<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SapRequestLogResource\Pages;
use App\Http\Controllers\SapController;
use App\Models\SapRequestLog;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SapRequestLogResource extends Resource
{
    protected static ?string $model = SapRequestLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'SAP Request Logs';

    protected static ?int $navigationSort = 7;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                SapRequestLog::query()
                    ->with([
                        'maintenanceRequest.customer',
                        'maintenanceRequest.technician',
                        'maintenanceRequest.invoice',
                        'creator',
                    ])
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('maintenance_request_id')
                    ->label('Request ID')
                    ->sortable()
                    ->searchable()
                    ->url(fn (SapRequestLog $record): ?string => $record->maintenance_request_id
                        ? MaintenanceRequestResource::getUrl('view', ['record' => $record->maintenance_request_id])
                        : null),

                Tables\Columns\TextColumn::make('maintenanceRequest.customer.phone')
                    ->label('Customer Phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('maintenanceRequest.type')
                    ->label('Request Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'new_installation' => 'New Installation',
                        'regular_maintenance' => 'Regular Maintenance',
                        'emergency_maintenance' => 'Emergency Maintenance',
                        default => $state ?: '-',
                    })
                    ->placeholder('-')
                    ->searchable(),

                Tables\Columns\TextColumn::make('maintenanceRequest.invoice.total')
                    ->label('Amount')
                    ->money('SAR')
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('maintenanceRequest.technician.sap_id')
                    ->label('Technician SAP ID')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment')
                    ->badge()
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('http_status')
                    ->label('HTTP')
                    ->badge()
                    ->placeholder('-')
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 200 && $state < 300 => 'success',
                        $state >= 400 => 'danger',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('sap_status')
                    ->label('SAP')
                    ->badge()
                    ->placeholder('-')
                    ->color(fn (?string $state): string => match ($state) {
                        'S' => 'success',
                        'E' => 'danger',
                        'initiated' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_success')
                    ->label('Success')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sap_desc')
                    ->label('SAP Desc')
                    ->limit(50)
                    ->placeholder('-')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('Y-m-d h:i A')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_success')
                    ->label('Success'),

                Tables\Filters\SelectFilter::make('sap_status')
                    ->label('SAP Status')
                    ->options([
                        'initiated' => 'Initiated',
                        'S' => 'Success',
                        'E' => 'Error',
                        'failed' => 'Failed',
                    ]),

                Tables\Filters\SelectFilter::make('action')
                    ->options(fn (): array => SapRequestLog::query()
                        ->whereNotNull('action')
                        ->distinct()
                        ->pluck('action', 'action')
                        ->toArray()),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Created From'),
                        DatePicker::make('created_until')
                            ->label('Created Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('resend')
                    ->label('Resend')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Resend SAP request?')
                    ->modalDescription('This will send the related maintenance request to SAP again.')
                    ->visible(fn (SapRequestLog $record): bool => static::canResend($record))
                    ->action(fn (SapRequestLog $record) => static::resend($record)),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Log Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id'),

                                TextEntry::make('maintenance_request_id')
                                    ->label('Maintenance Request ID')
                                    ->placeholder('-')
                                    ->url(fn (SapRequestLog $record): ?string => $record->maintenance_request_id
                                        ? MaintenanceRequestResource::getUrl('view', ['record' => $record->maintenance_request_id])
                                        : null),

                                TextEntry::make('action')
                                    ->badge(),

                                TextEntry::make('payment_method')
                                    ->label('Payment Method')
                                    ->placeholder('-'),

                                TextEntry::make('http_method')
                                    ->label('HTTP Method')
                                    ->placeholder('-'),

                                TextEntry::make('http_status')
                                    ->label('HTTP Status')
                                    ->badge()
                                    ->placeholder('-'),

                                TextEntry::make('sap_status')
                                    ->label('SAP Status')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'S' => 'success',
                                        'E' => 'danger',
                                        'initiated' => 'warning',
                                        'failed' => 'danger',
                                        default => 'gray',
                                    })
                                    ->placeholder('-'),

                                TextEntry::make('is_success')
                                    ->label('Success')
                                    ->badge()
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')
                                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),

                                TextEntry::make('created_at')
                                    ->dateTime('Y-m-d h:i A'),
                            ]),

                        TextEntry::make('url')
                            ->label('URL')
                            ->copyable()
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('sap_desc')
                            ->label('SAP Description')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('error_message')
                            ->label('Error Message')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Maintenance Request')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('maintenanceRequest.customer.phone')
                                    ->label('Customer Phone')
                                    ->placeholder('-'),

                                TextEntry::make('maintenanceRequest.technician.first_name')
                                    ->label('Technician')
                                    ->formatStateUsing(fn (SapRequestLog $record): string => trim(
                                        ($record->maintenanceRequest?->technician?->first_name ?? '') . ' ' .
                                        ($record->maintenanceRequest?->technician?->last_name ?? '')
                                    ) ?: '-'),

                                TextEntry::make('creator.name')
                                    ->label('Created By')
                                    ->placeholder('-'),
                            ]),
                    ])
                    ->collapsed(),

                Section::make('Request Payload')
                    ->schema([
                        TextEntry::make('request_payload')
                            ->state(fn (SapRequestLog $record): string => static::formatJsonState($record->request_payload))
                            ->fontFamily('mono')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                Section::make('Response Body')
                    ->schema([
                        TextEntry::make('response_body')
                            ->state(fn (SapRequestLog $record): string => static::formatJsonState($record->response_body))
                            ->fontFamily('mono')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function canResend(SapRequestLog $record): bool
    {
        return ! $record->is_success
            && $record->action === 'create_sales_order'
            && $record->maintenanceRequest !== null;
    }

    public static function resend(SapRequestLog $record): void
    {
        $paymentMethod = $record->payment_method
            ?: data_get($record->request_payload, 'PAYMENT_METHOD')
            ?: 'Cash';

        $maintenanceRequest = $record->maintenanceRequest;

        $result = app(SapController::class)->createSalesOrder(
            $maintenanceRequest,
            $paymentMethod,
        );

        $success = (bool) ($result['success'] ?? false);
        $newLogId = $result['sap_request_log_id'] ?? null;

        if ($newLogId) {
            SapRequestLog::query()
                ->where('maintenance_request_id', $record->maintenance_request_id)
                ->whereKeyNot($newLogId)
                ->delete();
        }

        Notification::make()
            ->title($success ? 'SAP request resent successfully' : 'SAP resend failed')
            ->body($result['sap_desc'] ?? $result['message'] ?? null)
            ->color($success ? 'success' : 'danger')
            ->send();
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSapRequestLogs::route('/'),
            'view' => Pages\ViewSapRequestLog::route('/{record}'),
        ];
    }
}
