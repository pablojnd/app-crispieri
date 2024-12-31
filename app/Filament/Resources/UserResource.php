<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Store;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\RelationManagers;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $modelLabel = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios';
    protected static ?string $navigationGroup = 'Administración';
    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información Personal')
                ->description('Datos principales del usuario')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                ])
                ->columns(2),

            Forms\Components\Section::make('Seguridad')
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->label('Contraseña')
                        ->password()
                        ->dehydrated(fn($state) => filled($state))
                        ->required(fn(string $context): bool => $context === 'create'),
                    Forms\Components\Toggle::make('email_verified_at')
                        ->label('Email Verificado')
                        ->dehydrated(false)
                        ->disabled(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Tiendas')
                ->schema([
                    Forms\Components\Select::make('stores')
                        ->label('Tiendas Asignadas')
                        ->multiple()
                        ->relationship(
                            'stores',
                            'name',
                            fn($query) => $query->orderBy('name')
                        )
                        ->preload()
                        ->searchable()
                        ->reactive(),
                    Forms\Components\Select::make('latest_store_id')
                        ->label('Tienda por Defecto')
                        ->relationship('latestStore', 'name')
                        ->options(function (callable $get) {
                            $storeIds = $get('stores');
                            if (!$storeIds) return [];
                            return Store::whereIn('id', $storeIds)->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->exists('stores', 'id')
                        ->visible(fn(callable $get) => count($get('stores')) > 0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email copiado'),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verificado')
                    ->boolean(),
                Tables\Columns\TextColumn::make('stores.name')
                    ->label('Tiendas')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email Verificado')
                    ->nullable(),
                Tables\Filters\SelectFilter::make('stores')
                    ->relationship('stores', 'name')
                    ->label('Tienda')
                    ->multiple()
                    ->preload(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
