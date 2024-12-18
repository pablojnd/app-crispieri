<?php

namespace App\Filament\Resources\BankResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BankBalancesRelationManager extends RelationManager
{
    protected static string $relationship = 'bankBalances';

    protected static ?string $title = 'Historial de Saldos';

    protected static ?string $modelLabel = 'Saldo Bancario';

    protected static ?string $pluralModelLabel = 'Saldos Bancarios';

    protected static ?string $tenantOwnershipRelationshipName = 'store';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Saldo')
                    ->description('Registre el saldo y tipo de cambio')
                    ->columns(2)
                    ->schema([
                        Forms\Components\DatePicker::make('balance_date')
                            ->label('Fecha')
                            ->required()
                            ->native(false)
                            ->default(now())
                            ->maxDate(now()),

                        Forms\Components\TextInput::make('exchange_rate')
                            ->label('Tipo de Cambio')
                            ->required()
                            ->numeric()
                            ->default(1.0000)
                            ->step(0.0001),

                        Forms\Components\TextInput::make('balance_usd')
                            ->label('Saldo USD')
                            ->required()
                            ->numeric()
                            ->prefix('USD')
                            ->default(0.00),

                        Forms\Components\TextInput::make('balance_clp')
                            ->label('Saldo CLP')
                            ->required()
                            ->numeric()
                            ->prefix('USD')
                            ->default(0.00),


                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->columnSpanFull()
                            ->rows(2),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('balance_date')
            ->columns([
                Tables\Columns\TextColumn::make('balance_date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance_usd')
                    ->label('Saldo')
                    ->money('USD')
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\TextColumn::make('balance_clp')
                    ->label('Saldo')
                    ->money('USD')
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\TextColumn::make('exchange_rate')
                    ->label('T/C')
                    ->numeric(4)
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Notas')
                    ->limit(30)
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
