<?php

namespace App\Filament\Resources\ComexImportOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Currency;
use Filament\Forms\Form;
use App\Enums\ExpenseType;
use Filament\Tables\Table;
use App\Enums\PaymentStatus;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\RelationManagers\RelationManager;

class ExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';
    protected static ?string $title = 'Gastos';
    protected static ?string $modelLabel = 'Gasto';
    protected static ?string $pluralModelLabel = 'Gastos';
    protected static ?string $recordTitleAttribute = 'expense_type';

    // protected static ?string $tenantOwnershipRelationshipName = 'store';

    // Configuración explícita de la relación
    protected static ?string $inverseRelationship = 'importOrder';
    protected static ?string $foreignKeyName = 'import_order_id';
    protected static ?string $inverseRelationshipForeignKeyName = 'import_order_id';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\Select::make('documents')
                    ->label('Documentos')
                    ->multiple()
                    ->relationship(
                        'documents',
                        'document_number',
                        fn($query) => $query->where('import_order_id', $this->ownerRecord->id)
                    )
                    ->preload(),

                Forms\Components\Select::make('shipping_lines')
                    ->label('Navieras')
                    ->multiple()
                    ->relationship(
                        'shippingLines',
                        'name',
                        fn($query) => $query->whereHas(
                            'comexShippingLineContainer',
                            fn($q) => $q->where('import_order_id', $this->ownerRecord->id)
                        )
                    )
                    ->preload(),

                Forms\Components\Select::make('containers')
                    ->label('Contenedores')
                    ->multiple()
                    ->relationship(
                        'containers',
                        'container_number',
                        fn($query) => $query->where('import_order_id', $this->ownerRecord->id)
                    )
                    ->preload(),

                Forms\Components\Select::make('currency_id')
                    ->label('Moneda')
                    ->relationship('currency', 'name')
                    ->required(),

                Forms\Components\DatePicker::make('expense_date')
                    ->label('Fecha')
                    ->required(),

                Forms\Components\Select::make('expense_type')
                    ->label('Tipo de Gasto')
                    ->options(ExpenseType::class)
                    ->required(),

                Forms\Components\TextInput::make('expense_quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->default(1),

                Forms\Components\TextInput::make('expense_amount')
                    ->label('Monto')
                    ->required()
                    ->numeric()
                    ->default(0),

                Forms\Components\Select::make('payment_status')
                    ->label('Estado de Pago')
                    ->options(PaymentStatus::class)
                    ->default('pending')
                    ->required(),

                Forms\Components\Textarea::make('notes')
                    ->label('Notas')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('expense_type')
            ->columns([
                Tables\Columns\SelectColumn::make('payment_status')
                    ->options(PaymentStatus::class)
                    ->label('Estado de Pago'),

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

                Tables\Columns\TextColumn::make('documents.document_number')
                    ->label('Documentos')
                    ->listWithLineBreaks()
                    ->limitList(3),

                Tables\Columns\TextColumn::make('shippingLines.name')
                    ->label('Navieras')
                    ->listWithLineBreaks()
                    ->limitList(3),

                Tables\Columns\TextColumn::make('containers.container_number')
                    ->label('Contenedores')
                    ->listWithLineBreaks()
                    ->limitList(3),
            ])
            ->defaultSort('expense_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('expense_type')
                    ->options(ExpenseType::class)
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
