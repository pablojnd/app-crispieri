<?php

namespace App\Filament\Resources\ComexImportOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $recordTitleAttribute = 'product.name';
    protected static ?string $title = 'Items';
    protected static ?string $modelLabel = 'Item';
    protected static ?string $pluralModelLabel = 'Items';

    protected static ?string $tenantOwnershipRelationshipName = 'store';

    // Agregar estas líneas para especificar el nombre correcto de la clave foránea
    protected static ?string $inverseRelationship = 'importOrder';
    protected static ?string $foreignKeyName = 'import_order_id';
    protected static ?string $inverseRelationshipForeignKeyName = 'import_order_id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship(
                        name: 'product',
                        titleAttribute: 'product_name',
                        modifyQueryUsing: fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
                        // modifyQueryUsing: fn(Builder $query) =>
                        // $query->whereBelongsTo($store)
                    )
                    ->searchable(['product_name', 'sku'])
                    ->preload()
                    ->required()
                    ->createOptionForm(function () {
                        return static::getProductsFormSchema();
                    })
                    ->label('Producto')
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->label('Cantidad'),

                Forms\Components\TextInput::make('total_price')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->prefix('$')
                    ->label('Precio Total'),

                Forms\Components\Select::make('documents')
                    ->relationship(
                        name: 'documents',
                        titleAttribute: 'document_number',
                        modifyQueryUsing: fn(Builder $query) => $query->where('import_order_id', $this->getOwnerRecord()->id)
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->label('Documentos'),

                Forms\Components\Select::make('containers')
                    ->relationship(
                        name: 'containers',
                        titleAttribute: 'container_number',
                        modifyQueryUsing: fn(Builder $query) => $query->where('import_order_id', $this->getOwnerRecord()->id)
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->label('Contenedores'),
            ])
            ->columns(4);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.product_name')
                    ->searchable()
                    ->sortable()
                    ->label('Producto'),

                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make())
                    ->label('Cantidad'),

                Tables\Columns\TextColumn::make('unit_price')
                    ->numeric(
                        decimalPlaces: 4,
                        thousandsSeparator: '.',
                        decimalSeparator: ','
                    )
                    ->label('Precio Unitario'),

                Tables\Columns\TextColumn::make('total_price')
                    ->numeric(
                        decimalPlaces: 4,
                        thousandsSeparator: '.',
                        decimalSeparator: ','
                    )
                    ->summarize(Tables\Columns\Summarizers\Sum::make())
                    ->label('Precio Total'),

                Tables\Columns\TextColumn::make('cif_unit')
                    ->numeric(
                        decimalPlaces: 4,
                        thousandsSeparator: '.',
                        decimalSeparator: ','
                    )
                    ->summarize(Tables\Columns\Summarizers\Sum::make())
                    ->label('CIF Unitario'),

                Tables\Columns\TextColumn::make('documents_count')
                    ->counts('documents')
                    ->label('Documentos'),

                Tables\Columns\TextColumn::make('containers_count')
                    ->counts('containers')
                    ->label('Contenedores'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Agregar Item'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function getProductsFormSchema(): array
    {
        return [
            Forms\Components\Tabs::make('Producto')
                ->tabs([
                    // Tab 1: Información Básica
                    Forms\Components\Tabs\Tab::make('Información Básica')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\TextInput::make('product_name')
                                    ->label('Nombre del Producto')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                                        if (($get('slug') ?? '') !== Str::slug($old)) {
                                            return;
                                        }
                                        $set('slug', Str::slug($state));
                                    }),
                                Forms\Components\TextInput::make('slug')
                                    ->label('URL Amigable'),
                                Forms\Components\Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->label('Categoría')
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nombre de la Categoría')
                                            ->required(),
                                    ])
                                    ->required(),
                                Forms\Components\Select::make('brand_id')
                                    ->relationship('brand', 'name')
                                    ->label('Marca')
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nombre de la Marca')
                                            ->required(),
                                    ]),
                                Forms\Components\Select::make('measurement_unit_id')
                                    ->relationship('measurementUnit', 'name')
                                    ->label('Unidad de Medida')
                                    ->createOptionForm(function () {
                                        return static::getMeasurementUnitsFormSchema();
                                    })
                                    ->required(),
                                Forms\Components\TextInput::make('hs_code')
                                    ->label('Código HS')
                                    ->helperText('Código de clasificación arancelaria'),
                                Forms\Components\Toggle::make('is_taxable')
                                    ->label('Aplica Impuesto')
                                    ->default(false),
                                Forms\Components\TextInput::make('tax_rate')
                                    ->label('Tasa de Impuesto (%)')
                                    ->numeric()
                                    ->visible(
                                        fn(Get $get) =>
                                        $get('is_taxable')
                                    ),
                            ]),
                            Forms\Components\RichEditor::make('description')
                                ->label('Descripción')
                                ->columnSpanFull(),
                        ]),

                    // Tab 3: Inventario y Logística
                    Forms\Components\Tabs\Tab::make('Inventario')
                        ->icon('heroicon-o-truck')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                // Forms\Components\TextInput::make('stock')
                                //     ->label('Stock Actual')
                                //     ->numeric()
                                //     ->required(),
                                // Forms\Components\TextInput::make('minimum_stock')
                                //     ->label('Stock Mínimo')
                                //     ->numeric(),
                                // Forms\Components\TextInput::make('maximum_stock')
                                //     ->label('Stock Máximo')
                                //     ->numeric(),
                                Forms\Components\TextInput::make('packing_type')
                                    ->label('Tipo de Empaque'),
                                Forms\Components\TextInput::make('packing_quantity')
                                    ->label('Cantidad por Empaque')
                                    ->numeric(),
                            ]),
                            Forms\Components\Section::make('Dimensiones')
                                ->description('Medidas del producto')
                                ->schema([
                                    Forms\Components\Grid::make(4)->schema([
                                        Forms\Components\TextInput::make('weight')
                                            ->label('Peso (kg)')
                                            ->numeric(),
                                        Forms\Components\TextInput::make('length')
                                            ->label('Largo (cm)')
                                            ->numeric(),
                                        Forms\Components\TextInput::make('width')
                                            ->label('Ancho (cm)')
                                            ->numeric(),
                                        Forms\Components\TextInput::make('height')
                                            ->label('Alto (cm)')
                                            ->numeric(),
                                    ]),
                                ]),
                        ]),

                    // Tab 4: Atributos
                    // Forms\Components\Tabs\Tab::make('Atributos')
                    //     ->icon('heroicon-o-adjustments-horizontal')
                    //     ->schema([
                    //         Forms\Components\Repeater::make('productAttributes')
                    //             ->relationship()
                    //             ->schema([
                    //                 Forms\Components\Select::make('attribute_id')
                    //                     ->label('Atributo')
                    //                     ->relationship('attribute', 'name')
                    //                     ->required()
                    //                     ->live()
                    //                     ->afterStateUpdated(
                    //                         fn($state, Set $set) =>
                    //                         $set('attribute_value_id', null)
                    //                     ),
                    //                 Forms\Components\Select::make('attribute_value_id')
                    //                     ->label('Valor')
                    //                     ->relationship('attributeValue', 'value')
                    //                     ->required()
                    //                     ->visible(
                    //                         fn(Get $get) =>
                    //                         filled($get('attribute_id'))
                    //                     ),
                    //             ])
                    //             ->columns(2),
                    //     ]),

                    // Tab 6: Multimedia
                    Forms\Components\Tabs\Tab::make('Multimedia')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Forms\Components\FileUpload::make('image')
                                ->label('Imágenes')
                                ->multiple()
                                ->image()
                                ->panelLayout('grid')
                                ->maxFiles(5)
                                ->columnSpanFull(),
                        ]),
                ])
                ->persistTab()
                ->id('product-tabs')
                ->columnSpanFull()
        ];
    }

    public function getMeasurementUnitsFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                Forms\Components\TextInput::make('abbreviation')
                    ->label('Abreviatura')
                    ->required(),
                Forms\Components\TextInput::make('description')
                    ->label('Descripción'),
                Forms\Components\Toggle::make('is_base_unit')
                    ->label('Es Unidad Base')
                    ->default(false),
                Forms\Components\TextInput::make('conversion_factor')
                    ->label('Factor de Conversión')
                    ->numeric()
                    ->visible(
                        fn(Get $get) =>
                        $get('is_base_unit') === false
                    ),
            ]),
        ];
    }
}
