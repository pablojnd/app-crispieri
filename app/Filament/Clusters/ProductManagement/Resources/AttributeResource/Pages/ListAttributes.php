<?php

namespace App\Filament\Clusters\ProductManagement\Resources\AttributeResource\Pages;

use App\Filament\Clusters\ProductManagement\Resources\AttributeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttributes extends ListRecords
{
    protected static string $resource = AttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
