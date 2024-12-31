<?php

namespace App\Filament\Clusters\ProductManagement\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Attribute;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Clusters\ProductManagement;
use App\Filament\Clusters\ProductManagement\Resources\AttributeResource\Pages;

class AttributeResource extends Resource
{
    protected static ?string $model = Attribute::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $cluster = ProductManagement::class;

    protected static ?string $modelLabel = 'Atributo';

    protected static ?string $pluralModelLabel = 'Atributos';

    protected static ?string $tenantOwnershipRelationshipName = 'store';

    protected static ?int $navigationSort = 4;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Nombre del atributo')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->columnSpan(1),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Toggle::make('is_required')
                                        ->label('¿Es requerido?')
                                        ->default(false),
                                    Forms\Components\Toggle::make('is_active')
                                        ->label('¿Está activo?')
                                        ->default(true),
                                ])
                                ->columnSpan(1),
                        ]),

                    Forms\Components\Section::make('Valores del atributo')
                        ->schema([
                            Forms\Components\Repeater::make('values')
                                ->label(false)
                                ->relationship()
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('value')
                                                ->label('Valor')
                                                ->required()
                                                ->maxLength(255)

                                        ]),
                                ])
                                ->itemLabel(
                                    fn(array $state): ?string =>
                                    $state['value'] ?? null
                                )
                                ->collapsible()
                                ->reorderableWithButtons()
                                ->defaultItems(1)
                                ->addActionLabel('Agregar valor')
                                ->columnSpanFull(),
                        ])
                        ->collapsible(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_required')
                    ->label('Requerido')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('values_count')
                    ->label('Valores')
                    ->counts('values')
                    ->sortable(),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Productos')
                    ->counts('products')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->native(false),

                Tables\Filters\TernaryFilter::make('is_required')
                    ->label('Requerido')
                    ->boolean()
                    ->trueLabel('Requeridos')
                    ->falseLabel('Opcionales')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                // ->before(function (AttributeResource $resource, Model $record) {
                //     if ($record->products()->count() > 0) {
                //         Notification::make()
                //             ->warning()
                //             ->title('No se puede eliminar')
                //             ->body('Este atributo está siendo usado por productos.')
                //             ->send();

                //         $record->update(['is_active' => false]);
                //         return redirect()->back();
                //     }
                // }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(
                fn(Builder $query) => $query
                    ->withCount(['values', 'products'])
            );
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
            'index' => Pages\ListAttributes::route('/'),
            'create' => Pages\CreateAttribute::route('/create'),
            'edit' => Pages\EditAttribute::route('/{record}/edit'),
        ];
    }
}
