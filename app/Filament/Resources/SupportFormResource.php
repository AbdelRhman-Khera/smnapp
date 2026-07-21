<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportFormResource\Pages;
use App\Models\Customer;
use App\Models\SupportForm;
use App\Models\Technician;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;

class SupportFormResource extends Resource
{
    protected static ?string $model = SupportForm::class;
    protected static ?string $navigationGroup = 'Support Management';
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $open = static::getModel()::where('status', 'open')->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function flagOptions(): array
    {
        return [
            'green' => 'Resolved',
            'yellow' => 'Pending',
            'red' => 'Critical',
        ];
    }

    public static function flagColor(?string $flag): string
    {
        return match ($flag) {
            'green' => 'success',
            'yellow' => 'warning',
            'red' => 'danger',
            default => 'gray',
        };
    }

    public static function resolveUserName(?SupportForm $record): ?string
    {
        if (! $record) {
            return null;
        }

        if (filled($record->name)) {
            return $record->name;
        }

        $user = $record->user_type === 'technician'
            ? Technician::find($record->user_id)
            : Customer::find($record->user_id);

        return $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : null;
    }

    public static function resolveUserPhone(?SupportForm $record): ?string
    {
        if (! $record) {
            return null;
        }

        if (filled($record->phone)) {
            return $record->phone;
        }

        $user = $record->user_type === 'technician'
            ? Technician::find($record->user_id)
            : Customer::find($record->user_id);

        return $user?->phone;
    }

    /**
     * Schema for the "Add Note" modal used by the table and page actions.
     */
    public static function noteFormSchema(): array
    {
        return [
            Textarea::make('note')
                ->label('Note')
                ->required()
                ->rows(3),
            Select::make('flag_color')
                ->label('Status Flag')
                ->options(static::flagOptions())
                ->default('yellow')
                ->required(),
        ];
    }

    /**
     * Append a note to the support form, stamping the author and time.
     */
    public static function appendNote(SupportForm $record, array $data): void
    {
        $notes = is_array($record->notes) ? $record->notes : [];

        $notes[] = [
            'user' => auth()->user()?->name ?? 'System',
            'user_id' => auth()->id(),
            'note' => $data['note'],
            'flag_color' => $data['flag_color'] ?? 'yellow',
            'created_at' => now()->toDateTimeString(),
        ];

        $record->update(['notes' => $notes]);

        Notification::make()
            ->title('Note added')
            ->success()
            ->send();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Request Details')
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($record) => static::resolveUserName($record)),

