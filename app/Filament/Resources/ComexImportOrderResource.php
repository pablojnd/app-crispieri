<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ComexImportOrder;
use Filament\Resources\Resource;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ComexImportOrderExport;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\{TransportType, ImportOrderStatus};
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ComexImportOrderResource\Pages;
use App\Filament\Resources\ComexImportOrderResource\RelationManagers;

class ComexImportOrderResource extends Resource
{
    protected static ?string $model = ComexImportOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $modelLabel = 'Orden de Importación';
    protected static ?string $pluralModelLabel = 'Órdenes de Importación';
    protected static ?string $navigationGroup = 'Comercio Exterior';
    protected static ?int $navigationSort = 1;
    protected static ?string $tenantOwnershipRelationshipName = 'store';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información Básica')
                ->description('Datos principales de la orden de importación')
                ->schema([
                    Forms\Components\Grid::make()
                        ->columns(3)
                        ->schema([
                            Forms\Components\TextInput::make('reference_number')
                                ->label('Número de Referencia')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),

                            Forms\Components\TextInput::make('external_reference')
                                ->label('Referencia Externa')
                                ->maxLength(255)
                                ->helperText('Referencia proporcionada por el proveedor'),

                            Forms\Components\Select::make('provider_id')
                                ->label('Proveedor')
                                ->relationship('provider', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Forms\Components\Select::make('origin_country_id')
                                ->label('País de Origen')
                                ->relationship('originCountry', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Forms\Components\TextInput::make('sve_registration_number')
                                ->label('Número SVE'),

                            Forms\Components\Select::make('type')
                                ->label('Tipo de Transporte')
                                ->options(TransportType::class)
                                ->required()
                        ]),
                ])
                ->columnSpan(['lg' => 2]),

            Forms\Components\Section::make('Estado y Cronograma')
                ->description('Fechas importantes de la orden')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options(ImportOrderStatus::class)
                        ->default(ImportOrderStatus::DRAFT)
                        ->required(),

                    Forms\Components\DatePicker::make('order_date')
                        ->label('Fecha de Orden')
                        ->default(now())
                        ->required(),

                    Forms\Components\DatePicker::make('estimated_departure')
                        ->label('Salida Estimada'),

                    Forms\Components\DatePicker::make('actual_departure')
                        ->label('Salida Real'),

                    Forms\Components\DatePicker::make('estimated_arrival')
                        ->label('Llegada Estimada'),

                    Forms\Components\DatePicker::make('actual_arrival')
                        ->label('Llegada Real'),
                ])
                ->columns(2)
                ->columnSpan(['lg' => 1]),
        ])
            ->columns([
                'default' => 1,
                'lg' => 3
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Referencia')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('provider.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Transporte')
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('order_date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(ImportOrderStatus::class),
                Tables\Filters\SelectFilter::make('type')
                    ->options(TransportType::class),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('exportExcelBodega')
                    ->label('Exportar Excel')
                    ->action(function (ComexImportOrder $record) {
                        return Excel::download(new ComexImportOrderExport($record), 'bodega.xlsx');
                    }),
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
            RelationManagers\DocumentsRelationManager::class,
            RelationManagers\ContainersRelationManager::class,
            RelationManagers\ItemsRelationManager::class,
            RelationManagers\ExpensesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComexImportOrders::route('/'),
            'create' => Pages\CreateComexImportOrder::route('/create'),
            'edit' => Pages\EditComexImportOrder::route('/{record}/edit'),
        ];
    }
}
