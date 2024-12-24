<?php

namespace App\Filament\Clusters\ProductManagement\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use App\Models\ProductAttribute;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use App\Models\ProductAttributeValue;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Clusters\ProductManagement;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Clusters\ProductManagement\Resources\ProductResource\Pages;
use App\Filament\Clusters\ProductManagement\Resources\ProductResource\RelationManagers;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $cluster = ProductManagement::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    protected static ?string $tenantOwnershipRelationshipName = 'store';

    protected static ?string $tenantRelationshipName = 'products';

    protected static ?string $modelLabel = 'Producto';

    protected static ?string $pluralModelLabel = 'Productos';

    public static function form(Form $form): Form
    {
        return $form->schema([
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
                                    ->dehydrateStateUsing(fn(string $state) => strtoupper($state))
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                                        if (($get('slug') ?? '') !== Str::slug($old)) {
                                            return;
                                        }
                                        $set('slug', Str::slug($state));
                                    }),
                                Forms\Components\TextInput::make('slug')
                                    ->label('URL Amigable')
                                    ->required(),
                                Forms\Components\Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->label('Categoría')
                                    ->required(),
                                Forms\Components\Select::make('brand_id')
                                    ->relationship('brand', 'name')
                                    ->label('Marca'),
                                Forms\Components\Select::make('measurement_unit_id')
                                    ->relationship('measurementUnit', 'name')
                                    ->label('Unidad de Medida')
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

                    // Tab 2: Precios y Ofertas
                    Forms\Components\Tabs\Tab::make('Precios y Ofertas')
                        ->icon('heroicon-o-currency-dollar')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('price')
                                    ->label('Precio Regular')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$'),
                                Forms\Components\TextInput::make('offer_price')
                                    ->label('Precio Oferta')
                                    ->numeric()
                                    ->prefix('$'),
                                Forms\Components\DatePicker::make('offer_start_date')
                                    ->label('Inicio de Oferta'),
                                Forms\Components\DatePicker::make('offer_end_date')
                                    ->label('Fin de Oferta'),
                            ]),
                        ]),

                    // Tab 3: Inventario y Logística
                    Forms\Components\Tabs\Tab::make('Inventario')
                        ->icon('heroicon-o-truck')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('stock')
                                    ->label('Stock Actual')
                                    ->numeric()
                                    ->required(),
                                Forms\Components\TextInput::make('minimum_stock')
                                    ->label('Stock Mínimo')
                                    ->numeric(),
                                Forms\Components\TextInput::make('maximum_stock')
                                    ->label('Stock Máximo')
                                    ->numeric(),
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
                    Forms\Components\Tabs\Tab::make('Atributos')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->schema([
                            Forms\Components\Repeater::make('members')
                                ->schema([
                                    Forms\Components\Select::make('role')
                                        ->relationship('attributes', 'attribute_name')
                                        ->required(),
                                    Forms\Components\Select::make('role')
                                        ->relationship('attributeValues', 'value_name')
                                        ->required(),
                                ])
                                ->columns(2)
                        ]),

                    // Tab 6: Datos de Proveedor
                    Forms\Components\Tabs\Tab::make('Proveedor')
                        ->icon('heroicon-o-building-office')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('supplier_code')
                                    ->label('Código de Proveedor'),
                                Forms\Components\TextInput::make('supplier_reference')
                                    ->label('Referencia de Proveedor'),
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
                ->persistTab()
                ->id('product-tabs')
                ->columnSpanFull()
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label(__('Imagen'))
                    ->circular()
                    ->stacked(),

                Tables\Columns\TextColumn::make('product_name')
                    ->label(__('Nombre'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('Categoría'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('brand.name')
                    ->label(__('Marca'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sku')
                    ->label(__('SKU'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label(__('Precio'))
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock')
                    ->label(__('Stock'))
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->sortable()
                    ->color(
                        fn(Product $record): string =>
                        $record->stock <= $record->minimum_stock
                            ? 'danger'
                            : 'success'
                    ),

                Tables\Columns\IconColumn::make('status')
                    ->label(__('Estado'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Creado'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship(
                        'category',
                        'name',
                        fn(Builder $query) =>
                        $query->where('store_id', Filament::getTenant()->id)
                    )
                    ->label(__('Categoría'))
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('brand')
                    ->relationship(
                        'brand',
                        'name',
                        fn(Builder $query) =>
                        $query->where('store_id', Filament::getTenant()->id)
                    )
                    ->label(__('Marca'))
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('status')
                    ->label(__('Estado'))
                    ->boolean()
                    ->trueLabel(__('Activo'))
                    ->falseLabel(__('Inactivo'))
                    ->native(false),

                Tables\Filters\Filter::make('low_stock')
                    ->label(__('Stock Bajo'))
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->whereColumn('stock', '<=', 'minimum_stock')
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->modalWidth(MaxWidth::SevenExtraLarge),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
