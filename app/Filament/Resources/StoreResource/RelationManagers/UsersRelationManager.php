<?php

namespace App\Filament\Resources\StoreResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';
    protected static ?string $title = 'Usuarios';
    protected static ?string $modelLabel = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Usuario')
                ->relationship(
                    name: 'users',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn(Builder $query) => $query->whereDoesntHave('stores')
                )
                ->searchable(['name', 'email'])
                ->preload()
                ->required(),

            Forms\Components\Select::make('role')
                ->label('Rol')
                ->options([
                    'admin' => 'Administrador',
                    'manager' => 'Gerente',
                    'user' => 'Usuario',
                ])
                ->default('user')
                ->required()
                ->native(false),

            Forms\Components\Toggle::make('is_active')
                ->label('Activo')
                ->default(true)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name', 'email'])
                    ->form(fn(Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Usuario')
                            ->required(),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make(),
            ]);
    }
}
