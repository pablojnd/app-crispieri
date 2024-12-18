<?php

namespace App\Filament\Resources\ComexImportOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\ContainerType;
use Filament\Resources\RelationManagers\RelationManager;

class ContainersRelationManager extends RelationManager
{
    protected static string $relationship = 'containers';

    protected static ?string $title = 'Contenedores';

    protected static ?string $modelLabel = 'Contenedor';

    protected static ?string $pluralModelLabel = 'Contenedores';

    protected static ?string $recordTitleAttribute = 'container_number';


    protected static ?string $tenantOwnershipRelationshipName = 'store';
    // Configuración clave para la relación
    protected static ?string $inverseRelationship = 'importOrder';
    protected static ?string $foreignKeyName = 'import_order_id';
    protected static ?string $inverseRelationshipForeignKeyName = 'import_order_id';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('container_number')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->label('Número de Contenedor'),

            Forms\Components\Select::make('type')
                ->options(ContainerType::class)
                ->required()
                ->label('Tipo'),

            Forms\Components\TextInput::make('weight')
                ->numeric()
                ->required()
                ->label('Peso (KG)'),

            Forms\Components\TextInput::make('seal_number')
                ->maxLength(255)
                ->label('Número de Sello'),

            Forms\Components\TextInput::make('cost')
                ->numeric()
                ->required()
                ->label('Costo'),

            Forms\Components\Textarea::make('notes')
                ->maxLength(500)
                ->columnSpanFull()
                ->label('Notas'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('container_number')
            ->columns([
                Tables\Columns\TextColumn::make('container_number')
                    ->searchable()
                    ->sortable()
                    ->label('Número'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->label('Tipo'),

                Tables\Columns\TextColumn::make('weight')
                    ->numeric(
                        decimalPlaces: 2,
                        thousandsSeparator: '.',
                        decimalSeparator: ','
                    )
                    ->sortable()
                    ->label('Peso'),

                Tables\Columns\TextColumn::make('cost')
                    ->numeric(
                        decimalPlaces: 2,
                        thousandsSeparator: '.',
                        decimalSeparator: ','
                    )
                    ->summarize(Tables\Columns\Summarizers\Sum::make())
                    ->sortable()
                    ->label('Costo'),

                Tables\Columns\TextColumn::make('documents_count')
                    ->counts('documents')
                    ->label('Documentos'),
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
