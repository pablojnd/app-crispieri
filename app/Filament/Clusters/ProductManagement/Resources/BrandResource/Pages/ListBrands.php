<?php

namespace App\Filament\Clusters\ProductManagement\Resources\BrandResource\Pages;

use App\Filament\Clusters\ProductManagement\Resources\BrandResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBrands extends ListRecords
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
