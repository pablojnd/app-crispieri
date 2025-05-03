<?php

namespace App\Filament\Resources\InventoryOrderResource\Pages;

use App\Filament\Resources\InventoryOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInventoryOrder extends ViewRecord
{
    protected static string $resource = InventoryOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('edit')
                ->label('Editar completo')
                ->icon('heroicon-o-pencil-square')
                ->url(fn () => route('inventory-orders.edit', ['tenant' => $this->record->store_id, 'orderId' => $this->record->id])),
        ];
    }
}
