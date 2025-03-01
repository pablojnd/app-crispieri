<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use App\Models\ComexItem;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Carbon\Carbon;
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

    /**
     * Preprocesa el valor para convertir comas en puntos decimales
     *
     * @param mixed $value
     * @return mixed
     */
    protected function prepareNumericValue($value)
    {
        // Si es un valor vacío, retornar null para que no falle la validación
        if (empty($value) || $value === '' || $value === null) {
            return null;
        }

        // Si es una cadena, intentar convertir a número
        if (is_string($value)) {
            // Primero intentar manejar notación científica
            if (strpos($value, 'E+') !== false || strpos($value, 'e+') !== false) {
                // Es notación científica, convertir a número directamente
                $parsed = (string) floatval($value);
                return $parsed;
            }

            // Manejar comas como separador decimal
            $value = str_replace(',', '.', $value);

            // Verificar si ahora es un número válido
            if (is_numeric($value)) {
                return $value;
            }
        }

        return $value;
    }

    /**
     * Preprocesa un valor de fecha para convertirlo al formato correcto
     *
     * @param mixed $value
     * @return string|null
     */
    protected function prepareDateValue($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            // Intentar analizar la fecha con varios formatos comunes
            $formats = [
                'd/m/Y', // 31/12/2023
                'd-m-Y', // 31-12-2023
                'Y/m/d', // 2023/12/31
                'Y-m-d', // 2023-12-31
                'd.m.Y', // 31.12.2023
            ];

            foreach ($formats as $format) {
                $date = Carbon::createFromFormat($format, $value);
                if ($date) {
                    return $date->format('Y-m-d');
                }
            }
        } catch (\Exception $e) {
            // Si falla, retornar el valor original
        }

        return $value;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Preprocesar valores numéricos (convertir comas en puntos)
            $numericFields = [
                'quantity', 'total_price', 'package_quality', 'weight', 'length',
                'width', 'height', 'packing_quantity', 'tax_rate',
                'minimum_stock', 'maximum_stock', 'offer_price'
            ];

            foreach ($numericFields as $field) {
                if (isset($row[$field])) {
                    $row[$field] = $this->prepareNumericValue($row[$field]);
                }
            }

            // Preprocesar fechas
            $dateFields = ['offer_start_date', 'offer_end_date'];
            foreach ($dateFields as $field) {
                if (isset($row[$field])) {
                    $row[$field] = $this->prepareDateValue($row[$field]);
                }
            }

            // Buscar producto por código si existe
            $product = null;
            if (!empty($row['code'])) {
                $product = Product::where('code', $row['code'])
                    ->where('store_id', $this->store->id)
                    ->first();
            }

            // Si no existe el producto, crearlo
            if (!$product) {
                // Obtener o crear categoría si se proporciona el nombre
                $categoryId = $row['category_id'] ?? null;
                if (!$categoryId && !empty($row['category_name'])) {
                    $category = Category::firstOrCreate(
                        [
                            'store_id' => $this->store->id,
                            'name' => $row['category_name']
                        ],
                        [
                            'slug' => Str::slug($row['category_name']),
                            'is_active' => true
                        ]
                    );
                    $categoryId = $category->id;
                }

                // Si no hay categoría, usar la predeterminada
                if (!$categoryId) {
                    $categoryId = Category::where('store_id', $this->store->id)->first()?->id ?? 1;
                }

                // Obtener o crear marca si se proporciona el nombre
                $brandId = $row['brand_id'] ?? null;
                if (!$brandId && !empty($row['brand_name'])) {
                    $brand = Brand::firstOrCreate(
                        [
                            'store_id' => $this->store->id,
                            'name' => $row['brand_name']
                        ],
                        [
                            'slug' => Str::slug($row['brand_name']),
                            'is_active' => true
                        ]
                    );
                    $brandId = $brand->id;
                }

                // Si no hay marca, usar la predeterminada
                if (!$brandId) {
                    $brandId = Brand::where('store_id', $this->store->id)->first()?->id ?? 1;
                }

                // Preparar los datos básicos del producto
                $productData = [
                    'product_name' => $row['product_name'],
                    'store_id' => $this->store->id,
                    'supplier_id' => $this->importOrder->provider_id,
                    'category_id' => $categoryId,
                    'brand_id' => $brandId,
                    'measurement_unit_id' => $row['measurement_unit_id'] ?? 1,
                    'status' => true,
                    'description' => $row['description'] ?? null,
                    'hs_code' => $row['hs_code'] ?? null,
                    'supplier_code' => $row['supplier_code'] ?? null,
                    'packing_type' => $row['packing_type'] ?? null,
                    'packing_quantity' => $row['packing_quantity'] ?? null,
                    'weight' => $row['weight'] ?? null,
                    'length' => $row['length'] ?? null,
                    'width' => $row['width'] ?? null,
                    'height' => $row['height'] ?? null,
                    'barcode' => $row['barcode'] ?? null,
                    'ean_code' => $row['ean_code'] ?? null,
                    'is_taxable' => $this->convertBooleanValue($row['is_taxable'] ?? false),
                    'tax_rate' => $row['tax_rate'] ?? null,
                    'minimum_stock' => $row['minimum_stock'] ?? null,
                    'maximum_stock' => $row['maximum_stock'] ?? null,
                    'offer_price' => $row['offer_price'] ?? null,
                    'offer_start_date' => $row['offer_start_date'] ?? null,
                    'offer_end_date' => $row['offer_end_date'] ?? null,
                ];

                // Solo asignar código si está presente en el CSV
                // El trait HasSku generará automáticamente el SKU (no el código)
                if (!empty($row['code'])) {
                    $productData['code'] = $row['code'];
                } else {
                    // Generar un código único para el producto si no se proporciona
                    // El código es diferente del SKU y necesitamos asegurar que tenga un valor
                    $productData['code'] = $this->generateProductCode($row['product_name']);
                }

                // No configuramos 'slug' ni 'sku' manualmente, dejamos que los traits los generen
                // BinaryCats\Sku\HasSku se encargará de generar el SKU
                // Spatie\Sluggable\HasSlug se encargará de generar el slug

                // Crear producto con todos los campos necesarios y dejar que los traits
                // SkuOptions y SlugOptions generen los valores automáticamente
                try {
                    // Validamos campos individuales antes de la creación
                    if (isset($productData['weight']) && !is_numeric($productData['weight'])) {
                        throw new \Exception("El peso no es un número válido: {$productData['weight']}");
                    }

                    if (isset($productData['offer_start_date']) && !$this->isValidDate($productData['offer_start_date'])) {
                        throw new \Exception("La fecha de inicio de oferta no es válida: {$productData['offer_start_date']}");
                    }

                    if (isset($productData['offer_end_date']) && !$this->isValidDate($productData['offer_end_date'])) {
                        throw new \Exception("La fecha de fin de oferta no es válida: {$productData['offer_end_date']}");
                    }

                    $product = Product::create($productData);

                    // Procesar atributos dinámicos (columnas que comienzan con "attribute_")
                    $this->processProductAttributes($product, $row);
                } catch (\Exception $e) {
                    throw new \Exception("Error al crear el producto {$row['product_name']}: " . $e->getMessage());
                }
            }

            // Crear el item de importación
            try {
                ComexItem::create([
                    'store_id' => $this->store->id,
                    'import_order_id' => $this->importOrder->id,
                    'product_id' => $product->id,
                    'package_quality' => $row['package_quality'] ?? 1,
                    'quantity' => $row['quantity'],
                    'total_price' => $row['total_price'],
                ]);
            } catch (\Exception $e) {
                throw new \Exception("Error al crear el item para {$product->product_name}: " . $e->getMessage());
            }
        }
    }

    /**
     * Procesa las columnas de atributos y las asocia al producto
     *
     * @param Product $product
     * @param array $row
     * @return void
     */
    protected function processProductAttributes(Product $product, array $row): void
    {
        // Buscar todas las columnas que comienzan con "attribute_"
        foreach ($row as $key => $value) {
            if (!empty($value) && is_string($key) && strpos($key, 'attribute_') === 0) {
                // Obtener el nombre del atributo removiendo el prefijo "attribute_"
                $attributeName = Str::title(str_replace('attribute_', '', $key));

                // Buscar o crear el atributo
                $attribute = Attribute::firstOrCreate(
                    [
                        'store_id' => $this->store->id,
                        'name' => $attributeName
                    ],
                    [
                        'is_active' => true,
                        'is_required' => false
                    ]
                );

                // Buscar o crear el valor del atributo
                $attributeValue = AttributeValue::firstOrCreate(
                    [
                        'attribute_id' => $attribute->id,
                        'value' => $value
                    ]
                );

                // Asociar el valor del atributo al producto
                $product->product_attribute_values()->create([
                    'attribute_id' => $attribute->id,
                    'attribute_value_id' => $attributeValue->id
                ]);
            }
        }
    }

    /**
     * Convierte un valor booleano de texto a booleano real
     */
    protected function convertBooleanValue($value): bool
    {
        if (is_string($value)) {
            $value = strtolower($value);
            return in_array($value, ['true', 'yes', 'si', '1', 'y', 's']);
        }

        return (bool) $value;
    }

    protected function generateProductCode(string $productName): string
    {
        // Generar un prefijo de 3 letras basado en el nombre del producto
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $productName), 0, 3));

        // Añadir un componente aleatorio para evitar colisiones
        $random = strtoupper(Str::random(4));

        // Añadir timestamp para garantizar unicidad
        $timestamp = now()->format('ymd');

        return "{$prefix}{$timestamp}{$random}";
    }

    public function rules(): array
    {
        return [
            '*.product_name' => ['required', 'string', 'max:255'],
            '*.quantity' => ['required'],  // Eliminamos 'numeric' para validar después de convertir
            '*.total_price' => ['required'], // Eliminamos 'numeric' para validar después de convertir
            // Campos opcionales pero importantes
            '*.code' => ['nullable', 'string', 'max:50'],
            '*.package_quality' => ['nullable', 'numeric', 'min:0'],
            '*.category_name' => ['nullable', 'string', 'max:255'],
            '*.brand_name' => ['nullable', 'string', 'max:255'],
            '*.category_id' => ['nullable', 'integer'],
            '*.brand_id' => ['nullable', 'integer'],
            // Otros campos opcionales
            '*.description' => ['nullable', 'string'],
            '*.hs_code' => ['nullable', 'string'],
            '*.supplier_code' => ['nullable', 'string'],
            '*.measurement_unit_id' => ['nullable', 'integer'],
            '*.weight' => ['nullable', 'numeric'],
            '*.length' => ['nullable', 'numeric'],
            '*.width' => ['nullable', 'numeric'],
            '*.height' => ['nullable', 'numeric'],
            '*.packing_type' => ['nullable', 'string'],
            '*.packing_quantity' => ['nullable', 'numeric'],
            '*.barcode' => ['nullable', 'string', 'max:255'],
            '*.ean_code' => ['nullable', 'string', 'max:255'],
            '*.is_taxable' => ['nullable', 'boolean'],
            '*.tax_rate' => ['nullable', 'numeric'],
            '*.minimum_stock' => ['nullable', 'numeric'],
            '*.maximum_stock' => ['nullable', 'numeric'],
            '*.offer_price' => ['nullable', 'numeric'],
            '*.offer_start_date' => ['nullable', 'date'],
            '*.offer_end_date' => ['nullable', 'date'],
            // Campos para atributos
            '*.attribute_*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function customValidationMessages()
    {
        return [
            'product_name.required' => 'El nombre del producto es obligatorio',
            'quantity.required' => 'La cantidad es obligatoria',
            'total_price.required' => 'El precio total es obligatorio',
        ];
    }

    /**
     * Validación después de transformar los valores
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $rows = $validator->getData();
            $rowIndex = 0;

            foreach ($rows as $row) {
                // Validar campos numéricos
                $numericFields = ['quantity', 'total_price', 'package_quality', 'weight', 'length', 'width', 'height'];
                foreach ($numericFields as $field) {
                    if (isset($row[$field]) && !empty($row[$field])) {
                        $numericValue = $this->prepareNumericValue($row[$field]);
                        if (!is_numeric($numericValue)) {
                            $validator->errors()->add(
                                "{$rowIndex}.{$field}",
                                "El campo {$field} debe ser un número válido. Valor: {$row[$field]}"
                            );
                        }
                    }
                }

                // Validar fechas
                $dateFields = ['offer_start_date', 'offer_end_date'];
                foreach ($dateFields as $field) {
                    if (isset($row[$field]) && !empty($row[$field])) {
                        $dateValue = $this->prepareDateValue($row[$field]);
                        if (!$this->isValidDate($dateValue)) {
                            $validator->errors()->add(
                                "{$rowIndex}.{$field}",
                                "El campo {$field} debe ser una fecha válida en formato DD/MM/AAAA. Valor: {$row[$field]}"
                            );
                        }
                    }
                }

                $rowIndex++;
            }
        });
    }

    /**
     * Comprueba si una fecha es válida
     */
    protected function isValidDate($date): bool
    {
        if (empty($date)) {
            return true;
        }

        try {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return false;
            }

            $d = Carbon::createFromFormat('Y-m-d', $date);
            return $d && $d->format('Y-m-d') === $date;
        } catch (\Exception $e) {
            return false;
        }
    }
}
