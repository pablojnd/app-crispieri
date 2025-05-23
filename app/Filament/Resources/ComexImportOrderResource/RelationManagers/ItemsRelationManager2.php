<?php

namespace App\Filament\Resources\ComexImportOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use App\Models\Attribute;
use App\Models\ComexItem;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Models\AttributeValue;
use Filament\Facades\Filament;
use App\Imports\ComexItemImporter;
use Filament\Support\Enums\MaxWidth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class ItemsRelationManager2 extends RelationManager
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
                    // ->relationship(
                    //     name: 'product',
                    //     titleAttribute: 'product_name',
                    //     modifyQueryUsing: fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant())
                    // )
                    ->searchable(['product_name', 'code'])
                    ->preload()
                    ->getSearchResultsUsing(fn(string $search): array => Product::getSelectSearchResults($search))
                    ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->getFormattedLabel())
                    ->createOptionForm(static::getProductsFormSchema())
                    ->required()
                    ->label('Producto')
                    ->columnSpan(3),

                Forms\Components\TextInput::make('package_quality')
                    ->required()
                    ->label('Cantidad de Bulto'),

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
                Tables\Actions\CreateAction::make()->label('Agregar Item')->modalWidth(MaxWidth::SixExtraLarge),
                Tables\Actions\Action::make('import')
                    ->label('Importar Items')
                    ->modalWidth('lg')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('Archivo CSV')
                            ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel'])
                            ->required()
                    ])
                    ->action(function (array $data) {
                        $importer = new ComexItemImporter($this->getOwnerRecord());
                        Excel::import($importer, $data['file']);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Importación completada')
                            ->send();
                    })
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
                                Forms\Components\Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->label('Categoría')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nombre de la Categoría')
                                            ->required(),
                                    ])
                                    ->required(),
                                Forms\Components\Select::make('brand_id')
                                    ->relationship('brand', 'name')
                                    ->label('Marca')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nombre de la Marca')
                                            ->required(),
                                    ]),
                                Forms\Components\Select::make('measurement_unit_id')
                                    ->relationship('measurementUnit', 'name')
                                    ->label('Unidad de Medida')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm(function () {
                                        return static::getMeasurementUnitsFormSchema();
                                    })
                                    ->required(),
                                Forms\Components\TextInput::make('hs_code')
                                    ->label('Código HS')
                                    ->helperText('Código de clasificación arancelaria'),

                                Forms\Components\TextInput::make('code')
                                    ->label('Código Interno')
                                    ->helperText('Código interno del producto'),
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
                    //         Forms\Components\Section::make('Atributos del producto')
                    //             ->description('Los atributos podrán ser configurados después de guardar el producto')
                    //             ->schema([
                    //                 Forms\Components\Placeholder::make('attribute_notice')
                    //                     ->content('Los atributos estarán disponibles después de crear el producto.')
                    //                     ->columnSpanFull(),
                    //             ]),
                    //     ]),

                    // Tab 6: Datos de Proveedor
                    Forms\Components\Tabs\Tab::make('Proveedor')
                        ->icon('heroicon-o-building-office')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\Select::make('supplier_id')
                                    ->label('Proveedor')
                                    ->relationship('supplier', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\TextInput::make('supplier_code')
                                    ->label('Código de Proveedor'),
                                Forms\Components\TextInput::make('barcode')
                                    ->label('Código de Barras'),
                                Forms\Components\TextInput::make('ean_code')
                                    ->label('Código EAN'),
                            ]),
                        ]),

                    // Tab 7: Multimedia
                    Forms\Components\Tabs\Tab::make('Multimedia')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Forms\Components\FileUpload::make('image')
                                ->label('Imágenes')
                                ->multiple()
                                ->image()
                                ->maxFiles(5)
                                ->columnSpanFull(),
                        ]),
                ])
                // ->persistTab()
                ->id('product-tabs')
                ->columnSpanFull()
        ];
    }

    public static function getMeasurementUnitsFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('code')
                    ->label('Código')
                    ->required()
                    ->maxLength(50)
                    ->unique('measurement_units', 'code'),

                Forms\Components\TextInput::make('abbreviation')
                    ->label('Abreviatura')
                    ->required()
                    ->maxLength(10),

                Forms\Components\Textarea::make('description')
                    ->label('Descripción')
                    ->maxLength(500)
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_base_unit')
                    ->label('Es Unidad Base')
                    ->default(false)
                    ->reactive(),

                Forms\Components\TextInput::make('conversion_factor')
                    ->label('Factor de Conversión')
                    ->numeric()
                    ->minValue(0)
                    ->default(1)
                    ->required()
                    ->visible(fn(Get $get): bool => ! $get('is_base_unit'))
                    ->rules([
                        fn(Get $get): string => $get('is_base_unit') ? 'nullable' : 'required',
                    ]),
            ]),
        ];
    }
}
