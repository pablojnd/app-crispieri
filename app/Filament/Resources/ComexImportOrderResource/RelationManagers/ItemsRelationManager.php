<?php

namespace App\Filament\Resources\ComexImportOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Awcodes\TableRepeater\Header;
use App\Imports\ComexItemImporter;
use Illuminate\Support\HtmlString;
use Filament\Support\Enums\MaxWidth;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Builder;
use Awcodes\TableRepeater\Components\TableRepeater;
use Filament\Resources\RelationManagers\RelationManager;
use League\Csv\Writer;
use Illuminate\Http\Response;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $recordTitleAttribute = 'product.name';
    protected static ?string $title = 'Items';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Producto')
                    ->options(fn() => Product::query()
                        ->whereBelongsTo(\Filament\Facades\Filament::getTenant())
                        ->with(['product_attribute_values.attribute', 'product_attribute_values.attributeValue'])
                        ->get()
                        ->mapWithKeys(fn(Product $product) => [
                            $product->id => "{$product->product_name} | Código: {$product->code} | " .
                                $product->product_attribute_values
                                ->map(fn($pav) => "{$pav->attribute->name}: {$pav->attributeValue->value}")
                                ->join(' | ')
                        ]))
                    ->searchable()
                    ->preload()
                    ->createOptionForm(static::getProductsFormSchema())
                    ->createOptionUsing(function (array $data, $livewire) {
                        $product = Product::create([
                            ...$data,
                            'store_id' => \Filament\Facades\Filament::getTenant()->id,
                            'supplier_id' => $livewire->ownerRecord->provider_id,
                            'status' => true,
                        ]);

                        if (!empty($data['product_attributes'])) {
                            foreach ($data['product_attributes'] as $attribute) {
                                $product->product_attribute_values()->create([
                                    'attribute_id' => $attribute['attribute_id'],
                                    'attribute_value_id' => $attribute['attribute_value_id'],
                                ]);
                            }
                        }

                        return $product->id;
                    })
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('package_quality')
                    ->label('Cantidad de Bultos'),

                Forms\Components\TextInput::make('quantity')
                    ->label('Cantidad')
                    ->required()
                    ->numeric()
                    ->minValue(0),

                Forms\Components\TextInput::make('total_price')
                    ->label('Precio Total')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('$'),

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
            ->columns(5);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Producto')
                    ->formatStateUsing(fn($record) => "{$record->product->product_name} | Código: {$record->product->code} |")
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Precio Total')
                    ->money('USD')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()),

                Tables\Columns\TextColumn::make('package_quality')
                    ->label('Bultos')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Item')
                    ->modalWidth(MaxWidth::FiveExtraLarge),

                // Acción mejorada para importar productos desde CSV
                Tables\Actions\Action::make('importCsv')
                    ->label('Importar CSV')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\FileUpload::make('csv_file')
                            ->label('Archivo CSV')
                            ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel'])
                            ->maxSize(5120) // 5MB
                            ->required(),

                        Forms\Components\Section::make('Instrucciones')
                            ->collapsible()
                            ->description('El archivo CSV debe contener las siguientes columnas:')
                            ->schema([
                                Forms\Components\Placeholder::make('columns_required')
                                    ->label('Columnas requeridas')
                                    ->content(new HtmlString('
                                        <div class="font-medium text-primary-600">
                                            • product_name - Nombre del producto<br>
                                            • quantity - Cantidad del producto (usar punto como decimal, ej: 1401.12)<br>
                                            • total_price - Precio total del producto (usar punto como decimal)
                                        </div>')),

                                Forms\Components\Placeholder::make('note')
                                    ->label('Importante')
                                    ->content(new HtmlString('
                                        <div class="font-medium text-warning-600">
                                            Para campos numéricos como cantidad y precio, puede usar punto o coma como separador decimal.<br>
                                            Por ejemplo: 1401.12 ó 1401,12
                                        </div>')),

                                Forms\Components\Placeholder::make('columns_recommended')
                                    ->label('Columnas recomendadas')
                                    ->content(new HtmlString('
                                        <div class="text-success-600">
                                            • code - Código del producto<br>
                                            • package_quality - Cantidad de bultos<br>
                                            • category_name - Nombre de la categoría<br>
                                            • brand_name - Nombre de la marca
                                        </div>')),

                                Forms\Components\Placeholder::make('columns_optional')
                                    ->label('Columnas opcionales')
                                    ->content(new HtmlString('
                                        <div class="text-gray-600">
                                            • description - Descripción del producto<br>
                                            • hs_code - Código HS<br>
                                            • supplier_code - Código del proveedor<br>
                                            • weight - Peso del producto (kg)<br>
                                            • length - Largo del producto (cm)<br>
                                            • width - Ancho del producto (cm)<br>
                                            • height - Alto del producto (cm)<br>
                                            • packing_type - Tipo de empaque<br>
                                            • packing_quantity - Cantidad por empaque<br>
                                            • measurement_unit_id - ID de la unidad de medida
                                        </div>')),

                                Forms\Components\Placeholder::make('columns_attributes')
                                    ->label('Atributos de producto')
                                    ->content(new HtmlString('
                                        <div class="font-medium text-amber-600">
                                            Para incluir atributos, puede agregar columnas con el prefijo "attribute_" seguido del nombre del atributo.<br>
                                            Por ejemplo:<br>
                                            • attribute_color - Color del producto<br>
                                            • attribute_size - Talla del producto<br>
                                            • attribute_material - Material del producto
                                        </div>')),

                                Forms\Components\Placeholder::make('example_with_attributes')
                                    ->label('Ejemplo con atributos')
                                    ->content(new HtmlString('
                                        <div class="p-3 font-mono text-xs text-gray-900 bg-gray-100 rounded">
                                        product_name,code,quantity,total_price,package_quality,category_name,brand_name,attribute_color,attribute_size<br>
                                        "Camisa Azul","SHIRT001",100,1500,10,"Ropa","Marca A","Azul","XL"<br>
                                        "Pantalón Negro","PANT002",50,2000,5,"Ropa","Marca B","Negro","32"
                                        </div>')),

                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('downloadTemplate')
                                        ->label('Descargar plantilla CSV')
                                        ->icon('heroicon-o-document-arrow-down')
                                        ->color('gray')
                                        ->action(function () {
                                            // Columnas básicas
                                            $headers = [
                                                // Datos básicos del producto
                                                'product_name',
                                                'code',
                                                'quantity',
                                                'total_price',
                                                'package_quality',
                                                'category_name',
                                                'brand_name',
                                                'description',

                                                // Datos logísticos
                                                'hs_code',
                                                'supplier_code',
                                                'packing_type',
                                                'packing_quantity',
                                                'weight',
                                                'length',
                                                'width',
                                                'height',

                                                // Otros códigos
                                                'barcode',
                                                'ean_code',

                                                // Impuestos
                                                'is_taxable',
                                                'tax_rate',

                                                // Niveles de stock
                                                'minimum_stock',
                                                'maximum_stock',

                                                // Ofertas
                                                'offer_price',
                                                'offer_start_date',
                                                'offer_end_date',

                                                // Atributos comunes (se autogeneran si no existen)
                                                'attribute_color',
                                                'attribute_size',
                                                'attribute_material',
                                                'attribute_style'
                                            ];

                                            $csv = Writer::createFromString();
                                            $csv->insertOne($headers);

                                            // Añadir un par de ejemplos
                                            $csv->insertOne([
                                                // Datos básicos
                                                'Camisa Azul Formal', 'SHIRT001', '100,50', '1500,75', '10', 'Ropa', 'Marca A',
                                                'Camisa de algodón color azul para oficina',

                                                // Datos logísticos
                                                'HS12345', 'SUP001', 'Caja', '12', '0,5', '60', '40', '2',

                                                // Otros códigos
                                                '7890123456789', 'EAN-123456',

                                                // Impuestos
                                                'true', '16',

                                                // Niveles de stock
                                                '10', '100',

                                                // Ofertas
                                                '1200', '2024-05-01', '2024-05-31',

                                                // Atributos
                                                'Azul', 'XL', 'Algodón', 'Formal'
                                            ]);

                                            $csv->insertOne([
                                                // Datos básicos
                                                'Pantalón Negro', 'PANT002', '50', '2000', '5', 'Ropa', 'Marca B',
                                                'Pantalón negro para caballero',

                                                // Datos logísticos
                                                'HS67890', 'SUP002', 'Paquete', '6', '0,7', '80', '35', '5',

                                                // Otros códigos
                                                '7890123456790', 'EAN-789012',

                                                // Impuestos
                                                'false', '',

                                                // Niveles de stock
                                                '5', '50',

                                                // Ofertas
                                                '1800', '2024-06-01', '2024-06-15',

                                                // Atributos
                                                'Negro', '32', 'Poliéster', 'Casual'
                                            ]);

                                            return response()->streamDownload(
                                                fn () => print($csv->toString()),
                                                'productos_completa_template.csv',
                                                [
                                                    'Content-Type' => 'text/csv',
                                                ]
                                            );
                                        }),
                                ]),
                            ]),
                    ])
                    ->modalWidth(MaxWidth::FiveExtraLarge)
                    ->action(function (array $data): void {
                        // Validar y procesar el archivo
                        $file = $data['csv_file'];

                        if (is_string($file)) {
                            $filePath = storage_path('app/public/' . $file);
                        } else {
                            $filePath = $file->getRealPath();
                        }

                        // Procesar la importación usando ComexItemImporter
                        try {
                            Excel::import(
                                new ComexItemImporter($this->getOwnerRecord()),
                                $filePath
                            );

                            // Notificar éxito
                            Notification::make()
                                ->title('Productos importados correctamente')
                                ->success()
                                ->send();

                            // Eliminamos la llamada a refreshRecords() ya que no existe
                            // Filament automáticamente actualizará los datos después de la acción
                        } catch (\Exception $e) {
                            // Notificar error con más detalles
                            Notification::make()
                                ->title('Error al importar')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth(MaxWidth::FiveExtraLarge),
                Tables\Actions\DeleteAction::make(),
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
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\TextInput::make('product_name')
                                        ->label('Nombre del Producto')
                                        ->required()
                                        ->maxLength(255),

                                    Forms\Components\TextInput::make('code')
                                        ->label('Código')
                                        ->required()
                                        ->maxLength(50),

                                    Forms\Components\Select::make('category_id')
                                        ->label('Categoría')
                                        ->options(fn() => \App\Models\Category::query()
                                            ->whereBelongsTo(\Filament\Facades\Filament::getTenant())
                                            ->pluck('name', 'id'))
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('name')
                                                ->required()
                                                ->maxLength(255),
                                        ])
                                        ->createOptionUsing(function (array $data) {
                                            return \App\Models\Category::create([
                                                'name' => $data['name'],
                                                'store_id' => \Filament\Facades\Filament::getTenant()->id,
                                                'status' => true,
                                            ])->id;
                                        }),

                                    Forms\Components\Select::make('brand_id')
                                        ->label('Marca')
                                        ->options(fn() => \App\Models\Brand::query()
                                            ->whereBelongsTo(\Filament\Facades\Filament::getTenant())
                                            ->pluck('name', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('name')
                                                ->required()
                                                ->maxLength(255),
                                        ])
                                        ->createOptionUsing(function (array $data) {
                                            return \App\Models\Brand::create([
                                                'name' => $data['name'],
                                                'store_id' => \Filament\Facades\Filament::getTenant()->id,
                                                'is_active' => true,
                                            ])->id;
                                        }),

                                    Forms\Components\Select::make('measurement_unit_id')
                                        ->label('Unidad de Medida')
                                        ->options(fn() => \App\Models\MeasurementUnit::query()
                                            ->pluck('name', 'id'))
                                        ->required()
                                        ->searchable()
                                        ->preload(),
                                ]),
                            Forms\Components\RichEditor::make('description')
                                ->label('Descripción')
                                ->columnSpanFull(),
                        ]),

                    // Tab 2: Inventario y Logística
                    Forms\Components\Tabs\Tab::make('Inventario y Logística')
                        ->icon('heroicon-o-truck')
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    // Forms\Components\TextInput::make('stock')
                                    //     ->label('Stock Actual')
                                    //     ->numeric(),
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
                                ->schema([
                                    Forms\Components\Grid::make(4)
                                        ->schema([
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

                    // Tab 3: Códigos y Referencias
                    Forms\Components\Tabs\Tab::make('Códigos y Referencias')
                        ->icon('heroicon-o-qr-code')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('hs_code')
                                        ->label('Código HS'),
                                    Forms\Components\TextInput::make('barcode')
                                        ->label('Código de Barras'),
                                    Forms\Components\TextInput::make('ean_code')
                                        ->label('Código EAN'),
                                    Forms\Components\TextInput::make('supplier_code')
                                        ->label('Código de Proveedor'),
                                    // Forms\Components\TextInput::make('supplier_reference')
                                    //     ->label('Referencia de Proveedor'),
                                ]),
                        ]),

                    // Tab 4: Proveedor
                    Forms\Components\Tabs\Tab::make('Proveedor')
                        ->icon('heroicon-o-building-office')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('supplier_id')
                                        ->label('Proveedor')
                                        ->options(fn($get, $livewire) => [
                                            $livewire->ownerRecord->provider_id => $livewire->ownerRecord->provider->name
                                        ])
                                        ->default(fn($livewire) => $livewire->ownerRecord->provider_id),

                                    Forms\Components\TextInput::make('supplier_code')
                                        ->label('Código de Proveedor'),
                                    // Forms\Components\TextInput::make('supplier_reference')
                                    //     ->label('Referencia de Proveedor'),
                                ]),
                        ]),

                    // Tab 5: Multimedia
                    Forms\Components\Tabs\Tab::make('Multimedia')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Forms\Components\FileUpload::make('image')
                                ->label('Imágenes')
                                ->multiple()
                                ->image()
                                ->imageResizeMode('contain')
                                ->imageCropAspectRatio('16:9')
                                ->imageResizeTargetWidth('1920')
                                ->imageResizeTargetHeight('1080')
                                ->maxFiles(5)
                                ->reorderable()
                                ->columnSpanFull(),
                        ]),
                    // Tab 6: Atributos
                    Forms\Components\Tabs\Tab::make('Atributos')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->schema([
                            Forms\Components\Section::make('Atributos del producto')
                                ->description('Seleccione los atributos y sus valores correspondientes')
                                ->schema([
                                    TableRepeater::make('product_attributes')
                                        ->headers([
                                            Header::make('attribute_id')
                                                ->label('Atributo')
                                                ->width('200px')
                                                ->markAsRequired(),
                                            Header::make('attribute_value_id')
                                                ->label('Valor')
                                                ->width('200px')
                                                ->markAsRequired(),
                                        ])
                                        ->schema([
                                            Forms\Components\Select::make('attribute_id')
                                                ->label('Atributo')
                                                ->options(fn() => \App\Models\Attribute::query()
                                                    ->whereBelongsTo(\Filament\Facades\Filament::getTenant())
                                                    ->pluck('name', 'id'))
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(fn(Forms\Set $set) => $set('attribute_value_id', null))
                                                ->createOptionForm([
                                                    Forms\Components\TextInput::make('name')
                                                        ->label('Nombre')
                                                        ->required()
                                                        ->maxLength(255),
                                                    Forms\Components\Toggle::make('is_required')
                                                        ->label('¿Es requerido?'),
                                                ]),

                                            Forms\Components\Select::make('attribute_value_id')
                                                ->label('Valor')
                                                ->options(function (Forms\Get $get) {
                                                    $attributeId = $get('attribute_id');
                                                    if (!$attributeId) return [];

                                                    return \App\Models\AttributeValue::query()
                                                        ->where('attribute_id', $attributeId)
                                                        ->pluck('value', 'id');
                                                })
                                                ->required()
                                                ->live()
                                                ->createOptionForm([
                                                    Forms\Components\TextInput::make('value')
                                                        ->label('Valor')
                                                        ->required()
                                                        ->maxLength(255),
                                                ])
                                                ->createOptionUsing(function (array $data, Forms\Get $get) {
                                                    return \App\Models\AttributeValue::create([
                                                        'attribute_id' => $get('attribute_id'),
                                                        'value' => $data['value'],
                                                    ])->id;
                                                }),
                                        ])
                                        ->columnSpanFull()
                                        ->defaultItems(0)
                                        ->addActionLabel('Agregar atributo')
                                        ->emptyLabel('No hay atributos configurados')
                                        ->streamlined()
                                        ->showLabels(false),
                                ]),
                        ]),
                ])
                ->columnSpanFull(),
        ];
    }
}
