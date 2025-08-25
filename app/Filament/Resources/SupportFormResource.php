<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportFormResource\Pages;
use App\Filament\Resources\SupportFormResource\RelationManagers;
use App\Models\Customer;
use App\Models\SupportForm;
use App\Models\Technician;
use Dom\Text;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\Facades\Auth;

class SupportFormResource extends Resource
{
    protected static ?string $model = SupportForm::class;
    protected static ?string $navigationGroup = 'Support Management';
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {

        return $form->schema([
            Select::make('user_type')
                ->label('User Type')
                ->options([
                    'technician' => 'Technician',
                    'customer' => 'Customer',
                ])
                ->disabled()
                ->required(),

            Select::make('user_id')
                ->label('User')
                ->reactive()
                ->options(
                    fn(callable $get) =>
                    $get('user_type') === 'technician'
                        ? Technician::pluck('first_name', 'id')->toArray()
                        : Customer::pluck('first_name', 'id')->toArray()
                )
                ->disabled()
                ->required(),
            TextInput::make('name')
                ->label('Name')
                ->disabled(),
            TextInput::make('phone')
                ->label('Phone')
                ->disabled(),
            TextInput::make('subject')
                ->label('Subject')
                ->disabled()
                ->required(),

            Select::make('platform')
                ->label('Platform')
                ->options([
                    'app' => 'App',
                    'web' => 'Web',
                    'chatbot' => 'Chatbot',
                ])
                ->disabled()
                ->required(),
            Textarea::make('details')
                ->label('Details')
                ->disabled()
                ->required()
                ->columnSpanFull(),


            Select::make('status')
                ->label('Status')
                ->options([
                    'open' => 'Open',
                    'closed' => 'Closed',
                ])
                ->required(),

            Repeater::make('notes')
                ->label('Notes')
                ->schema([
                    TextInput::make('user')
                        ->default(fn() => auth()->user()->name)
                        ->disabled(),
                    Textarea::make('note')
                        ->label('Note')
                        ->required(),
                    Select::make('flag_color')
                        ->label('Status Flag')
                        ->options([
                            'green' => 'Resolved',
                            'yellow' => 'Pending',
                            'red' => 'Critical',
                        ])
                        ->required(),
                ])
                ->default(fn($record) => is_array($record?->notes) ? $record->notes : [])
                ->disableItemDeletion()
                ->disableItemMovement()
                ->addable(true)
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('subject')->searchable(),
                BadgeColumn::make('user_type')
                    ->colors([
                        'info' => 'technician',
                        'success' => 'customer',
                    ]),
                TextColumn::make('user_id')
                    ->label('User')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->user_type === 'technician'
                            ? Technician::find($state)?->first_name
                            : Customer::find($state)?->first_name
                    ),
                BadgeColumn::make('platform')
                    ->colors([
                        'success' => 'app',
                        'secondary' => 'web',
                        'info' => 'chatbot',
                    ]),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'open',
                        'danger' => 'closed',
                    ]),
                TextColumn::make('created_at')->sortable()->dateTime(),
            ])->defaultSort('id', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
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
            'index' => Pages\ListSupportForms::route('/'),
            // 'create' => Pages\CreateSupportForm::route('/create'),
            'edit' => Pages\EditSupportForm::route('/{record}/edit'),
        ];
    }
}
