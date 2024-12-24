<?php

namespace App\Filament\Resources\ComexImportOrderResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Imports\ComexOrderImportOrderImport;
use App\Filament\Resources\ComexImportOrderResource;
use App\Filament\Resources\ComexImportOrderResource\Widgets\ImportOrderStats;
use Filament\Notifications\Notification;

class ListComexImportOrders extends ListRecords
{
    protected static string $resource = ComexImportOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Action::make('Importar ordenes')
                ->label('importOrders')
                // ->icon('heroicon-o-cloud-upload')
                ->form([
                    FileUpload::make('file')
                        ->label('Archivo')
                    // ->acceptedFileTypes(['.xlsx', '.xls'])
                    // ->rules('required', 'mimes:xlsx,xls'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['file']);
                    Excel::import(new ComexOrderImportOrderImport, $file);
                    // dd($file);

                    Notification::make()
                        ->title('Ordenes importadas')
                        // ->message('Las ordenes se han importado correctamente')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ImportOrderStats::class,
        ];
    }
}
