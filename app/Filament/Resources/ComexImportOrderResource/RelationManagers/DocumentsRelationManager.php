<?php

namespace App\Filament\Resources\ComexImportOrderResource\RelationManagers;

use Filament\Forms;
use App\Models\Bank;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\DocumentType;
use App\Enums\PaymentStatus;
use App\Enums\DocumentClauseType;
use Filament\Forms\Components\Tabs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documentos';

    protected static ?string $modelLabel = 'Documento';

    protected static ?string $pluralModelLabel = 'Documentos';

    protected static ?string $tenantOwnershipRelationshipName = 'store';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Documento')
                    ->tabs([
                        Tabs\Tab::make('Información General')
                            ->schema([
                                Forms\Components\TextInput::make('document_number')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Número de Documento'),

                                Forms\Components\Select::make('document_type')
                                    ->options(DocumentType::class)
                                    ->required()
                                    ->label('Tipo'),

                                Forms\Components\Select::make('document_clause')
                                    ->options(DocumentClauseType::class)
                                    ->required()
                                    ->label('Cláusula'),

                                Forms\Components\DatePicker::make('document_date')
                                    ->required()
                                    ->default(now())
                                    ->label('Fecha'),

                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('fob_total')
                                            ->numeric()
                                            ->required()
                                            ->label('FOB'),

                                        Forms\Components\TextInput::make('freight_total')
                                            ->numeric()
                                            ->required()
                                            ->label('Flete'),

                                        Forms\Components\TextInput::make('insurance_total')
                                            ->numeric()
                                            ->required()
                                            ->label('Seguro'),

                                        Forms\Components\TextInput::make('cif_total')
                                            ->numeric()
                                            ->readOnly()
                                            ->visibleOn('view', 'edit')
                                            ->label('CIF'),

                                        Forms\Components\TextInput::make('factor')
                                            ->numeric()
                                            ->readOnly()
                                            ->visibleOn('view', 'edit')
                                            ->label('Factor'),
                                    ]),

                                Forms\Components\Textarea::make('notes')
                                    ->maxLength(500)
                                    ->columnSpanFull()
                                    ->label('Notas'),

                                // Actualizar el select de estado de pago
                                Forms\Components\Select::make('payment_status')
                                    ->options(PaymentStatus::class) // Filament manejará automáticamente la conversión
                                    ->default(PaymentStatus::PENDING)
                                    ->disabled()
                                    ->hiddenOn('create')
                                    ->label('Estado de Pago'),
                            ])->columns(3),

                        Tabs\Tab::make('Pagos')
                            ->schema([
                                Forms\Components\Repeater::make('payments')
                                    ->label('')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Select::make('bank_id')
                                                    ->label('Banco')
                                                    ->options(function () {
                                                        try {
                                                            $banks = Bank::getAvailableBanksForSelect();
                                                            return empty($banks) ? ['' => 'No hay bancos disponibles'] : $banks;
                                                        } catch (\Exception $e) {
                                                            return ['' => 'Error al cargar bancos'];
                                                        }
                                                    })
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->helperText('Seleccione un banco'),

                                                Forms\Components\TextInput::make('amount')
                                                    ->label('Monto')
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(0),

                                                Forms\Components\TextInput::make('exchange_rate')
                                                    ->label('Tipo de Cambio')
                                                    ->numeric()
                                                    ->default(1.0000)
                                                    ->required(),

                                                Forms\Components\Select::make('payment_status')
                                                    ->options(PaymentStatus::getSelectOptions())
                                                    ->default(PaymentStatus::COMPLETED)
                                                    ->required()
                                                    ->label('Estado'),

                                                Forms\Components\DatePicker::make('payment_date')
                                                    ->label('Fecha de Pago')
                                                    ->required(),

                                                Forms\Components\TextInput::make('reference_number')
                                                    ->label('Referencia')
                                                    ->maxLength(255),
                                            ]),

                                        Forms\Components\Textarea::make('notes')
                                            ->label('Notas')
                                            ->columnSpanFull()
                                            ->maxLength(500),
                                    ])
                                    ->defaultItems(0)
                                    // ->reorderableWithButtons()
                                    ->collapsible()
                                    ->collapseAllAction(
                                        fn(Forms\Components\Actions\Action $action) =>
                                        $action->label('Colapsar Todo')
                                    )
                                    ->expandAllAction(
                                        fn(Forms\Components\Actions\Action $action) =>
                                        $action->label('Expandir Todo')
                                    )
                                    ->addAction(
                                        fn(Forms\Components\Actions\Action $action) =>
                                        $action->label('Agregar Pago')
                                    )
                                    ->deleteAction(
                                        fn(Forms\Components\Actions\Action $action) =>
                                        $action->label('Eliminar Pago')
                                    )
                                    ->columnSpanFull()
                            ]),
                    ])
                    ->columnSpanFull()
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('document_number')
            ->modifyQueryUsing(
                fn(Builder $query) => $query
                    ->withSum('items', 'total_price')
                    ->withSum('payments', 'amount')
            )
            ->columns([
                Tables\Columns\TextColumn::make('document_number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->label('Número'),

                Tables\Columns\TextColumn::make('document_type')
                    ->sortable()
                    ->badge()
                    ->label('Tipo'),

                Tables\Columns\TextColumn::make('document_date')
                    ->date()
                    ->sortable()
                    ->label('Fecha'),

                Tables\Columns\TextColumn::make('fob_total')
                    ->sortable()
                    ->numeric(
                        decimalPlaces: 4,
                        thousandsSeparator: '.',
                        decimalSeparator: ','
                    )
                    ->summarize(Tables\Columns\Summarizers\Sum::make())
                    ->label('FOB'),

                Tables\Columns\TextColumn::make('cif_total')
                    ->sortable()
                    ->numeric(
                        decimalPlaces: 4,
                        thousandsSeparator: '.',
                        decimalSeparator: ','
                    )
                    ->summarize(Tables\Columns\Summarizers\Sum::make())
                    ->tooltip(
                        fn($record) =>
                        "FOB: $" . number_format($record->fob_total, 4) . "\n" .
                            "Flete: $" . number_format($record->freight_total, 4) . "\n" .
                            "Seguro: $" . number_format($record->insurance_total, 4)
                    )
                    ->label('CIF'),

                Tables\Columns\TextColumn::make('factor')
                    ->numeric(
                        decimalPlaces: 9,
                        thousandsSeparator: '.',
                        decimalSeparator: ','
                    )
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(
                        fn($record) =>
                        "CIF ($" . number_format($record->cif_total, 4) . ") / " .
                            "Total Items ($" . number_format($record->items_sum_total_price, 4) . ") = " .
                            number_format($record->factor, 9)
                    )
                    ->label('Factor'),

                Tables\Columns\TextColumn::make('items_sum_total_price')
                    ->numeric(
                        decimalPlaces: 4,
                        thousandsSeparator: '.',
                        decimalSeparator: ','
                    )
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make())
                    ->label('Total Items'),

                Tables\Columns\TextColumn::make('total_paid')
                    ->money('USD')
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                    )
                    ->label('Pagado'),

                Tables\Columns\TextColumn::make('pending_amount')
                    ->money('USD')
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                    )
                    ->color(fn($record) => $record->pending_amount > 0 ? 'danger' : 'success')
                    ->label('Pendiente'),

                Tables\Columns\TextColumn::make('payments_count')
                    ->counts('payments')
                    ->sortable()
                    ->label('Pagos'),

                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->sortable()
                    ->label('Items')
                    ->tooltip(fn($record) => $record->items_tooltip)
                    ->badge()
                    ->alignCenter(),
            ])
            ->defaultSort('document_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('document_type')
                    ->options(DocumentType::class)
                    ->label('Tipo'),
                Tables\Filters\Filter::make('pending')
                    ->query(fn(Builder $query) => $query->where('pending_amount', '>', 0))
                    ->label('Pendientes'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Agregar Documento'),
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
