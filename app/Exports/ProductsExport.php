<?php

namespace App\Exports;

use App\Models\Product;
use App\Models\Attribute;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ProductsExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithChunkReading
{
    protected $storeId;
    protected $attributes;
    protected $productIds = null;

    /**
     * @param array|null $productIds IDs de productos específicos para exportar (opcional)
     */
    public function __construct(array $productIds = null)
    {
        $this->storeId = Filament::getTenant()->id;
        $this->productIds = $productIds;

        try {
            // Obtener todos los atributos de la tienda actual para crear encabezados dinámicos
            $this->attributes = Attribute::where('store_id', $this->storeId)->get();
        } catch (\Exception $e) {
            Log::error('Error al cargar atributos para exportación: ' . $e->getMessage());
            $this->attributes = collect();
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        $query = Product::query()
            ->where('store_id', $this->storeId)
            ->with([
                'category',
                'brand',
                'measurementUnit',
                'supplier',
                'product_attribute_values.attribute',
                'product_attribute_values.attributeValue'
            ]);

        // Si hay IDs específicos, filtrar por ellos
        if ($this->productIds) {
            $query->whereIn('id', $this->productIds);
        }

        return $query;
    }

    /**
     * @param Product $product
     * @return array
     */
    public function map($product): array
    {
        // Preparar los datos básicos del producto
        $row = [
            'nombre' => $product->product_name,
            'codigo' => $product->code,
            'codigo_barras' => $product->barcode,
            'precio' => $product->price,
            'stock' => $product->stock,
            'categoria' => $product->category ? $product->category->name : null,
            'marca' => $product->brand ? $product->brand->name : null,
            'descripcion' => $product->description,
            'stock_minimo' => $product->minimum_stock,
            'stock_maximo' => $product->maximum_stock,
            'packing_type' => $product->packing_type,
            'packing_quantity' => $product->packing_quantity,
            'peso' => $product->weight,
            'largo' => $product->length,
            'ancho' => $product->width,
            'alto' => $product->height,
            'ean' => $product->ean_code,
            'codigo_proveedor' => $product->supplier_code,
            'notas' => $product->additional_notes,
            'precio_oferta' => $product->offer_price,
            'es_gravable' => $product->is_taxable ? 'SI' : 'NO',
            'tasa_impuesto' => $product->tax_rate,
            'unidad_medida' => $product->measurementUnit ? $product->measurementUnit->name : null,
            'proveedor' => $product->supplier ? $product->supplier->name : null,
        ];

        // Añadir los atributos personalizados al array
        foreach ($this->attributes as $attribute) {
            $attributeName = 'atributo_' . strtolower($attribute->name);

            // Buscar si este producto tiene un valor para este atributo
            $attributeValue = $product->product_attribute_values
                ->where('attribute_id', $attribute->id)
                ->first();

            $row[$attributeName] = $attributeValue ? $attributeValue->attributeValue->value : null;
        }

        return $row;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        // Encabezados básicos
        $headings = [
            'nombre',
            'codigo',
            'codigo_barras',
            'precio',
            'stock',
            'categoria',
            'marca',
            'descripcion',
            'stock_minimo',
            'stock_maximo',
            'packing_type',
            'packing_quantity',
            'peso',
            'largo',
            'ancho',
            'alto',
            'ean',
            'codigo_proveedor',
            'notas',
            'precio_oferta',
            'es_gravable',
            'tasa_impuesto',
            'unidad_medida',
            'proveedor'
        ];

        // Añadir encabezados para los atributos personalizados
        foreach ($this->attributes as $attribute) {
            $headings[] = 'atributo_' . strtolower($attribute->name);
        }

        return $headings;
    }

    /**
     * Procesamiento en chunks para mejorar rendimiento y uso de memoria
     */
    public function chunkSize(): int
    {
        return 50; // Reducido a 50 para mejorar rendimiento
    }
}
