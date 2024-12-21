<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProviderResource\Pages;
use App\Filament\Resources\ProviderResource\RelationManagers;
use App\Models\Provider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $modelLabel = 'Proveedor';
    protected static ?string $pluralModelLabel = 'Proveedores';
    protected static ?string $navigationGroup = 'Gestión de Inventario';
    // protected static ?string $tenantOwnershipRelationshipName = 'store';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información Básica')
                ->description('Datos principales del proveedor')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre/Razón Social')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('contact_name')
                        ->label('Nombre de Contacto')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('rut')
                        ->label('RUT')
                        ->unique(ignoreRecord: true)
                        ->maxLength(12),
                    Forms\Components\TextInput::make('email')
                        ->label('Correo Electrónico')
                        ->email()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->label('Teléfono')
                        ->tel()
                        ->maxLength(255),

                    Forms\Components\Textarea::make('observations')
                        ->label('Observaciones')
                        ->columnSpanFull()
                        ->rows(3),
                ])
                ->columns(4)
                ->columnSpan(2),

            Forms\Components\Section::make('Contacto')
                ->description('Información de contacto del proveedor')
                ->schema([
                    Forms\Components\Toggle::make('active')
                        ->label('Activo')
                        ->inline(false)
                        ->default(true)
                        ->required(),
                    Forms\Components\Select::make('type')
                        ->label('Tipo')
                        ->options([
                            'manufacturer' => 'Fabricante',
                            'distributor' => 'Distribuidor',
                            'wholesaler' => 'Mayorista',
                            'retailer' => 'Minorista',
                        ])
                        ->required()
                        ->native(false),

                    Forms\Components\TextInput::make('website')
                        ->label('Sitio Web')
                        ->url()
                        ->columnSpanFull()
                        ->maxLength(255),
                ])
                ->columns(2)
                ->columnSpan(1),

            Forms\Components\Section::make('Direcciones')
                ->description('Gestionar las direcciones del proveedor')
                ->schema([
                    Forms\Components\Repeater::make('addresses')
                        ->relationship()
                        ->schema([
                            Forms\Components\Toggle::make('is_default')
                                ->label('Dirección Principal')
                                ->default(false),
                            Forms\Components\Select::make('type')
                                ->label('Tipo de Dirección')
                                ->options([
                                    'main' => 'Principal',
                                    'billing' => 'Facturación',
                                    'shipping' => 'Envío',
                                ])
                                ->required()
                                ->native(false),
                            Forms\Components\TextInput::make('street_address')
                                ->label('Calle')
                                ->required(),
                            Forms\Components\TextInput::make('street_number')
                                ->label('Número'),
                            Forms\Components\TextInput::make('apartment')
                                ->label('Departamento/Oficina'),
                            Forms\Components\TextInput::make('city')
                                ->label('Ciudad')
                                ->required(),
                            Forms\Components\TextInput::make('state')
                                ->label('Región/Estado'),
                            Forms\Components\TextInput::make('country')
                                ->label('País')
                                ->required(),
                            Forms\Components\TextInput::make('postal_code')
                                ->label('Código Postal'),
                            Forms\Components\Textarea::make('additional_info')
                                ->label('Información Adicional')
                                ->rows(2)
                                ->columnSpan(2),
                        ])
                        ->label('Direccion')
                        ->columns(4)
                        ->defaultItems(0)
                        ->reorderable(false)
                        ->collapsible()
                        ->itemLabel(
                            fn(array $state): ?string =>
                            $state['street_address'] ?? 'Nueva dirección'
                        )
                ])
                ->collapsible(),

        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rut')
                    ->label('RUT')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Contacto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono'),
                Tables\Columns\SelectColumn::make('type')
                    ->label('Tipo')
                    ->options([
                        'manufacturer' => 'Fabricante',
                        'distributor' => 'Distribuidor',
                        'wholesaler' => 'Mayorista',
                        'retailer' => 'Minorista',
                    ]),
                Tables\Columns\IconColumn::make('active')
                    ->label('Estado')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'manufacturer' => 'Fabricante',
                        'distributor' => 'Distribuidor',
                        'wholesaler' => 'Mayorista',
                        'retailer' => 'Minorista',
                    ]),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Activo'),
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
            'index' => Pages\ListProviders::route('/'),
            'create' => Pages\CreateProvider::route('/create'),
            'edit' => Pages\EditProvider::route('/{record}/edit'),
        ];
    }
}
