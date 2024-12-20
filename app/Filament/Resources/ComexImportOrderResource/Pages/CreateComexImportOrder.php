<?php

namespace App\Filament\Resources\ComexImportOrderResource\Pages;

use Filament\Actions;
use App\Models\ComexImportOrder;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ComexImportOrderResource;

class CreateComexImportOrder extends CreateRecord
{
    protected static string $resource = ComexImportOrderResource::class;

    protected function afterFill(): void
    {
        $this->data['reference_number'] = ComexImportOrder::generateReferenceNumber();
    }

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     $data['reference_number'] = ComexImportOrder::generateReferenceNumber();
    //     return $data;
    // }
}
