<?php

namespace App\Filament\Clusters\ProductManagement\Resources\ProductResource\Pages;

use App\Exports\ProductsExport;
use App\Imports\ProductImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Filament\Clusters\ProductManagement\Resources\ProductResource;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Exportar Productos')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function (): void {
                    try {
                        // Mostrar notificación de proceso iniciado
                        Notification::make()
                            ->title('Generando exportación')
                            ->body('Esto puede tomar un momento...')
                            ->info()
                            ->send();

                        // Generar un nombre de archivo único
                        $filename = 'productos-' . now()->format('Y-m-d-His') . '.xlsx';
                        $filepath = 'exports/' . $filename;
                        
                        // Guardar el archivo en el disco
                        Excel::store(new ProductsExport(), $filepath, 'public');
                        
                        // Generar una URL de descarga y notificar
                        $downloadUrl = route('product.download', ['filename' => $filepath]);
                        
                        Notification::make()
                            ->title('Exportación completada')
                            ->body('Haga clic aquí para descargar el archivo')
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('download')
                                    ->label('Descargar')
                                    ->url($downloadUrl)
                                    ->openUrlInNewTab()
                            ])
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        // Registrar el error para diagnóstico
                        Log::error('Error en exportación de productos: ' . $e->getMessage());
                        
                        Notification::make()
                            ->title('Error al exportar')
                            ->body('No se pudo completar la exportación. Detalles: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

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

    protected function getTableBulkActions(): array
    {
        return [
            \Filament\Tables\Actions\BulkAction::make('exportSelected')
                ->label('Exportar Seleccionados')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function ($records): void {
                    try {
                        // Obtener los IDs de los productos seleccionados
                        $productIds = $records->pluck('id')->toArray();
                        
                        if (empty($productIds)) {
                            Notification::make()
                                ->title('Sin productos seleccionados')
                                ->body('Por favor seleccione al menos un producto para exportar.')
                                ->warning()
                                ->send();
                            return;
                        }
                        
                        // Mostrar notificación de proceso iniciado
                        Notification::make()
                            ->title('Generando exportación')
                            ->body('Preparando ' . count($productIds) . ' productos seleccionados...')
                            ->info()
                            ->send();
                            
                        // Generar un nombre de archivo único
                        $filename = 'productos-seleccionados-' . count($productIds) . '-' . now()->format('Y-m-d-His') . '.xlsx';
                        $filepath = 'exports/' . $filename;
                        
                        // Guardar el archivo en el disco
                        Excel::store(new ProductsExport($productIds), $filepath, 'public');
                        
                        // Generar una URL de descarga y notificar
                        $downloadUrl = route('product.download', ['filename' => $filepath]);
                        
                        Notification::make()
                            ->title('Exportación completada')
                            ->body('Haga clic aquí para descargar ' . count($productIds) . ' productos')
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('download')
                                    ->label('Descargar')
                                    ->url($downloadUrl)
                                    ->openUrlInNewTab()
                            ])
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        // Registrar el error
                        Log::error('Error en exportación de productos seleccionados: ' . $e->getMessage());
                        
                        Notification::make()
                            ->title('Error al exportar productos seleccionados')
                            ->body('Detalles: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
        ];
    }
}
