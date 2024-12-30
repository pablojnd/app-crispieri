<?php

namespace App\Filament\Resources\ComexImportOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\ContainerType;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class ShippingLineRelationManager extends RelationManager
{
    protected static string $relationship = 'comexShippingLineContainers';

    protected static ?string $title = 'Navieras';

    protected static ?string $modelLabel = 'naviera';

    protected static ?string $pluralModelLabel = 'navieras';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Naviera')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Información General')
                            ->schema([
                                Forms\Components\Select::make('shipping_line_id')
                                    ->label('Nombre de la Naviera')
                                    ->relationship('shippingLine', 'name')
                                    ->placeholder('Seleccione una Naviera')
                                    ->preload()
                                    ->searchable()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nombre')
                                            ->required(),
                                        Forms\Components\TextInput::make('contact_person')
                                            ->label('Persona de Contacto')
                                            ->required(),
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Teléfono')
                                            ->required(),
                                        Forms\Components\TextInput::make('email')
                                            ->label('Correo Electrónico')
                                            ->required(),
                                    ]),

                                Forms\Components\DatePicker::make('estimated_departure')
                                    ->label('Fecha Estimada de Salida'),
                                Forms\Components\DatePicker::make('actual_departure')
                                    ->label('Fecha Real de Salida'),
                                Forms\Components\DatePicker::make('estimated_arrival')
                                    ->label('Fecha Estimada de Llegada'),
                                Forms\Components\DatePicker::make('actual_arrival')
                                    ->label('Fecha Real de Llegada'),
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notas')
                                    ->maxLength(65535)
                                    ->columnSpanFull(),
                            ])->columns(3),
                        Forms\Components\Tabs\Tab::make('Contenedores')
                            ->schema([
                                Forms\Components\Repeater::make('containers')
                                    ->relationship('containers')
                                    ->schema([
                                        Forms\Components\TextInput::make('container_number')
                                            ->label('Número de Contenedor')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Select::make('type')
                                            ->label('Tipo de Contenedor')
                                            ->options(ContainerType::class)
                                            ->required(),
                                        Forms\Components\TextInput::make('weight')
                                            ->label('Peso (KG)')
                                            ->numeric()
                                            ->default(0.00)
                                            ->step(0.01),
                                        Forms\Components\TextInput::make('cost')
                                            ->label('Costo')
                                            ->numeric()
                                            ->default(0.00)
                                            ->step(0.01),
                                        Forms\Components\Textarea::make('notes')
                                            ->label('Notas')
                                            ->maxLength(65535)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(4)
                                    ->defaultItems(0)
                                    ->reorderable(false)
                                    ->columnSpanFull()
                                    ->collapsible()
                                    ->itemLabel(fn(array $state): ?string => $state['container_number'] ?? null)
                            ]),
                    ])
                    ->columnSpanFull()
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('shippingLine.name')
                    ->label('Naviera')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('containers.container_number')
                    ->label('Contenedores')
                    ->listWithLineBreaks()
                    ->searchable(),
                Tables\Columns\TextColumn::make('estimated_departure')
                    ->label('Fecha Est. Salida')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('estimated_arrival')
                    ->label('Fecha Est. Llegada')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('containers_count')
                    ->label('# Contenedores')
                    ->counts('containers')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->modalWidth(MaxWidth::SevenExtraLarge),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()->modalWidth(MaxWidth::FiveExtraLarge),
                    Tables\Actions\DeleteAction::make(),
                ]),
                // Tables\Actions\EditAction::make()->modalWidth(MaxWidth::FiveExtraLarge),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
