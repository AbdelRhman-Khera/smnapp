<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Filament\Resources\PageResource\RelationManagers;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationGroup = 'Content Management';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Page Details')
                ->tabs([
                    Tab::make('Arabic')
                        ->schema([
                            TextInput::make('title_ar')
                                ->label('Title (Arabic)')
                                ->required(),
                            RichEditor::make('content_ar')
                                ->label('Content (Arabic)')
                                ->toolbarButtons([
                                    'bold',
                                    'italic',
                                    'underline',
                                    'strike',
                                    'link',
                                    'orderedList',
                                    'unorderedList',
                                    'blockquote',
                                    'codeBlock',
                                    'textColor',
                                    'backgroundColor',
                                    'fullscreen',
                                    'redo',
                                    'undo',
                                ])
                                ->required(),
                        ]),
                    Tab::make('English')
                        ->schema([
                            TextInput::make('title_en')
                                ->label('Title (English)')
                                ->required(),
                            RichEditor::make('content_en')
                                ->label('Content (English)')
                                ->toolbarButtons([
                                    'bold',
                                    'italic',
                                    'underline',
                                    'strike',
                                    'link',
                                    'orderedList',
                                    'unorderedList',
                                    'blockquote',
                                    'codeBlock',
                                    'textColor',
                                    'backgroundColor',
                                    'fullscreen',
                                    'redo',
                                    'undo',
                                ])
                                ->required(),
                        ]),
                ])->columnSpanFull(),
            TextInput::make('slug')
                ->label('Slug')
                ->unique()
                ->required(),
            Toggle::make('is_active')
                ->label('Is Active')
                ->default(true),
        ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title_ar')->label('Title (Arabic)'),
                TextColumn::make('title_en')->label('Title (English)'),
                TextColumn::make('slug')->label('Slug'),
                BooleanColumn::make('is_active')->label('Active'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
