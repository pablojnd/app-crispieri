<?php

namespace App\Filament\Clusters\ProductManagement\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Category;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Clusters\ProductManagement;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Clusters\ProductManagement\Resources\CategoryResource\Pages;
use App\Filament\Clusters\ProductManagement\Resources\CategoryResource\RelationManagers;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 2;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    // protected static ?string $tenantRelationshipName = 'store';

    protected static bool $isScopedToTenant = true;

    protected static ?string $modelLabel = 'Categoría';

    protected static ?string $pluralModelLabel = 'Categorías';

    protected static ?string $cluster = ProductManagement::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        // Sección principal (2/3 del ancho)
                        Forms\Components\Section::make('Información de Categoría')
                            ->description('Detalles de la categoría de producto')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('parent_id')
                                            ->label('Categoría Padre')
                                            ->relationship(
                                                name: 'parent',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant())
                                            )
                                            ->preload()
                                            ->searchable()
                                            ->placeholder('Seleccione una categoría padre')
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('name')
                                            ->label('Nombre')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                                                if (($get('slug') ?? '') !== Str::slug($old)) {
                                                    return;
                                                }

                                                $set('slug', Str::slug($state));
                                            })
                                            ->columnSpan(1),
                                    ]),

                                Forms\Components\TextInput::make('slug')
                                    ->label('URL Amigable')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->helperText('URL única para la categoría')
                                    ->prefixIcon('heroicon-o-link'),

                                Forms\Components\Textarea::make('description')
                                    ->label('Descripción')
                                    ->maxLength(255)
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(2),

                        // Sección de configuración (1/3 del ancho)
                        Forms\Components\Section::make('Configuración')
                            ->schema([
                                // Placeholder para contar productos
                                Forms\Components\Placeholder::make('product_count')
                                    ->label('Productos en esta Categoría')
                                    ->content(function (?Category $record) {
                                        if (!$record) return 'N/A';

                                        $directCount = $record->productCount();
                                        $recursiveCount = $record->recursiveProductCount();

                                        return "Directos: {$directCount} | Total (incluyendo subcategorías): {$recursiveCount}";
                                    }),

                                Forms\Components\Toggle::make('status')
                                    ->label('Activo')
                                    ->default(true)
                                    ->helperText('Habilitar o deshabilitar categoría'),
                                Forms\Components\Toggle::make('is_featured')
                                    ->label('Categoría Destacada')
                                    ->helperText('Mostrar en página principal'),


                                Forms\Components\FileUpload::make('image')
                                    ->label('Imagen de Categoría')
                                    ->image()
                                    ->directory('category-images')
                                    ->visibility('public')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('parent_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
