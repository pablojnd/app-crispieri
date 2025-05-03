<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryOrderResource\Pages;
use App\Models\InventoryOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;

class InventoryOrderResource extends Resource
{
    protected static ?string $model = InventoryOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $modelLabel = 'Pedido de Inventario';
    protected static ?string $pluralModelLabel = 'Pedidos de Inventario';
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?string $tenantOwnershipRelationshipName = 'store';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->label('Número de Pedido')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('reference')
                            ->label('Referencia')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('client_name')
                            ->label('Cliente')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('client_email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('client_phone')
                            ->label('Teléfono')
                            ->maxLength(20),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'draft' => 'Borrador',
                                'pending' => 'Pendiente',
                                'confirmed' => 'Confirmado',
                                'modified' => 'Modificado',
                                'cancelled' => 'Cancelado',
                                'completed' => 'Completado',
                            ])
                            ->required(),
                    ]),

                Forms\Components\Textarea::make('notes')
                    ->label('Notas')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client_name')
                    ->label('Cliente')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'draft' => 'Borrador',
                        'pending' => 'Pendiente',
                        'confirmed' => 'Confirmado',
                        'modified' => 'Modificado',
                        'cancelled' => 'Cancelado',
                        'completed' => 'Completado',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'info' => 'modified',
                        'danger' => 'cancelled',
                        'success' => 'completed',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_by')
                    ->label('Creado por'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'draft' => 'Borrador',
                        'pending' => 'Pendiente',
                        'confirmed' => 'Confirmado',
                        'modified' => 'Modificado',
                        'cancelled' => 'Cancelado',
                        'completed' => 'Completado',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('customEdit')
                    ->label('Editar completo')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (InventoryOrder $record): string => route('inventory-orders.edit', ['tenant' => $record->store_id, 'orderId' => $record->id])),
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
            'index' => Pages\ListInventoryOrders::route('/'),
            'create' => Pages\CreateInventoryOrder::route('/create'),
            'edit' => Pages\EditInventoryOrder::route('/{record}/edit'),
            'view' => Pages\ViewInventoryOrder::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }
}
