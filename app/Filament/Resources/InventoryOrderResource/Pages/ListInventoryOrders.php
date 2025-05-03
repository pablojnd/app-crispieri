<?php

namespace App\Filament\Resources\InventoryOrderResource\Pages;

use App\Filament\Resources\InventoryOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryOrders extends ListRecords
{
    protected static string $resource = InventoryOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('newOrder')
                ->label('Nuevo Pedido')
                ->icon('heroicon-o-plus')
                ->url(fn () => route('inventory-orders.create', ['tenant' => \Filament\Facades\Filament::getTenant()->id])),
        ];
    }
}
