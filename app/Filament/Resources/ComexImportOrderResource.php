<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use App\Models\ComexImportOrder;
use Filament\Resources\Resource;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ComexItemImporter;
use App\Exports\ComexImportOrderExport;
use Illuminate\Database\Eloquent\Builder;
use App\Exports\ComexImportOrderSimpleExport;
use App\Enums\{TransportType, ImportOrderStatus};
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ComexImportOrderResource\Pages;
use App\Filament\Resources\ComexImportOrderResource\RelationManagers;

class ComexImportOrderResource extends Resource
{
    protected static ?string $model = ComexImportOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $modelLabel = 'Orden de Importación';
    protected static ?string $pluralModelLabel = 'Órdenes de Importación';
    protected static ?string $navigationGroup = 'Comercio Exterior';
    protected static ?int $navigationSort = 1;
    protected static ?string $tenantOwnershipRelationshipName = 'store';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información Básica')
                ->description('Datos principales de la orden de importación')
                ->schema([
                    Forms\Components\Grid::make()
                        ->columns(3)
                        ->schema([
                            Forms\Components\TextInput::make('reference_number')
                                ->label('Número de Referencia')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),

                            Forms\Components\TextInput::make('external_reference')
                                ->label('Referencia Externa')
                                ->maxLength(255)
                                ->helperText('Referencia proporcionada por el proveedor'),

                            Forms\Components\TextInput::make('sve_registration_number')
                                ->label('Número SVE'),

                            Forms\Components\Select::make('provider_id')
                                ->label('Proveedor')
                                ->relationship(
                                    name: 'provider',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant())
                                )
                                ->searchable(['name', 'rut'])
                                ->preload()
                                ->required()
                                ->createOptionForm(function () {
                                    return static::getProviderFormSchema();
                                }),

                            Forms\Components\Select::make('origin_country_id')
                                ->label('País de Origen')
                                ->relationship(
                                    name: 'originCountry',
                                    titleAttribute: 'country_name'
                                )
                                ->searchable(['country_name'])
                                ->preload()
                                ->required()
                                ->createOptionForm(function () {
                                    return static::getCountryFormSchema();
                                }),

                            Forms\Components\Select::make('type')
                                ->label('Tipo de Transporte')
                                ->options(TransportType::class)
                                ->required()
                        ]),
                ])
                ->columnSpan(['lg' => 2]),

            Forms\Components\Section::make('Estado y Cronograma')
                ->description('Fechas importantes de la orden')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options(ImportOrderStatus::class)
                        ->default(ImportOrderStatus::DRAFT)
                        ->required(),

                    Forms\Components\DatePicker::make('order_date')
                        ->label('Fecha de Orden')
                        ->default(now())
                        ->required(),

                    Forms\Components\TextInput::make('exchange_rate')
                        ->label('Cambio')
                        ->numeric()
                        ->default(1)
                        ->required(),

                    Forms\Components\Placeholder::make('total_cif_and_expenses')
                        ->label('Total CIF + Gastos')
                        ->content(fn($record) => $record ? $record->getTotalCifAndExpenses() : '0.00')
                ])
                ->columns(2)
                ->columnSpan(['lg' => 1]),
        ])
            ->columns([
                'default' => 1,
                'lg' => 3
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Referencia')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('provider.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Transporte')
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('order_date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_cif_and_expenses')
                    ->label('Total CIF + Gastos')
                    // ->money('USD')
                    ->getStateUsing(function (ComexImportOrder $record): string {
                        return $record->getTotalCifAndExpenses();
                    }),
            ])
            ->defaultSort('reference_number', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(ImportOrderStatus::class),
                Tables\Filters\SelectFilter::make('type')
                    ->options(TransportType::class),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\Action::make('importItems')
                        ->label('Importar Productos')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->form([
                            Forms\Components\FileUpload::make('csv_file')
                                ->label('Archivo CSV')
                                ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'application/csv', 'application/x-csv', 'text/comma-separated-values', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                                ->required()
                                ->helperText('Sube un archivo CSV o Excel con los productos a importar')
                        ])
                        ->action(function (array $data, ComexImportOrder $record) {
                            try {
                                Excel::import(new ComexItemImporter($record), $data['csv_file']);

                                // Notificar éxito
                                Filament::notify('success', 'Productos importados correctamente');
                            } catch (\Exception $e) {
                                // Notificar error
                                Filament::notify('danger', 'Error al importar: ' . $e->getMessage());
                            }
                        }),
                    Tables\Actions\Action::make('exportExcelInterno')
                        ->label('Exportar Excel Interno')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function (ComexImportOrder $record) {
                            return Excel::download(new ComexImportOrderExport($record), 'Orden_importacion_' . $record->reference_number . '.xlsx');
                        }),
                    Tables\Actions\Action::make('exportExcelBodega')
                        ->label('Exportar Excel Bodega')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->action(function (ComexImportOrder $record) {
                            return Excel::download(new ComexImportOrderSimpleExport($record), 'Ordern_bodega.xlsx_' . $record->reference_number . '.xlsx');
                        }),
                ]),
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
            RelationManagers\DocumentsRelationManager::class,
            RelationManagers\ShippingLineRelationManager::class,
            // RelationManagers\ContainersRelationManager::class,
            RelationManagers\ItemsRelationManager::class,
            RelationManagers\ExpensesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComexImportOrders::route('/'),
            'create' => Pages\CreateComexImportOrder::route('/create'),
            'edit' => Pages\EditComexImportOrder::route('/{record}/edit'),
        ];
    }

    protected static function getProviderFormSchema(): array
    {
        return [
            Forms\Components\Tabs::make('Proveedor')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('Información Básica')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Nombre')
                                        ->required()
                                        ->helperText('Nombre comercial del proveedor')
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('contact_name')
                                        ->label('Nombre de Contacto')
                                        ->helperText('Persona de contacto principal')
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('email')
                                        ->label('Email')
                                        ->email()
                                        ->helperText('Correo electrónico principal')
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('phone')
                                        ->label('Teléfono')
                                        ->numeric()
                                        ->helperText('Incluir código de país'),
                                    Forms\Components\TextInput::make('rut')
                                        ->label('RUT')
                                        ->unique(ignoreRecord: true)
                                        ->numeric()
                                        ->helperText('RUT chileno sin puntos ni guión'),
                                    Forms\Components\Select::make('type')
                                        ->label('Tipo de Proveedor')
                                        ->options([
                                            'manufacturer' => 'Fabricante',
                                            'distributor' => 'Distribuidor',
                                            'wholesaler' => 'Mayorista',
                                            'retailer' => 'Minorista'
                                        ])
                                        ->default('distributor')
                                        ->required(),
                                    Forms\Components\TextInput::make('website')
                                        ->label('Sitio Web')
                                        ->url()
                                        ->maxLength(255),
                                    Forms\Components\Toggle::make('active')
                                        ->label('Activo')
                                        ->default(true)
                                        ->helperText('Determina si el proveedor está disponible para nuevas órdenes'),
                                    Forms\Components\Textarea::make('observations')
                                        ->label('Observaciones')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Forms\Components\Tabs\Tab::make('Direcciones')
                        ->schema([
                            Forms\Components\Repeater::make('addresses')
                                ->label('Direcciones')
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Nombre de la dirección')
                                        ->required()
                                        ->helperText('Ej: Oficina Principal, Bodega, etc.'),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('street_address')
                                                ->label('Dirección')
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('street_number')
                                                ->label('Número')
                                                ->numeric()
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('city')
                                                ->label('Ciudad')
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('state')
                                                ->label('Estado/Región')
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('country')
                                                ->label('País')
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('postal_code')
                                                ->label('Código Postal')
                                                ->maxLength(255),
                                        ]),
                                    Forms\Components\Toggle::make('is_primary')
                                        ->label('Dirección Principal')
                                        ->helperText('Marcar como dirección principal'),
                                ])
                                ->itemLabel(fn(array $state): ?string => $state['name'] ?? null)
                                ->collapsible()
                                ->defaultItems(0)
                                ->reorderable(),
                        ]),
                ])
                ->columnSpanFull(),
        ];
    }

    protected static function getCountryFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Toggle::make('is_active')
                    ->label('País Activo')
                    ->default(true)
                    ->helperText('Determina si el país está disponible para su uso'),

                Forms\Components\TextInput::make('country_name')
                    ->label('Nombre de País')
                    ->placeholder('Ej: Chile')
                    ->maxLength(255)
                    ->helperText('Nombre oficial de la moneda'),

                Forms\Components\TextInput::make('country_code')
                    ->label('Código de País')
                    ->placeholder('Ej: CL')
                    ->maxLength(255)
                    ->helperText('Código de país según ISO 3166-1 alpha-2'),

                Forms\Components\Select::make('currency_id')
                    ->label('Código de Moneda')
                    ->relationship('currency', 'name')
                    ->placeholder('Ej: CLP')
                    ->createOptionForm(
                        fn() => static::getCurrencyFormSchema()
                    )
                    ->helperText('Código de la moneda'),
            ])
        ];
    }

    protected static function getCurrencyFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Nombre de Moneda')
                ->placeholder('Ej: Peso Chileno')
                ->maxLength(255)
                ->helperText('Nombre oficial de la moneda'),

            Forms\Components\TextInput::make('code')
                ->label('Código de Moneda')
                ->placeholder('Ej: CLP')
                ->maxLength(255)
                ->helperText('Código de la moneda'),
        ];
    }
}
