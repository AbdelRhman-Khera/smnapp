<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LandingPageResource\Pages;
use App\Filament\Resources\LandingPageResource\RelationManagers;
use App\Models\Landing;
use Faker\Provider\ar_EG\Text;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LandingPageResource extends Resource
{
    protected static ?string $model = Landing::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationUrl(): string
    {
        return static::getUrl('edit', ['record' => 1]);
    }


    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Main Section')
                ->schema([
                    Tabs::make('Main Section Tabs')
                        ->tabs([
                            Tab::make('Arabic')
                                ->schema([
                                    TextInput::make('main_title_ar')
                                        ->label('Main Title (Arabic)')
                                        ->required()
                                        ->columnSpan(1),
                                    Textarea::make('main_description_ar')
                                        ->label('Main Description (Arabic)')
                                        ->required(),
                                ]),
                            Tab::make('English')
                                ->schema([
                                    TextInput::make('main_title_en')
                                        ->label('Main Title (English)')
                                        ->required()
                                        ->columnSpan(1),
                                    Textarea::make('main_description_en')
                                        ->label('Main Description (English)')
                                        ->required(),
                                ]),
                        ]),
                    FileUpload::make('main_image')->label('Main Image'),
                    FileUpload::make('logo')->label('Logo'),
                ])
                ->icon('heroicon-o-home')
                ->collapsible(),

            Section::make('Feature Section')
                ->schema([
                    Tabs::make('Feature Section Tabs')
                        ->tabs([
                            Tab::make('Arabic')
                                ->schema([
                                    TextInput::make('feature_title_ar')
                                        ->label('Feature Title (Arabic)')
                                        ->required()
                                        ->columnSpan(1),
                                    Textarea::make('feature_description_ar')
                                        ->label('Feature Description (Arabic)')
                                        ->required(),
                                ]),
                            Tab::make('English')
                                ->schema([
                                    TextInput::make('feature_title_en')
                                        ->label('Feature Title (English)')
                                        ->required()
                                        ->columnSpan(1),
                                    Textarea::make('feature_description_en')
                                        ->label('Feature Description (English)')
                                        ->required(),
                                ]),
                        ]),
                    FileUpload::make('feature_image')->label('Feature Image'),
                ])
                ->icon('heroicon-o-star')
                ->collapsible(),

            Section::make('Steps Section')
                ->schema([
                    Repeater::make('steps')
                        ->schema([
                            Tabs::make('Step Tabs')
                                ->tabs([
                                    Tab::make('Arabic')
                                        ->schema([
                                            TextInput::make('step_title_ar')->label('Step Title (Arabic)')->columnSpan(1),
                                            Textarea::make('step_description_ar')->label('Step Description (Arabic)'),
                                        ]),
                                    Tab::make('English')
                                        ->schema([
                                            TextInput::make('step_title_en')->label('Step Title (English)')->columnSpan(1),
                                            Textarea::make('step_description_en')->label('Step Description (English)'),
                                        ]),
                                ]),
                            FileUpload::make('step_icon')->label('Step Icon'),
                        ]),
                ])
                ->icon('heroicon-o-check')
                ->collapsible(),

            Section::make('Services Section')
                ->schema([
                    Repeater::make('services')
                        ->schema([
                            Tabs::make('Service Tabs')
                                ->tabs([
                                    Tab::make('Arabic')
                                        ->schema([
                                            TextInput::make('service_title_ar')->label('Service Title (Arabic)')->columnSpan(1),
                                            Textarea::make('service_description_ar')->label('Service Description (Arabic)'),
                                        ]),
                                    Tab::make('English')
                                        ->schema([
                                            TextInput::make('service_title_en')->label('Service Title (English)')->columnSpan(1),
                                            Textarea::make('service_description_en')->label('Service Description (English)'),
                                        ]),
                                ]),
                        ]),
                    FileUpload::make('services_image')->label('Services Image'),
                ])
                ->icon('heroicon-o-cog')
                ->collapsible(),

            Section::make('Store Section')
                ->schema([
                    Tabs::make('Store Section Tabs')
                        ->tabs([
                            Tab::make('Arabic')
                                ->schema([
                                    TextInput::make('store_title_ar')->label('Store Title (Arabic)')->columnSpan(1),
                                    Textarea::make('store_description_ar')->label('Store Description (Arabic)'),
                                ]),
                            Tab::make('English')
                                ->schema([
                                    TextInput::make('store_title_en')->label('Store Title (English)')->columnSpan(1),
                                    Textarea::make('store_description_en')->label('Store Description (English)'),
                                ]),
                        ]),
                    FileUpload::make('store_image')->label('Store Image'),
                    TextInput::make('store_url')->label('Store URL'),
                ])
                ->icon('heroicon-o-shopping-cart')
                ->collapsible(),

            Section::make('Map Section')
                ->schema([
                    FileUpload::make('map_image')->label('Map Image'),
                ])
                ->icon('heroicon-o-map')
                ->collapsible(),

            Section::make('Download Section')
                ->schema([
                    Tabs::make('Download Section Tabs')
                        ->tabs([
                            Tab::make('Arabic')
                                ->schema([
                                    TextInput::make('download_title_ar')->label('Download Title (Arabic)')->columnSpan(1),
                                ]),
                            Tab::make('English')
                                ->schema([
                                    TextInput::make('download_title_en')->label('Download Title (English)')->columnSpan(1),
                                ]),
                        ]),
                    TextInput::make('app_store_url')->label('App Store URL')->columnSpan(1),
                    TextInput::make('google_play_url')->label('Google Play URL')->columnSpan(1),
                    FileUpload::make('download_image')->label('Download Image'),
                ])
                ->icon('heroicon-o-arrow-down-tray')
                ->collapsible(),

            Section::make('Footer Section')
                ->schema([
                    Repeater::make('social')
                        ->label('Social Media Links')
                        ->schema([
                            TextInput::make('platform')
                                ->label('Platform Name')
                                ->placeholder('e.g., Facebook, Twitter')
                                ->required(),
                            TextInput::make('url')
                                ->label('URL')
                                ->placeholder('e.g., https://facebook.com')
                                ->required(),
                        ])
                        ->collapsible()
                        ->columns(2), // Display two fields side-by-side
                    Tabs::make('Rights Text')
                        ->tabs([
                            Tab::make('Arabic')
                                ->schema([
                                    TextInput::make('rights_ar')
                                        ->label('Rights Text (Arabic)')
                                        ->placeholder('جميع الحقوق محفوظة لشركتنا')
                                        ->required(),
                                ]),
                            Tab::make('English')
                                ->schema([
                                    TextInput::make('rights_en')
                                        ->label('Rights Text (English)')
                                        ->placeholder('All rights reserved to our company')
                                        ->required(),
                                ]),
                        ]),
                ])
                ->icon('heroicon-o-cursor-arrow-ripple')
                ->collapsible(),


        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('main_title_ar')->label('Main Title (Arabic)'),
                TextColumn::make('main_title_en')->label('Main Title (English)'),
                ImageColumn::make('main_image')->label('Main Image'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListLandingPages::route('/'),
            // 'create' => Pages\CreateLandingPage::route('/create'),
            'edit' => Pages\EditLandingPage::route('/{record}/edit'),
        ];
    }
}
