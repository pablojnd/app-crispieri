<?php

namespace App\Filament\Resources\ComexImportOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Currency;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\RelationManagers\RelationManager;

class ExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';
    protected static ?string $title = 'Gastos';
    protected static ?string $modelLabel = 'Gasto';
    protected static ?string $pluralModelLabel = 'Gastos';
    protected static ?string $recordTitleAttribute = 'expense_type';

    protected static ?string $tenantOwnershipRelationshipName = 'store';

    // Configuración explícita de la relación
    protected static ?string $inverseRelationship = 'importOrder';
    protected static ?string $foreignKeyName = 'import_order_id';
    protected static ?string $inverseRelationshipForeignKeyName = 'import_order_id';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Select::make('currency_id')
                    ->relationship('currency', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Moneda'),

                Forms\Components\DatePicker::make('expense_date')
                    ->required()
                    ->default(now())
                    ->label('Fecha'),

                Forms\Components\Select::make('expense_type')
                    ->options([
                        'gate_in' => 'Gate In',
                        'thc' => 'THC',
                        'manifest_opening' => 'Apertura de Manifiesto',
                        'guarantee' => 'Garantía',
                        'liability_letter' => 'Carta de Responsabilidad',
                        'bl_issuance' => 'Emisión B/L',
                        'demurrage' => 'Demurrage',
                        'container_movement' => 'Movimiento de Contenedor',
                        'cranes' => 'Grúas',
                        'unloading' => 'Descarga',
                        'other' => 'Otro'
                    ])
                    ->required()
                    ->label('Tipo de Gasto'),

                Forms\Components\TextInput::make('expense_quantity')
                    ->numeric()
                    ->label('Cantidad'),

                Forms\Components\TextInput::make('expense_amount')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->prefix('$')
                    ->label('Monto'),
            ]),

            Forms\Components\Textarea::make('notes')
                ->maxLength(500)
                ->columnSpanFull()
                ->label('Notas'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('expense_type')
            ->columns([
                Tables\Columns\TextColumn::make('expense_type')
                    ->badge()
                    ->sortable()
                    ->label('Tipo'),

                Tables\Columns\TextColumn::make('expense_date')
                    ->date()
                    ->sortable()
                    ->label('Fecha'),

                Tables\Columns\TextColumn::make('expense_quantity')
                    ->numeric(2)
                    ->sortable()
                    ->label('Cantidad'),

                Tables\Columns\TextColumn::make('expense_amount')
                    ->money('USD')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('USD'))
                    ->label('Monto'),

                Tables\Columns\TextColumn::make('currency.name')
                    ->sortable()
                    ->searchable()
                    ->label('Moneda'),

                Tables\Columns\TextColumn::make('notes')
                    ->limit(30)
                    ->tooltip(function ($record): ?string {
                        return $record->notes ?? null;
                    })
                    ->label('Notas'),
            ])
            ->defaultSort('expense_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('expense_type')
                    ->options([
                        'gate_in' => 'Gate In',
                        'thc' => 'THC',
                        'manifest_opening' => 'Apertura de Manifiesto',
                        'guarantee' => 'Garantía',
                        'liability_letter' => 'Carta de Responsabilidad',
                        'bl_issuance' => 'Emisión B/L',
                        'demurrage' => 'Demurrage',
                        'container_movement' => 'Movimiento de Contenedor',
                        'cranes' => 'Grúas',
                        'unloading' => 'Descarga',
                        'other' => 'Otro'
                    ])
                    ->label('Tipo'),
                // Tables\Filters\DateRangeFilter::make('expense_date')
                //     ->label('Rango de Fechas'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Agregar Gasto'),
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
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }
}
