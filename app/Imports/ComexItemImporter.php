<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use App\Models\ComexItem;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\MeasurementUnit;
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
                return (string) floatval($value);
            }

            // Eliminar caracteres no numéricos excepto punto y coma
            $value = preg_replace('/[^\d.,]/', '', $value);

            // Manejar comas como separador decimal
            $value = str_replace(',', '.', $value);

            // Verificar si ahora es un número válido
            if (is_numeric($value)) {
                return (string) floatval($value);
            }
        } elseif (is_numeric($value)) {
            // Si ya es numérico, asegurarnos que sea string para consistencia
            return (string) floatval($value);
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

    /**
     * Validación antes de importar - esto es clave para prevenir errores
     */
    public function prepareForValidation($data, $index)
    {
        // Preprocesar valores numéricos antes de la validación
        $numericFields = [
            'quantity', 'total_price', 'package_quality', 'weight', 'length',
            'width', 'height', 'packing_quantity', 'tax_rate',
            'minimum_stock', 'maximum_stock', 'offer_price'
        ];

        foreach ($numericFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->prepareNumericValue($data[$field]);
            }
        }

        // Preprocesar fechas antes de la validación
        $dateFields = ['offer_start_date', 'offer_end_date'];
        foreach ($dateFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->prepareDateValue($data[$field]);
            }
        }

        // Preprocesar valores booleanos
        if (isset($data['is_taxable'])) {
            // Convertir valores de texto a booleano
            $data['is_taxable'] = $this->convertBooleanValue($data['is_taxable']);
        }

        // Procesar campos de atributos dinámicos (attribute_*)
        foreach ($data as $key => $value) {
            if (is_string($key) && strpos($key, 'attribute_') === 0 && !is_null($value)) {
                // Convertir todos los valores de attribute_* a string
                $data[$key] = (string)$value;
            }
        }

        // Corregir códigos de barras (eliminar notación científica)
        if (isset($data['barcode'])) {
            if (strpos($data['barcode'], 'E+') !== false || strpos($data['barcode'], 'e+') !== false) {
                // Convertir de notación científica a número regular
                $data['barcode'] = number_format((float)$data['barcode'], 0, '', '');
            }
        }

        if (isset($data['ean_code'])) {
            // Asegurar que ean_code sea string
            $data['ean_code'] = (string)$data['ean_code'];
        }

        return $data;
    }

    public function collection(Collection $rows)
    {
        $index = 0;
        foreach ($rows as $row) {
            // Log para diagnóstico
            $this->logImportData($row, $index++);

            // Preprocesar valores numéricos (convertir comas en puntos)
            $numericFields = [
                'quantity',
                'total_price',
                'package_quality',
                'weight',
                'length',
                'width',
                'height',
                'packing_quantity',
                'tax_rate',
                'minimum_stock',
                'maximum_stock',
                'offer_price'
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
                $measurementUnitId = $this->getMeasurementUnitId($row['measurement_unit_id'] ?? null);

                $productData = [
                    'product_name' => $row['product_name'],
                    'store_id' => $this->store->id,
                    'supplier_id' => $this->importOrder->provider_id,
                    'category_id' => $categoryId,
                    'brand_id' => $brandId,
                    'measurement_unit_id' => $measurementUnitId,
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

                // Registrar la unidad de medida que se va a usar
                \Log::info('Usando unidad de medida ID=' . $measurementUnitId . ' para producto ' . $row['product_name']);

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

                    // Registrar información adicional para depuración
                    $this->logImportData($row, $index, [
                        'measurement_unit_id' => $productData['measurement_unit_id'],
                        'barcode_processed' => $productData['barcode'],
                        'ean_code_processed' => $productData['ean_code'],
                    ]);

                    // Verificar explícitamente si la unidad de medida existe
                    if (!MeasurementUnit::find($productData['measurement_unit_id'])) {
                        throw new \Exception("La unidad de medida con ID {$productData['measurement_unit_id']} no existe en la base de datos");
                    }

                    $product = Product::create($productData);

                    // Procesar atributos dinámicos (columnas que comienzan con "attribute_")
                    $this->processProductAttributes($product, $row->toArray());
                } catch (\Exception $e) {
                    // Registrar error detallado
                    \Log::error('Error al crear producto: ' . $e->getMessage() . '. Datos del producto: ' . json_encode($productData));
                    throw new \Exception("Error al crear el producto {$row['product_name']}: " . $e->getMessage());
                }
            }

            // Crear el item de importación
            try {
                ComexItem::create([
                    'store_id' => $this->store->id,
                    'import_order_id' => $this->importOrder->id,
                    'product_id' => $product->id,
                    'package_quality' => $row['package_quality'] ?? 1,  // Asegurarse de usar el valor procesado
                    'quantity' => $row['quantity'],
                    'total_price' => $row['total_price'],
                    'cif_unit' => $row['total_price'] > 0 && $row['quantity'] > 0
                        ? (float)$row['total_price'] / (float)$row['quantity']
                        : 0,  // Calcular cif_unit automáticamente
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
                // Asegurarnos de que el valor sea string
                $value = (string)$value;

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
            '*.quantity' => ['required', 'numeric'],  // Ahora podemos volver a añadir 'numeric'
            '*.total_price' => ['required', 'numeric'], // Ahora podemos volver a añadir 'numeric'
            // Campos opcionales pero importantes
            '*.code' => ['nullable', 'string', 'max:50'],
            '*.package_quality' => ['nullable', 'numeric', 'min:0'], // Volver a añadir 'numeric'
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
            // Campos para atributos - cambiamos la regla para ser más permisiva y luego los convertimos a string
            '*.attribute_*' => ['nullable'],
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
                $numericFields = [
                    'quantity',
                    'total_price',
                    'package_quality',  // Asegurarse de incluir package_quality aquí
                    'weight',
                    'length',
                    'width',
                    'height'
                ];
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

    /**
     * Para diagnóstico - agregar a la clase para ver qué valores son problemáticos
     */
    protected function logImportData($row, $index, array $additionalData = [])
    {
        $logData = [
            'row' => $index,
            'product_name' => $row['product_name'] ?? 'N/A',
            'weight' => $row['weight'] ?? 'N/A',
            'weight_processed' => $this->prepareNumericValue($row['weight'] ?? null),
            'is_numeric' => is_numeric($this->prepareNumericValue($row['weight'] ?? null)) ? 'true' : 'false'
        ];

        // Agregar datos adicionales para depuración
        $logData = array_merge($logData, $additionalData);

        // Crear un archivo de log para depuración
        $logPath = storage_path('logs/import_debug.log');
        file_put_contents(
            $logPath,
            json_encode($logData) . PHP_EOL,
            FILE_APPEND
        );
    }

    /**
     * Obtiene o crea una unidad de medida predeterminada
     *
     * @return int ID de la unidad de medida
     */
    protected function getDefaultMeasurementUnitId(): int
    {
        try {
            // Comprobar si la tabla tiene algún registro
            $hasUnits = MeasurementUnit::count() > 0;

            if ($hasUnits) {
                // Si hay unidades de medida, obtener la primera
                $unit = MeasurementUnit::first();
                \Log::info('Unidad de medida encontrada: ID=' . $unit->id . ', Nombre=' . $unit->name);
                return $unit->id;
            } else {
                // No hay unidades de medida, crear una
                \Log::info('No se encontraron unidades de medida. Creando una nueva...');

                // Incluimos el campo 'code' que es obligatorio
                $unit = MeasurementUnit::create([
                    'name' => 'Unidad',
                    'code' => 'UN',
                    'abbreviation' => 'UN',
                    'description' => 'Unidad por defecto',
                    'is_base_unit' => true,
                    'conversion_factor' => 1
                ]);

                \Log::info('Nueva unidad de medida creada: ID=' . $unit->id);
                return $unit->id;
            }
        } catch (\Exception $e) {
            \Log::error('Error obteniendo/creando unidad de medida: ' . $e->getMessage());

            // Intentar obtener las columnas disponibles para diagnóstico
            try {
                $columns = \DB::getSchemaBuilder()->getColumnListing('measurement_units');
                \Log::info('Columnas disponibles en measurement_units: ' . implode(', ', $columns));
            } catch (\Exception $ex) {
                \Log::error('No se pudo obtener las columnas: ' . $ex->getMessage());
            }

            // Intentar con un método más seguro
            return $this->getKnownMeasurementUnitId();
        }
    }

    /**
     * Obtiene el ID de una unidad de medida conocida que existe en la base de datos
     *
     * @return int ID de una unidad de medida existente
     */
    protected function getKnownMeasurementUnitId(): int
    {
        // Consulta directa a la base de datos para obtener un ID existente
        $result = \DB::select('SELECT id FROM measurement_units LIMIT 1');
        if (!empty($result)) {
            return $result[0]->id;
        }

        // Si aún no hay unidades de medida, crear una directamente con SQL
        try {
            // Incluir todos los campos requeridos según el log
            $sql = "INSERT INTO measurement_units (name, code, abbreviation, description, is_base_unit, conversion_factor, created_at, updated_at)
                   VALUES ('Unidad', 'UN', 'UN', 'Unidad por defecto', 1, 1, NOW(), NOW())";
            \DB::statement($sql);

            // Obtener el ID del registro creado
            $result = \DB::select('SELECT id FROM measurement_units ORDER BY id DESC LIMIT 1');
            if (!empty($result)) {
                $unitId = $result[0]->id;
                \Log::info('Unidad de medida creada con SQL: ID=' . $unitId);
                return $unitId;
            }
        } catch (\Exception $e) {
            \Log::error('Error al intentar crear unidad de medida con SQL: ' . $e->getMessage());
        }

        // Si todo falla, verificar si hay alguna unidad existente
        try {
            // Buscar cualquier unidad de medida existente
            $existingUnit = MeasurementUnit::first();
            if ($existingUnit) {
                \Log::info('Usando unidad de medida existente de último recurso: ID=' . $existingUnit->id);
                return $existingUnit->id;
            }

            // Si realmente no hay ninguna unidad, lanzar una excepción descriptiva
            throw new \Exception('No se pudo crear ni encontrar ninguna unidad de medida');
        } catch (\Exception $e) {
            \Log::error('Error crítico con unidades de medida: ' . $e->getMessage());
            throw $e; // Re-lanzar para detener la importación con un mensaje claro
        }
    }

    /**
     * Obtiene una unidad de medida por su ID o nombre, o usa la predeterminada
     *
     * @param mixed $idOrName ID o nombre de la unidad de medida
     * @return int ID de la unidad de medida
     */
    protected function getMeasurementUnitId($idOrName): int
    {
        try {
            // Si no se proporciona, usar la predeterminada
            if (empty($idOrName)) {
                $defaultId = $this->getDefaultMeasurementUnitId();

                // Verificar explícitamente que la unidad existe
                if (MeasurementUnit::find($defaultId)) {
                    return $defaultId;
                } else {
                    throw new \Exception("La unidad de medida predeterminada con ID {$defaultId} no existe");
                }
            }

            // Si es numérico, intentar buscar por ID
            if (is_numeric($idOrName)) {
                $unit = MeasurementUnit::find($idOrName);
                if ($unit) {
                    return $unit->id;
                }
            }

            // Intentar buscar por nombre o código
            $unit = MeasurementUnit::where('name', $idOrName)
                ->orWhere('code', $idOrName)
                ->first();

            if ($unit) {
                return $unit->id;
            }

            // Si no se encuentra, usar la predeterminada con verificación explícita
            $defaultId = $this->getDefaultMeasurementUnitId();
            if (!MeasurementUnit::find($defaultId)) {
                throw new \Exception("La unidad de medida predeterminada con ID {$defaultId} no existe");
            }

            return $defaultId;
        } catch (\Exception $e) {
            \Log::error('Error buscando unidad de medida: ' . $e->getMessage());
            throw $e; // Re-lanzar para detener la importación con un mensaje claro
        }
    }
}
