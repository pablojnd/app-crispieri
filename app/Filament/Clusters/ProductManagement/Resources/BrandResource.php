<?php

namespace App\Filament\Clusters\ProductManagement\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Brand;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Clusters\ProductManagement;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Clusters\ProductManagement\Resources\BrandResource\Pages;
use App\Filament\Clusters\ProductManagement\Resources\BrandResource\RelationManagers;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $navigationIcon = 'heroicon-o-bookmark';

    protected static ?int $navigationSort = 3;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    // protected static ?string $tenantRelationshipName = 'store';

    protected static bool $isScopedToTenant = true;

    protected static ?string $modelLabel = 'Marca';

    protected static ?string $pluralModelLabel = 'Marcas';

    protected static ?string $cluster = ProductManagement::class;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\Section::make('Información Básica')
                        ->description('Datos principales de la marca')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Nombre')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('slug')
                                ->label('Slug')
                                ->helperText('URL amigable para la marca'),

                            Forms\Components\Textarea::make('description')
                                ->label('Descripción')
                                ->maxLength(500)
                                ->columnSpanFull(),
                        ])->columns(2),

                    Forms\Components\Section::make('Multimedia')
                        ->schema([
                            // Forms\Components\FileUpload::make('logo')
                            //     ->label('Logo')
                            //     ->image()
                            //     ->directory('brands')
                            //     ->imageEditor()
                            //     ->columnSpanFull(),
                        ]),

                    Forms\Components\Section::make('Configuración')
                        ->schema([
                            Forms\Components\Toggle::make('is_active')
                                ->label('Activa')
                                ->default(true),
                            // Forms\Components\Toggle::make('is_featured')
                            //     ->label('Destacada'),
                        ])->columns(2),
                ])->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('products_count')
                    ->label('Productos')
                    ->counts('products')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Destacada')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activa'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Destacada'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }
}
