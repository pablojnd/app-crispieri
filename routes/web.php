<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Añadir la ruta para descargar la plantilla
Route::get('/product-template', [App\Http\Controllers\ProductTemplateController::class, 'downloadTemplate'])
    ->name('product.template.download')
    ->middleware(['auth']);

// Ruta para descargar archivos exportados
Route::get('/download/product/{filename}', function ($filename) {
    // Verificar que el archivo exista
    if (!Storage::disk('public')->exists($filename)) {
        abort(404, 'Archivo no encontrado');
    }

    // Obtener el path completo del archivo
    $path = Storage::disk('public')->path($filename);

    // Extraer solo el nombre del archivo (para mostrarlo en la descarga)
    $displayName = basename($filename);

    // Determinar el tipo MIME
    $mime = Storage::disk('public')->mimeType($filename);

    // Configurar cabeceras para descarga
    $headers = [
        'Content-Type' => $mime,
        'Content-Disposition' => 'attachment; filename="' . $displayName . '"',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0'
    ];

    // Devolver el archivo como descarga con cabeceras personalizadas
    return response()->file($path, $headers);
})->name('product.download');

Route::get('/', function () {
    return redirect('/admin');
});
