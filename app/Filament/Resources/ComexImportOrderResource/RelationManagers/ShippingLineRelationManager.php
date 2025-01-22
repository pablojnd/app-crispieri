<?php

namespace App\Filament\Resources\ComexImportOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\ContainerType;
use Filament\Facades\Filament;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Validation\Rules\Unique;

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
                                    ->relationship(
                                        name: 'shippingLine',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
                                    )
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
                                Forms\Components\Group::make()
                                    ->relationship('events')
                                    ->schema([
                                        Forms\Components\DatePicker::make('start_at')
                                            ->label('Fecha Estimada de Salida')
                                            ->required(),
                                        Forms\Components\DatePicker::make('end_at')
                                            ->label('Fecha Estimada de Llegada'),
                                        Forms\Components\Textarea::make('description')
                                            ->label('Descripción')
                                            ->columnSpanFull()
                                    ])->columns(2)
                                    ->columnSpanFull()
                                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data) use ($form): array {
                                        $record = $form->getRecord();
                                        $shippingLine = $record?->shippingLine;

                                        return [
                                            'store_id' => Filament::getTenant()->id,
                                            'title' => "{$this->getOwnerRecord()->provider->name} | {$shippingLine?->name} | 0 contenedores",
                                            'description' => $data['description'] ?? null,
                                            'start_at' => $data['start_at'],
                                            'end_at' => $data['end_at'] ?? null,
                                        ];
                                    })
                                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data) use ($form): array {
                                        $record = $form->getRecord();
                                        $shippingLine = $record?->shippingLine;
                                        $containerCount = $record?->containers->count() ?? 0;

                                        return [
                                            'store_id' => Filament::getTenant()->id,
                                            'title' => "{$this->getOwnerRecord()->provider->name} | {$shippingLine?->name} | {$containerCount} contenedores",
                                            'description' => $data['description'] ?? null,
                                            'start_at' => $data['start_at'],
                                            'end_at' => $data['end_at'] ?? null,
                                        ];
                                    }),
                            ])->columns(3),
                        Forms\Components\Tabs\Tab::make('Contenedores')
                            ->schema([
                                Forms\Components\Repeater::make('containers')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\TextInput::make('container_number')
                                            ->label('Número de Contenedor')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(
                                                table: 'comex_containers',
                                                column: 'container_number',
                                                ignoreRecord: true,
                                                modifyRuleUsing: function (Unique $rule) {
                                                    return $rule->where('store_id', Filament::getTenant()->id);
                                                }
                                            ),
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
                                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data) use ($form): array {
                                        $record = $form->getRecord();
                                        $data['store_id'] = Filament::getTenant()->id;
                                        $data['import_order_id'] = $this->getOwnerRecord()->id;

                                        // Actualizar el título del evento después de crear el contenedor
                                        if ($event = $record?->events) {
                                            $containerCount = ($record->containers()->count() ?? 0) + 1;
                                            $event->update([
                                                'title' => "{$this->getOwnerRecord()->provider->name} | {$record->shippingLine->name} | {$containerCount} contenedores"
                                            ]);
                                        }

                                        return $data;
                                    })
                                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data) use ($form): array {
                                        $record = $form->getRecord();

                                        // Actualizar el título del evento al guardar cambios en contenedores
                                        if ($event = $record?->events) {
                                            $containerCount = $record->containers()->count();
                                            $event->update([
                                                'title' => "{$this->getOwnerRecord()->provider->name} | {$record->shippingLine->name} | {$containerCount} contenedores"
                                            ]);
                                        }

                                        $data['store_id'] = Filament::getTenant()->id;
                                        $data['import_order_id'] = $this->getOwnerRecord()->id;
                                        return $data;
                                    })
                                    ->afterStateUpdated(function ($record) {
                                        if ($record && $event = $record->events) {
                                            $containerCount = $record->containers()->count();
                                            $event->update([
                                                'title' => "{$this->getOwnerRecord()->provider->name} | {$record->shippingLine->name} | {$containerCount} contenedores"
                                            ]);
                                        }
                                    })
                                    // ->collapsible()
                                    // ->reorderableWithButtons()
                                    // ->cloneable()
                                    ->columns(4)
                                    ->defaultItems(0)
                                    ->reorderable(false)
                                    ->columnSpanFull()
                                    ->collapsible()
                                    ->itemLabel(fn(array $state): ?string => $state['container_number'] ?? null)
                            ]),
                        // Forms\Components\Tabs\Tab::make('Eventos')
                        //     ->schema([
                        //         Forms\Components\Fieldset::make('Evento')
                        //             ->relationship('events')
                        //             ->schema([
                        //                 Forms\Components\TextInput::make('title')
                        //                     ->label('Título')
                        //                     ->required(),
                        //                 Forms\Components\Textarea::make('description')
                        //                     ->label('Descripción'),
                        //                 Forms\Components\DateTimePicker::make('start_at')
                        //                     ->label('Fecha y hora de inicio')
                        //                     ->required(),
                        //                 Forms\Components\DateTimePicker::make('end_at')
                        //                     ->label('Fecha y hora de fin'),
                        //             ])->columns(2)
                        //             ->mutateRelationshipDataBeforeCreateUsing(function (array $data) {
                        //                 $data['store_id'] = Filament::getTenant()->id;
                        //                 $data['title'] = $data['title'] ?? 'Evento de Contenedor';
                        //                 return $data;
                        //             }),
                        //     ]),
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
                Tables\Columns\TextColumn::make('events.start_at')
                    ->label('Fecha Est. Salida')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('events.end_at')
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
                Tables\Actions\CreateAction::make()
                    ->modalWidth(MaxWidth::SevenExtraLarge)
                // ->after(function ($data, $record) {
                //     if (isset($data['containers'])) {
                //         foreach ($data['containers'] as $containerData) {
                //             $containerData['store_id'] = auth()->user()->store_id;
                //             $containerData['import_order_id'] = $this->getOwnerRecord()->id;
                //             $containerData['comex_shipping_line_container_id'] = $record->id;
                //             $record->containers()->create($containerData);
                //         }
                //     }
                // }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->modalWidth(MaxWidth::FiveExtraLarge),
                    Tables\Actions\DeleteAction::make()
                        ->before(function ($record) {
                            // Solo eliminar los contenedores, no la naviera
                            $record->containers()->each(function ($container) {
                                $container->items()->detach();
                                $container->documents()->detach();
                                $container->expenses()->detach();
                                $container->delete();
                            });
                        })
                        ->successNotification(
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Naviera eliminada')
                                ->body('La naviera y sus contenedores han sido eliminados correctamente.')
                        ),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
