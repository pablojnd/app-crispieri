<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Añadir la ruta para descargar la plantilla
Route::get('/product-template', [App\Http\Controllers\ProductTemplateController::class, 'downloadTemplate'])
    ->name('product.template.download')
    ->middleware(['auth']);

Route::get('/', function () {
    return redirect('/admin');
});
