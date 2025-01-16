<?php

namespace App\Imports;

use App\Models\Product;
use Filament\Facades\Filament;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\DB;

class ProductImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    protected $tenant;
    protected $category_id;
    protected $brand_id;
    private $errors = [];

    public function __construct($category_id, $brand_id)
    {
        $this->tenant = Filament::getTenant();
        $this->category_id = $category_id;
        $this->brand_id = $brand_id;
    }

    public function model(array $row)
    {
        DB::beginTransaction();
        try {
            $product = new Product([
                'store_id' => $this->tenant->id,
                'category_id' => $this->category_id,
                'brand_id' => $this->brand_id,
                'product_name' => $row['nombre'],
                'price' => (int) $row['precio'],
                'stock' => (float) $row['stock'],
                'description' => $row['descripcion'] ?? null,
                'code' => $row['codigo'] ?? null,
                'barcode' => $row['codigo_barras'] ?? null,
                'minimum_stock' => $row['stock_minimo'] ?? null,
                'maximum_stock' => $row['stock_maximo'] ?? null,
                'status' => true,
            ]);

            DB::commit();
            return $product;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->errors[] = "Error en la fila {$row['nombre']}: " . $e->getMessage();
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'numeric', 'min:0'],
            'codigo' => ['nullable', 'string', 'max:50'],
            'codigo_barras' => ['nullable', 'string', 'max:50'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'stock_maximo' => ['nullable', 'numeric', 'min:0'],
            'descripcion' => ['nullable', 'string'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nombre.required' => 'El nombre del producto es obligatorio',
            'precio.required' => 'El precio es obligatorio',
            'precio.numeric' => 'El precio debe ser un número',
            'stock.required' => 'El stock es obligatorio',
            'stock.numeric' => 'El stock debe ser un número',
        ];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
