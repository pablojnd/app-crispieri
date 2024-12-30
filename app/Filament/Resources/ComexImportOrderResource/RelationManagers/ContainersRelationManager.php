<?php

namespace App\Filament\Resources\ComexImportOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\ContainerType;
use Filament\Support\Enums\MaxWidth;
use Filament\Resources\RelationManagers\RelationManager;

class ContainersRelationManager extends RelationManager
{
    protected static string $relationship = 'containers';

    protected static ?string $title = 'Contenedores';

    protected static ?string $modelLabel = 'Contenedor';

    protected static ?string $pluralModelLabel = 'Contenedores';

    protected static ?string $recordTitleAttribute = 'container_number';


    protected static ?string $tenantOwnershipRelationshipName = 'store';
    // Configuración clave para la relación
    protected static ?string $inverseRelationship = 'importOrder';
    protected static ?string $foreignKeyName = 'import_order_id';
    protected static ?string $inverseRelationshipForeignKeyName = 'import_order_id';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Tabs')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('Naviera')
                        ->schema([
                            Forms\Components\Select::make('shippingLine_ic')
                                ->label('Nombre')
                                ->relationship('shippingLine', 'name')
                                ->placeholder('Seleccione una Naviera')
                                ->required()
                                ->searchable()
                                ->preload()
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

                                    Forms\Components\TextInput::make('address')
                                        ->label('Dirección')
                                        ->required(),
                                ])
                                ->required(),

                            Forms\Components\DatePicker::make('estimated_departure')
                                ->label('Salida Estimada'),

                            Forms\Components\DatePicker::make('actual_departure')
                                ->label('Salida Real'),

                            Forms\Components\DatePicker::make('estimated_arrival')
                                ->label('Llegada Estimada'),

                            Forms\Components\DatePicker::make('actual_arrival')
                                ->label('Llegada Real'),

                            // Forms\Components\Select::make('status')
                            //     ->options([
                            //         'active' => 'Activo',
                            //         'inactive' => 'Inactivo',
                            //     ])
                            //     ->default('active')
                            //     ->required(),

                            Forms\Components\Textarea::make('notes')
                                ->label('Notas')
                                ->columnSpanFull(),
                        ])->columns(2),
                    Forms\Components\Tabs\Tab::make('Contenedores')
                        ->schema([
                            Forms\Components\TextInput::make('container_number')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255)
                                ->label('Número de Contenedor'),

                            Forms\Components\Select::make('type')
                                ->options(ContainerType::class)
                                ->required()
                                ->label('Tipo'),

                            Forms\Components\TextInput::make('weight')
                                ->numeric()
                                ->required()
                                ->label('Peso (KG)'),

                            Forms\Components\TextInput::make('cost')
                                ->numeric()
                                ->required()
                                ->label('Costo'),

                            Forms\Components\Textarea::make('notes')
                                ->maxLength(500)
                                ->columnSpanFull()
                                ->label('Notas'),
                        ])->columns(2),
                ])->columnSpanFull()
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('container_number')
            ->columns([
                Tables\Columns\TextColumn::make('container_number')
                    ->searchable()
                    ->sortable()
                    ->label('Número'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->label('Tipo'),

                Tables\Columns\TextColumn::make('shippingLine.name')
                    ->searchable()
                    ->sortable()
                    ->label('Naviera'),

                Tables\Columns\TextColumn::make('estimated_departure')
                    ->date()
                    ->toggleable()
                    ->label('Fecha Est. Salida'),

                Tables\Columns\TextColumn::make('estimated_arrival')
                    ->date()
                    ->toggleable()
                    ->label('Fecha Est. Llegada'),

                Tables\Columns\TextColumn::make('weight')
                    ->numeric(
                        decimalPlaces: 2,
                        thousandsSeparator: '.',
                        decimalSeparator: ','
                    )
                    ->sortable()
                    ->label('Peso'),

                Tables\Columns\TextColumn::make('cost')
                    ->numeric(
                        decimalPlaces: 2,
                        thousandsSeparator: '.',
                        decimalSeparator: ','
                    )
                    ->summarize(Tables\Columns\Summarizers\Sum::make())
                    ->sortable()
                    ->label('Costo'),

                Tables\Columns\TextColumn::make('documents_count')
                    ->counts('documents')
                    ->label('Documentos'),
            ])
            ->defaultSort('container_number', 'asc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Agregar Contenedor'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()->modalWidth(MaxWidth::FiveExtraLarge),
                    Tables\Actions\DeleteAction::make()->modalWidth(MaxWidth::FiveExtraLarge),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
