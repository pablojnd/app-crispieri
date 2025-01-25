<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ComexItem;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Filament\Facades\Filament;

class ComexItemImporter implements ToCollection, WithHeadingRow, WithValidation
{
    protected $importOrder;
    protected $store;

    public function __construct($importOrder)
    {
        $this->importOrder = $importOrder;
        $this->store = Filament::getTenant();
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Buscar producto por código si existe
            $product = null;
            if (!empty($row['code'])) {
                $product = Product::where('code', $row['code'])
                    ->where('store_id', $this->store->id)
                    ->first();
            }

            // Si no existe el producto, crearlo
            if (!$product) {
                // Generar código si no se proporcionó
                $code = $row['code'] ?? $this->generateProductCode($row['product_name']);

                $product = Product::create([
                    'product_name' => $row['product_name'],
                    'code' => $code,
                    'store_id' => $this->store->id,
                    'status' => true,
                    'measurement_unit_id' => $row['measurement_unit_id'] ?? 1, // Unidad por defecto
                    'slug' => Str::slug($row['product_name']),
                    // Campos opcionales si vienen en el CSV
                    'description' => $row['description'] ?? null,
                    'hs_code' => $row['hs_code'] ?? null,
                    'supplier_code' => $row['supplier_code'] ?? null,
                    'category_id' => $row['category_id'] ?? 1, // Categoría por defecto
                ]);
            }

            // Crear el item de importación
            ComexItem::create([
                'store_id' => $this->store->id,
                'import_order_id' => $this->importOrder->id,
                'product_id' => $product->id,
                'package_quality' => $row['package_quality'],
                'quantity' => $row['quantity'],
                'total_price' => $row['total_price'],
            ]);
        }
    }

    protected function generateProductCode(string $productName): string
    {
        // Generar código basado en el nombre del producto
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $productName), 0, 3));
        $timestamp = now()->format('ymdHis');
        return "{$prefix}{$timestamp}";
    }

    public function rules(): array
    {
        return [
            '*.product_name' => ['required', 'string', 'max:255'],
            '*.code' => ['nullable', 'string', 'max:50'],
            '*.package_quality' => ['required', 'numeric', 'min:0'],
            '*.quantity' => ['required', 'numeric', 'min:0'],
            '*.total_price' => ['required', 'numeric', 'min:0'],
            // Campos opcionales
            '*.description' => ['nullable', 'string'],
            '*.hs_code' => ['nullable', 'string'],
            '*.supplier_code' => ['nullable', 'string'],
            '*.category_id' => ['nullable', 'exists:categories,id'],
            '*.measurement_unit_id' => ['nullable', 'exists:measurement_units,id'],
        ];
    }

    public function customValidationMessages()
    {
        return [
            'product_name.required' => 'El nombre del producto es obligatorio',
            'package_quality.required' => 'La cantidad de bulto es obligatoria',
            'quantity.required' => 'La cantidad es obligatoria',
            'total_price.required' => 'El precio total es obligatorio',
        ];
    }
}
