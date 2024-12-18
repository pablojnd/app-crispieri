<?php

namespace App\Filament\Clusters\ProductManagement\Resources\BrandResource\Pages;

use App\Filament\Clusters\ProductManagement\Resources\BrandResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBrand extends CreateRecord
{
    protected static string $resource = BrandResource::class;
}
