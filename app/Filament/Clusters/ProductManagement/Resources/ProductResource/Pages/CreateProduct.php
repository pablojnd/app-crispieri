<?php

namespace App\Filament\Clusters\ProductManagement\Resources\ProductResource\Pages;

use App\Filament\Clusters\ProductManagement\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
