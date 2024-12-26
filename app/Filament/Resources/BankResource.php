<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Bank;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\BankResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BankResource\RelationManagers;

class BankResource extends Resource
{
    protected static ?string $model = Bank::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $modelLabel = 'Cuenta Bancaria';

    protected static ?string $pluralModelLabel = 'Cuentas Bancarias';

    protected static ?string $navigationGroup = 'Comercio Exterior';

    protected static ?int $navigationSort = 2;

    protected static ?string $tenantOwnershipRelationshipName = 'store';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Banco')
                    ->description('Detalles de la cuenta bancaria')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Select::make('bank_code_id')
                            ->relationship(
                                name: 'bankCode',
                                titleAttribute: 'code_bank_name',
                                // modifyQueryUsing: fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
                            )
                            ->label('Banco')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('bank_name')
                                    ->label('Nombre del Banco')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('code')
                                    ->label('Código del Banco')
                                    ->required()
                                    ->maxLength(10),
                            ]),
                        Forms\Components\TextInput::make('account_number')
                            ->label('Número de Cuenta')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('account_type')
                            ->label('Tipo de Cuenta')
                            ->options([
                                'checking' => 'Cuenta Corriente',
                                'savings' => 'Cuenta de Ahorro',
                                'other' => 'Otro'
                            ])
                            ->required(),
                        // Forms\Components\Select::make('currency_id')
                        //     ->label('Moneda')
                        //     ->relationship('currency', 'name')
                        //     ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bankCode.code_bank_name')
                    ->label('Banco')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('account_number')
                    ->label('N° Cuenta')
                    ->searchable(),
                Tables\Columns\TextColumn::make('latestBalance.balance_usd')
                    ->label('Saldo USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('latestBalance.balance_clp')
                    ->label('Saldo CLP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('account_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'checking' => 'success',
                        'savings' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'checking' => 'Corriente',
                        'savings' => 'Ahorro',
                        default => 'Otro',
                    }),
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
            RelationManagers\BankBalancesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBanks::route('/'),
            'create' => Pages\CreateBank::route('/create'),
            'edit' => Pages\EditBank::route('/{record}/edit'),
        ];
    }
}
