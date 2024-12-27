<?php

namespace App\Filament\Resources\ComexImportOrderResource\RelationManagers;

use App\Enums\ContainerType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShippingLineRelationManager extends RelationManager
{
    protected static string $relationship = 'shippingLines';

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
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre de la Naviera')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('contact_person')
                                    ->label('Persona de Contacto')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Teléfono')
                                    ->tel()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label('Correo Electrónico')
                                    ->email()
                                    ->maxLength(255),
                                Forms\Components\DatePicker::make('estimated_departure')
                                    ->label('Fecha Estimada de Salida'),
                                Forms\Components\DatePicker::make('actual_departure')
                                    ->label('Fecha Real de Salida'),
                                Forms\Components\DatePicker::make('estimated_arrival')
                                    ->label('Fecha Estimada de Llegada'),
                                Forms\Components\DatePicker::make('actual_arrival')
                                    ->label('Fecha Real de Llegada'),
                                Forms\Components\Select::make('status')
                                    ->label('Estado')
                                    ->options([
                                        'active' => 'Activo',
                                        'inactive' => 'Inactivo',
                                        'in_transit' => 'En Tránsito',
                                        'completed' => 'Completado'
                                    ])
                                    ->default('active'),
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notas')
                                    ->maxLength(65535)
                                    ->columnSpanFull(),
                            ])->columns(3),
                        Forms\Components\Tabs\Tab::make('Contenedores')
                            ->schema([
                                Forms\Components\Repeater::make('containers')
                                    ->relationship()
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
                                    ])->columns(3)
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
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Naviera')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Contacto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estimated_departure')
                    ->label('Salida Est.')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estimated_arrival')
                    ->label('Llegada Est.')
                    ->date()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'in_transit',
                        'success' => 'active',
                        'danger' => 'inactive',
                        'primary' => 'completed',
                    ]),
                Tables\Columns\TextColumn::make('containers_count')
                    ->label('Contenedores')
                    ->counts('containers')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'in_transit' => 'En Tránsito',
                        'completed' => 'Completado'
                    ]),
                Tables\Filters\Filter::make('estimated_departure')
                    ->form([
                        Forms\Components\DatePicker::make('departure_from')
                            ->label('Fecha de Salida Desde'),
                        Forms\Components\DatePicker::make('departure_until')
                            ->label('Fecha de Salida Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['departure_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('estimated_departure', '>=', $date),
                            )
                            ->when(
                                $data['departure_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('estimated_departure', '<=', $date),
                            );
                    })
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nueva Naviera'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
