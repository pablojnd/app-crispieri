<?php

namespace App\Filament\Resources\ComexImportOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
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
                    // ->createOptionForm([
                    //     Forms\Components\TextInput::make('name')
                    //         ->required()
                    //         ->maxLength(255),
                    //     Forms\Components\TextInput::make('sku')
                    //         ->required()
                    //         ->maxLength(255),
                    // ])
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
            ->columns(3);
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
                    ->label('Precio Total'),

                Tables\Columns\TextColumn::make('cif_unit')
                    ->numeric(
                        decimalPlaces: 4,
                        thousandsSeparator: '.',
                        decimalSeparator: ','
                    )
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
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
