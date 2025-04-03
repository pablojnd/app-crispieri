<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\AttributeValue;
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
    protected $measurement_unit_id;
    protected $supplier_id;
    private $errors = [];
    private $updated = 0;
    private $created = 0;
    private $infoMessages = [];

    public function __construct($measurement_unit_id = null, $supplier_id = null, $category_id = null, $brand_id = null)
    {
        $this->tenant = Filament::getTenant();
        $this->category_id = $category_id;
        $this->brand_id = $brand_id;
        $this->measurement_unit_id = $measurement_unit_id;
        $this->supplier_id = $supplier_id;
    }

    public function model(array $row)
    {
        DB::beginTransaction();
        try {
            // Buscar o crear la categoría si no se proporcionó una
            $categoryId = $this->category_id;
            if (!$categoryId && isset($row['categoria'])) {
                $categoryId = $this->findOrCreateCategory($row['categoria']);
            }

            // Buscar o crear la marca si no se proporcionó una
            $brandId = $this->brand_id;
            if (!$brandId && isset($row['marca'])) {
                $brandId = $this->findOrCreateBrand($row['marca']);
            }

            // Verificar si el producto existe por su código en la tienda actual
            $product = Product::where('store_id', $this->tenant->id)
                             ->where('code', $row['codigo'])
                             ->first();

            // Crear array con datos del producto
            $productData = [
                'store_id' => $this->tenant->id,
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'measurement_unit_id' => $this->measurement_unit_id,
                'supplier_id' => $this->supplier_id,
                'product_name' => $row['nombre'],
                'price' => (int) $row['precio'],
                'stock' => (float) $row['stock'],
                'code' => $row['codigo'],
                'barcode' => $row['codigo_barras'] ?? null,
                'description' => $row['descripcion'] ?? null,
                'minimum_stock' => $row['stock_minimo'] ?? null,
                'maximum_stock' => $row['stock_maximo'] ?? null,
                'packing_type' => $row['packing_type'] ?? null,
                'packing_quantity' => isset($row['packing_quantity']) ? (float) $row['packing_quantity'] : null,
                'status' => true,
            ];

            // Agregar campos opcionales si están presentes en el CSV
            if (isset($row['peso'])) $productData['weight'] = (float) $row['peso'];
            if (isset($row['largo'])) $productData['length'] = (float) $row['largo'];
            if (isset($row['ancho'])) $productData['width'] = (float) $row['ancho'];
            if (isset($row['alto'])) $productData['height'] = (float) $row['alto'];
            if (isset($row['ean'])) $productData['ean_code'] = $row['ean'];
            if (isset($row['codigo_proveedor'])) $productData['supplier_code'] = $row['codigo_proveedor'];
            if (isset($row['notas'])) $productData['additional_notes'] = $row['notas'];
            if (isset($row['precio_oferta'])) $productData['offer_price'] = (float) $row['precio_oferta'];
            if (isset($row['es_gravable'])) {
                $esGravable = strtoupper($row['es_gravable']);
                $productData['is_taxable'] = ($esGravable === 'SI' || $esGravable === 'S' || $esGravable === '1' || $esGravable === 'TRUE');
            }
            if (isset($row['tasa_impuesto'])) $productData['tax_rate'] = (float) $row['tasa_impuesto'];

            if ($product) {
                // Actualizar producto existente
                $product->update($productData);
                $this->updated++;
            } else {
                // Crear nuevo producto
                $product = Product::create($productData);
                $this->created++;
            }

            // Procesar atributos personalizados (columnas que empiezan con 'atributo_')
            $this->processAttributes($product, $row);

            DB::commit();
            return $product;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->errors[] = "Error en la fila con producto '{$row['nombre']}', código '{$row['codigo']}': " . $e->getMessage();
            return null;
        }
    }

    /**
     * Procesa los atributos personalizados del producto
     */
    protected function processAttributes($product, array $row)
    {
        foreach ($row as $key => $value) {
            // Solo procesar columnas que empiezan con 'atributo_' y tienen un valor
            if (Str::startsWith($key, 'atributo_') && !empty($value)) {
                // Extraer el nombre del atributo (ej: 'atributo_color' => 'color')
                $attributeName = Str::after($key, 'atributo_');

                // Buscar o crear el atributo
                $attribute = Attribute::firstOrCreate(
                    [
                        'store_id' => $this->tenant->id,
                        'name' => ucfirst($attributeName)
                    ],
                    [
                        'is_active' => true,
                        'is_required' => false
                    ]
                );

                // Buscar o crear el valor de atributo
                $attributeValue = AttributeValue::firstOrCreate(
                    [
                        'attribute_id' => $attribute->id,
                        'value' => $value
                    ]
                );

                // Crear relación entre producto y atributo/valor
                $product->product_attribute_values()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'attribute_id' => $attribute->id
                    ],
                    [
                        'attribute_value_id' => $attributeValue->id
                    ]
                );
            }
        }
    }

    /**
     * Busca una categoría por nombre pero no la actualiza si ya existe
     */
    protected function findOrCreateCategory($categoryName)
    {
        $category = Category::where('store_id', $this->tenant->id)
                          ->where('name', $categoryName)
                          ->first();

        if (!$category) {
            $category = Category::create([
                'store_id' => $this->tenant->id,
                'name' => $categoryName,
                'is_active' => true
            ]);

            // Registrar la creación de una nueva categoría
            $this->logInfo("Se ha creado la categoría: {$categoryName}");
        }

        return $category->id;
    }

    /**
     * Busca una marca por nombre pero no la actualiza si ya existe
     */
    protected function findOrCreateBrand($brandName)
    {
        $brand = Brand::where('store_id', $this->tenant->id)
                     ->where('name', $brandName)
                     ->first();

        if (!$brand) {
            $brand = Brand::create([
                'store_id' => $this->tenant->id,
                'name' => $brandName,
                'is_active' => true
            ]);

            // Registrar la creación de una nueva marca
            $this->logInfo("Se ha creado la marca: {$brandName}");
        }

        return $brand->id;
    }

    /**
     * Registra información para estadísticas
     */
    private function logInfo($message)
    {
        $this->infoMessages[] = $message;
    }

    public function rules(): array
    {
        $rules = [
            'nombre' => ['required', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'numeric', 'min:0'],
            'codigo' => ['required', 'string', 'max:50'],
            'codigo_barras' => ['nullable', 'string', 'max:50'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'stock_maximo' => ['nullable', 'numeric', 'min:0'],
            'descripcion' => ['nullable', 'string'],
            'packing_type' => ['nullable', 'string', 'max:50'],
            'packing_quantity' => ['nullable', 'numeric', 'min:0'],
            'peso' => ['nullable', 'numeric', 'min:0'],
            'largo' => ['nullable', 'numeric', 'min:0'],
            'ancho' => ['nullable', 'numeric', 'min:0'],
            'alto' => ['nullable', 'numeric', 'min:0'],
            'ean' => ['nullable', 'string', 'max:50'],
            'codigo_proveedor' => ['nullable', 'string', 'max:50'],
            'precio_oferta' => ['nullable', 'numeric', 'min:0'],
            'es_gravable' => ['nullable', 'string'],
            'tasa_impuesto' => ['nullable', 'numeric', 'min:0'],
        ];

        // Si no se proporcionó category_id, se requiere el campo 'categoria' en el CSV
        if ($this->category_id === null) {
            $rules['categoria'] = ['required', 'string', 'max:255'];
        }

        // Si no se proporcionó brand_id, se requiere el campo 'marca' en el CSV
        if ($this->brand_id === null) {
            $rules['marca'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }

    public function customValidationMessages(): array
    {
        return [
            'nombre.required' => 'El nombre del producto es obligatorio',
            'precio.required' => 'El precio es obligatorio',
            'precio.numeric' => 'El precio debe ser un número',
            'stock.required' => 'El stock es obligatorio',
            'stock.numeric' => 'El stock debe ser un número',
            'codigo.required' => 'El código del producto es obligatorio para identificarlo',
            'categoria.required' => 'La categoría del producto es obligatoria',
            'marca.required' => 'La marca del producto es obligatoria',
        ];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getStats(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'errors' => count($this->errors),
            'infoMessages' => $this->infoMessages
        ];
    }
}
