<?php

namespace App\Filament\Clusters\ProductManagement\Resources\ProductResource\Pages;

use App\Filament\Clusters\ProductManagement\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
