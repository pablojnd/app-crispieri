<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreResource\Pages;
use App\Filament\Resources\StoreResource\RelationManagers;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $tenantOwnershipRelationshipName = 'users';

    protected static ?string $modelLabel = 'Tienda';
    protected static ?string $pluralModelLabel = 'Tiendas';
    protected static ?string $navigationGroup = 'Configuración';

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información Básica')
                ->description('Datos principales de la tienda')
                ->schema([
                    Forms\Components\FileUpload::make('logo')
                        ->label('Logo')
                        ->image()
                        ->disk('public')
                        ->directory('store-logos')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->label('Correo Electrónico')
                        ->email()
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->label('Teléfono')
                        ->tel(),
                    Forms\Components\TextInput::make('website')
                        ->label('Sitio Web')
                        ->url(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Direcciones')
                ->description('Ubicaciones de la tienda')
                ->schema([
                    Forms\Components\Repeater::make('addresses')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->label('Tipo')
                                ->options([
                                    'main' => 'Principal',
                                    'branch' => 'Sucursal',
                                    'warehouse' => 'Almacén',
                                ])
                                ->required(),
                            Forms\Components\Toggle::make('is_default')
                                ->label('Principal')
                                ->default(false),
                            Forms\Components\TextInput::make('street_address')
                                ->label('Dirección')
                                ->required(),
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
                        ])
                        ->columns(2)
                        ->defaultItems(1)
                        ->collapsible()
                        ->itemLabel(
                            fn(array $state): ?string =>
                            $state['street_address'] ?? 'Nueva dirección'
                        ),
                ]),

            Forms\Components\Section::make('Configuración')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Activa')
                        ->default(true)
                        ->required(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email copiado')
                    ->copyMessageDuration(1500),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Usuarios')
                    ->counts('users'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activa'),
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
            RelationManagers\UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStores::route('/'),
            'create' => Pages\CreateStore::route('/create'),
            'edit' => Pages\EditStore::route('/{record}/edit'),
        ];
    }
}
