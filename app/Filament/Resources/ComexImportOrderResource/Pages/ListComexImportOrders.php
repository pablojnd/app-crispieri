<?php

namespace App\Filament\Resources\ComexImportOrderResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ComexImportOrderResource;
use App\Filament\Resources\ComexImportOrderResource\Widgets\ImportOrderStats;

class ListComexImportOrders extends ListRecords
{
    protected static string $resource = ComexImportOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ImportOrderStats::class,
        ];
    }
}