                    TextInput::make('phone')
                        ->label('Phone')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($record) => static::resolveUserPhone($record)),

                    Select::make('user_type')
                        ->label('User Type')
                        ->options([
                            'technician' => 'Technician',
                            'customer' => 'Customer',
                        ])
                        ->disabled()
                        ->dehydrated(false),

                    Select::make('platform')
                        ->label('Platform')
                        ->options([
                            'app' => 'App',
                            'web' => 'Web',
                            'chatbot' => 'Chatbot',
                        ])
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('subject')
                        ->label('Subject')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),

                    Textarea::make('details')
                        ->label('Details')
                        ->disabled()
                        ->dehydrated(false)
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Resolution')
                ->schema([
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'open' => 'Open',
                            'closed' => 'Closed',
                        ])
                        ->required(),
                ]),

            Forms\Components\Section::make('Follow-up Notes')
                ->description('History of internal notes. Use the "Add Note" button to add a new one — existing notes cannot be edited.')
                ->schema([
                    Repeater::make('notes')
                        ->hiddenLabel()
                        // Notes are managed through the Add Note action, not this form,
                        // so this repeater is display-only and never saved back.
                        ->dehydrated(false)
                        ->schema([
                            TextInput::make('user')->label('By')->disabled(),
                            TextInput::make('created_at')->label('At')->disabled(),
                            Select::make('flag_color')->label('Flag')->options(static::flagOptions())->disabled(),
                            Textarea::make('note')->label('Note')->disabled()->columnSpanFull(),
                        ])
                        ->columns(3)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->itemLabel(fn (array $state): ?string => trim(
                            ($state['user'] ?? 'Note') . ' · ' . ($state['created_at'] ?? '')
                        ))
                        ->collapsed()
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record): bool => filled($record) && filled($record->notes)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->columns([
                TextColumn::make('id')->sortable(),

                TextColumn::make('subject')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn (SupportForm $record): ?string => $record->subject),

                TextColumn::make('name')
                    ->label('User')
                    ->getStateUsing(fn (SupportForm $record) => static::resolveUserName($record) ?: '-')
                    ->description(fn (SupportForm $record) => static::resolveUserPhone($record))
                    ->searchable(['name', 'phone']),

                Tables\Columns\TextColumn::make('user_type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'technician' ? 'info' : 'success'),

                Tables\Columns\TextColumn::make('platform')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'app' => 'success',
                        'web' => 'gray',
                        'chatbot' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?: '-'))
                    ->color(fn (?string $state): string => $state === 'open' ? 'warning' : 'success'),

                TextColumn::make('last_flag')
                    ->label('Last Flag')
                    ->badge()
                    ->getStateUsing(fn (SupportForm $record): ?string => is_array($record->notes) && count($record->notes)
                        ? (end($record->notes)['flag_color'] ?? null)
                        : null)
                    ->formatStateUsing(fn (?string $state): string => $state ? (static::flagOptions()[$state] ?? $state) : 'No notes')
                    ->color(fn (?string $state): string => static::flagColor($state)),

                TextColumn::make('notes_count')
                    ->label('Notes')
                    ->getStateUsing(fn (SupportForm $record): int => is_array($record->notes) ? count($record->notes) : 0)
                    ->badge()
                    ->color('gray'),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('Y-m-d h:i A')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'closed' => 'Closed',
                    ]),

                Tables\Filters\SelectFilter::make('user_type')
                    ->label('User Type')
                    ->options([
                        'technician' => 'Technician',
                        'customer' => 'Customer',
                    ]),

                Tables\Filters\SelectFilter::make('platform')
                    ->options([
                        'app' => 'App',
                        'web' => 'Web',
                        'chatbot' => 'Chatbot',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('addNote')
                    ->label('Add Note')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('info')
                    ->modalHeading('Add follow-up note')
                    ->modalSubmitActionLabel('Add Note')
                    ->form(static::noteFormSchema())
                    ->action(fn (SupportForm $record, array $data) => static::appendNote($record, $data)),

                Tables\Actions\Action::make('toggleStatus')
                    ->label(fn (SupportForm $record): string => $record->status === 'open' ? 'Close' : 'Reopen')
                    ->icon(fn (SupportForm $record): string => $record->status === 'open' ? 'heroicon-o-check-circle' : 'heroicon-o-arrow-path')
                    ->color(fn (SupportForm $record): string => $record->status === 'open' ? 'success' : 'gray')
                    ->requiresConfirmation()
                    ->action(function (SupportForm $record): void {
                        $record->update(['status' => $record->status === 'open' ? 'closed' : 'open']);

                        Notification::make()
                            ->title($record->status === 'open' ? 'Ticket reopened' : 'Ticket closed')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            \Filament\Infolists\Components\Section::make('Request Details')
                ->schema([
                    \Filament\Infolists\Components\Grid::make(3)->schema([
                        \Filament\Infolists\Components\TextEntry::make('name')
                            ->label('User')
                            ->getStateUsing(fn (SupportForm $record) => static::resolveUserName($record) ?: '-'),
                        \Filament\Infolists\Components\TextEntry::make('phone')
                            ->label('Phone')
                            ->getStateUsing(fn (SupportForm $record) => static::resolveUserPhone($record) ?: '-')
                            ->copyable(),
                        \Filament\Infolists\Components\TextEntry::make('user_type')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'technician' ? 'info' : 'success'),
                        \Filament\Infolists\Components\TextEntry::make('platform')
                            ->badge(),
                        \Filament\Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => ucfirst($state ?: '-'))
                            ->color(fn (?string $state): string => $state === 'open' ? 'warning' : 'success'),
                        \Filament\Infolists\Components\TextEntry::make('created_at')
                            ->label('Submitted')
                            ->dateTime('Y-m-d h:i A'),
                    ]),
                    \Filament\Infolists\Components\TextEntry::make('subject')
                        ->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('details')
                        ->columnSpanFull(),
                ]),

            \Filament\Infolists\Components\Section::make('Follow-up Notes')
                ->schema([
                    \Filament\Infolists\Components\RepeatableEntry::make('notes')
                        ->hiddenLabel()
                        ->schema([
                            \Filament\Infolists\Components\Grid::make(3)->schema([
                                \Filament\Infolists\Components\TextEntry::make('user')
                                    ->label('By')
                                    ->placeholder('-'),
                                \Filament\Infolists\Components\TextEntry::make('created_at')
                                    ->label('At')
                                    ->placeholder('-'),
                                \Filament\Infolists\Components\TextEntry::make('flag_color')
                                    ->label('Flag')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => $state ? (static::flagOptions()[$state] ?? $state) : '-')
                                    ->color(fn (?string $state): string => static::flagColor($state)),
                            ]),
                            \Filament\Infolists\Components\TextEntry::make('note')
                                ->hiddenLabel()
                                ->columnSpanFull(),
                        ])
                        ->contained(false),
                ])
                ->visible(fn (SupportForm $record): bool => is_array($record->notes) && count($record->notes) > 0),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportForms::route('/'),
            'view' => Pages\ViewSupportForm::route('/{record}'),
            'edit' => Pages\EditSupportForm::route('/{record}/edit'),
        ];
    }
}
