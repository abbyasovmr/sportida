<?php
// app/Filament/Resources/EventResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Мероприятия';
    protected static ?string $modelLabel = 'Мероприятие';
    protected static ?string $pluralModelLabel = 'Мероприятия';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основное')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', \Str::slug($state))),
                        
                        Forms\Components\TextInput::make('slug')
                            ->label('URL')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->prefix('sportida.ru/events/'),
                        
                        Forms\Components\Select::make('organization_id')
                            ->label('Организатор')
                            ->relationship('organization', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        
                        Forms\Components\Select::make('sport_id')
                            ->label('Вид спорта')
                            ->relationship('sport', 'name')
                            ->required(),
                        
                        Forms\Components\Select::make('type')
                            ->label('Тип')
                            ->options([
                                'competition' => 'Соревнование',
                                'championship' => 'Чемпионат',
                                'cup' => 'Кубок',
                                'training_camp' => 'Учебно-тренировочные сборы',
                                'master_class' => 'Мастер-класс',
                                'exhibition' => 'Выставка',
                            ])
                            ->required(),
                        
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'draft' => 'Черновик',
                                'published' => 'Опубликовано',
                                'registration_open' => 'Регистрация открыта',
                                'registration_closed' => 'Регистрация закрыта',
                                'in_progress' => 'Идет',
                                'completed' => 'Завершено',
                                'cancelled' => 'Отменено',
                            ])
                            ->default('draft')
                            ->required(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Даты')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Начало')
                            ->required(),
                        
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Окончание')
                            ->required(),
                        
                        Forms\Components\DateTimePicker::make('registration_start')
                            ->label('Начало регистрации'),
                        
                        Forms\Components\DateTimePicker::make('registration_end')
                            ->label('Конец регистрации'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Место проведения')
                    ->schema([
                        Forms\Components\TextInput::make('city')
                            ->label('Город')
                            ->required(),
                        
                        Forms\Components\TextInput::make('venue_name')
                            ->label('Название места'),
                        
                        Forms\Components\Textarea::make('venue_address')
                            ->label('Адрес')
                            ->rows(2),
                    ]),
                
                Forms\Components\Section::make('Описание')
                    ->schema([
                        Forms\Components\RichEditor::make('description')
                            ->label('Описание')
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'h2', 'h3', 'bulletList', 'orderedList', 'link'
                            ]),
                        
                        Forms\Components\RichEditor::make('program')
                            ->label('Программа'),
                        
                        Forms\Components\RichEditor::make('rules')
                            ->label('Положение'),
                    ]),
                
                Forms\Components\Section::make('Медиа')
                    ->schema([
                        Forms\Components\FileUpload::make('cover_image')
                            ->label('Обложка')
                            ->image()
                            ->imageEditor()
                            ->optimize('webp')
                            ->resize(1200, 800)
                            ->directory('events/covers'),
                        
                        Forms\Components\FileUpload::make('gallery')
                            ->label('Галерея')
                            ->image()
                            ->multiple()
                            ->optimize('webp')
                            ->resize(1920, 1080)
                            ->directory('events/gallery'),
                        
                        Forms\Components\FileUpload::make('documents')
                            ->label('Документы (PDF)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->multiple()
                            ->directory('events/documents'),
                    ]),
                
                Forms\Components\Section::make('SEO')
                    ->schema([
                        Forms\Components\TextInput::make('seo_title')
                            ->label('SEO Title')
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('seo_description')
                            ->label('SEO Description')
                            ->rows(2),
                        
                        Forms\Components\Textarea::make('seo_keywords')
                            ->label('SEO Keywords')
                            ->rows(2),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->label('')
                    ->square()
                    ->size(50),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Организатор')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('city')
                    ->label('Город')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),
                
                Tables\Columns\SelectColumn::make('status')
                    ->label('Статус')
                    ->options([
                        'draft' => 'Черновик',
                        'published' => 'Опубликовано',
                        'registration_open' => 'Регистрация открыта',
                        'registration_closed' => 'Регистрация закрыта',
                        'in_progress' => 'Идет',
                        'completed' => 'Завершено',
                        'cancelled' => 'Отменено',
                    ])
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('views_count')
                    ->label('Просмотры')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Черновик',
                        'published' => 'Опубликовано',
                        'registration_open' => 'Регистрация открыта',
                        'completed' => 'Завершено',
                        'cancelled' => 'Отменено',
                    ]),
                
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'competition' => 'Соревнование',
                        'championship' => 'Чемпионат',
                        'cup' => 'Кубок',
                        'training_camp' => 'УТС',
                    ]),
                
                Tables\Filters\Filter::make('start_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('start_date', '>=', $date))
                            ->when($data['to'], fn (Builder $query, $date) => $query->whereDate('start_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('preview')
                    ->label('На сайте')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Event $record) => '/events/' . $record->slug)
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // EventRanksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
