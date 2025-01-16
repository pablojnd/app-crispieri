<?php

namespace App\Filament\Clusters\ProductManagement\Resources\ProductResource\Pages;

use App\Imports\ProductImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use App\Filament\Clusters\ProductManagement\Resources\ProductResource;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Importar Productos')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    Select::make('category_id')
                        ->label('Categoría')
                        ->relationship('category', 'name')
                        ->required(),
                    Select::make('brand_id')
                        ->label('Marca')
                        ->relationship('brand', 'name')
                        ->required(),
                    FileUpload::make('file')
                        ->label('Archivo Excel')
                        ->preserveFilenames()
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv'
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $importer = new ProductImport(
                        $data['category_id'],
                        $data['brand_id']
                    );

                    // Usar storage_path en lugar de public_path
                    $file = storage_path('app/public/' . $data['file']);

                    try {
                        Excel::import($importer, $file);

                        if (count($importer->getErrors()) > 0) {
                            Notification::make()
                                ->title('Error en la importación')
                                ->body(implode("\n", $importer->getErrors()))
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Importación exitosa')
                                ->body('Los productos se han importado correctamente')
                                ->success()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error en la importación')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            CreateAction::make(),
        ];
    }
}
