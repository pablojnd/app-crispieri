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
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                        if (($get('slug') ?? '') !== Str::slug($old)) {
                            return;
                        }

                        $set('slug', Str::slug($state));
                    }),

                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('description')
                    ->label('Descripción')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\FileUpload::make('logo')
                    ->label('Logo')
                    ->image()
                    ->directory('logos'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->size(80),
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Tienda')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
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
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }
}
