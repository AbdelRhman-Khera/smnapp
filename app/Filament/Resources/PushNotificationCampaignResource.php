<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PushNotificationCampaignResource\Pages;
use App\Models\Customer;
use App\Models\PushNotificationCampaign;
use App\Models\Technician;
use App\Services\PushNotificationCampaignService;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PushNotificationCampaignResource extends Resource
{
    protected static ?string $model = PushNotificationCampaign::class;

    protected static ?string $navigationGroup = 'System';
    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationLabel = 'Push Notifications';
    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Audience')
                ->schema([
                    Select::make('audience')
                        ->options([
                            'customer' => 'Customer App',
                            'technician' => 'Technician App',
                        ])
                        ->live()
                        ->required(),
                    Select::make('recipient_scope')
                        ->label('Recipients')
                        ->options([
                            'all' => 'All users with an active device token',
                            'selected' => 'Selected users only',
                        ])
                        ->live()
                        ->default('all')
                        ->required(),
                    Select::make('recipient_ids')
                        ->label('Selected Users')
                        ->multiple()
                        ->searchable()
                        ->options(fn (Forms\Get $get): array => match ($get('audience')) {
                            'technician' => Technician::query()
                                ->orderBy('first_name')
                                ->get()
                                ->mapWithKeys(fn (Technician $technician): array => [
                                    $technician->id => trim($technician->first_name . ' ' . $technician->last_name) . ' - ' . $technician->phone,
                                ])
                                ->all(),
                            'customer' => Customer::query()
                                ->orderBy('first_name')
                                ->get()
                                ->mapWithKeys(fn (Customer $customer): array => [
                                    $customer->id => \App\Support\CustomerPhone::optionLabel($customer),
                                ])
                                ->all(),
                            default => [],
                        })
                        ->visible(fn (Forms\Get $get): bool => $get('recipient_scope') === 'selected')
                        ->required(fn (Forms\Get $get): bool => $get('recipient_scope') === 'selected'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Arabic Content')
                ->schema([
                    TextInput::make('title_ar')->label('Title (AR)')->required()->maxLength(255),
                    Textarea::make('body_ar')->label('Message (AR)')->required()->rows(4)->columnSpanFull(),
                ]),

            Forms\Components\Section::make('English Content')
                ->schema([
                    TextInput::make('title_en')->label('Title (EN)')->required()->maxLength(255),
                    Textarea::make('body_en')->label('Message (EN)')->required()->rows(4)->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Optional App Action')
                ->schema([
                    TextInput::make('deep_link')
                        ->label('Deep Link')
                        ->placeholder('app://maintenance-request/123')
                        ->helperText('The mobile app can open this screen when the user taps the notification.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('15s')
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('audience')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'technician' ? 'Technician App' : 'Customer App')
                    ->color(fn (string $state): string => $state === 'technician' ? 'info' : 'success'),
                TextColumn::make('recipient_scope')
                    ->label('Recipients')
                    ->formatStateUsing(fn (string $state): string => $state === 'all' ? 'All users' : 'Selected users'),
                TextColumn::make('title_en')->label('Title')->searchable()->limit(40),
                TextColumn::make('send_count')->label('Sends')->sortable(),
                TextColumn::make('last_targeted_count')->label('Last Targeted')->sortable(),
                TextColumn::make('last_success_count')->label('Last Sent')->sortable(),
                TextColumn::make('last_failed_count')
                    ->label('Last Failed')
                    ->color(fn ($state): string => (int) $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('last_sent_at')->label('Last Sent At')->dateTime()->sortable(),
                TextColumn::make('createdBy.name')->label('Created By')->placeholder('-'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('audience')->options([
                    'customer' => 'Customer App',
                    'technician' => 'Technician App',
                ]),
                SelectFilter::make('recipient_scope')->options([
                    'all' => 'All users',
                    'selected' => 'Selected users',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('send')
                    ->label(fn (PushNotificationCampaign $record): string => $record->send_count > 0 ? 'Send Again' : 'Send')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Send push notification?')
                    ->modalDescription('The message will be queued and sent to the selected audience in the background. Counters update as sending progresses.')
                    ->action(function (PushNotificationCampaign $record): void {
                        $result = app(PushNotificationCampaignService::class)->queue($record);

                        Notification::make()
                            ->title('Push notification queued')
                            ->body("Targeted: {$result['targeted']} recipients in {$result['jobs']} batches. Sending runs in the background — the Sent/Failed counters will update as it progresses.")
                            ->color('success')
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPushNotificationCampaigns::route('/'),
            'create' => Pages\CreatePushNotificationCampaign::route('/create'),
            'edit' => Pages\EditPushNotificationCampaign::route('/{record}/edit'),
        ];
    }
}
