<?php

namespace App\Filament\Resources\ComexImportOrderResource\Pages;

use App\Filament\Resources\ComexImportOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListComexImportOrders extends ListRecords
{
    protected static string $resource = ComexImportOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
