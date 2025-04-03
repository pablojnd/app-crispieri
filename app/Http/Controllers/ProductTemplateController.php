<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class ProductTemplateController extends Controller
{
    /**
     * Genera y descarga un archivo CSV de ejemplo para la importación de productos
     */
    public function downloadTemplate()
    {
        // Encabezados de las columnas - Primero las obligatorias, luego las opcionales
        $headers = [
            // Obligatorios
            'nombre',
            'codigo',
            'precio',
            'stock',
            'categoria',
            'marca',
            // Opcionales
            'descripcion',
            'codigo_barras',
            'stock_minimo',
            'stock_maximo',
            'packing_type',         // Tipo de empaque (nuevo)
            'packing_quantity',     // Cantidad por empaque (nuevo)
            'peso',
            'largo',
            'ancho',
            'alto',
            'ean',
            'codigo_proveedor',
            'precio_oferta',
            'es_gravable',
            'tasa_impuesto',
            'notas',
            'atributo_color',
            'atributo_talla'
        ];

        // Indicaciones de obligatoriedad
        $fieldRequirements = [
            '[OBLIGATORIO] Nombre del producto',
            '[OBLIGATORIO] Código interno único',
            '[OBLIGATORIO] Precio de venta',
            '[OBLIGATORIO] Cantidad disponible',
            '[OBLIGATORIO] Nombre de la categoría',
            '[OBLIGATORIO] Nombre de la marca',
            '[OPCIONAL] Descripción detallada',
            '[OPCIONAL] Código de barras',
            '[OPCIONAL] Stock mínimo permitido',
            '[OPCIONAL] Stock máximo permitido',
            '[OPCIONAL] Tipo de empaque (ej: UND, MT2, KG)',
            '[OPCIONAL] Cantidad por empaque',
            '[OPCIONAL] Peso en kilogramos',
            '[OPCIONAL] Largo en centímetros',
            '[OPCIONAL] Ancho en centímetros',
            '[OPCIONAL] Alto en centímetros',
            '[OPCIONAL] Código EAN',
            '[OPCIONAL] Código asignado por el proveedor',
            '[OPCIONAL] Precio promocional',
            '[OPCIONAL] ¿Es gravable? (SI/NO)',
            '[OPCIONAL] Porcentaje de impuesto',
            '[OPCIONAL] Notas adicionales',
            '[OPCIONAL] Valor del atributo color',
            '[OPCIONAL] Valor del atributo talla'
        ];

        // Productos de ejemplo
        $exampleProducts = [
            [
                'Laptop Avanzada XPS',
                'LAP001',
                1599.99,
                10,
                'Tecnología',
                'TechBrand',
                'Laptop de alto rendimiento con procesador i7 de última generación',
                '7890123456789',
                5,
                20,
                'UND',
                1,
                2.1,
                35.6,
                24.5,
                1.8,
                '9876543210123',
                'PROV-LAP001',
                1499.99,
                'SI',
                16,
                'Modelo 2024 con garantía extendida',
                'Negro',
                '15 pulgadas'
            ],
            [
                'Cerámica para piso',
                'CER001',
                29.99,
                500,
                'Construcción',
                'CeramicPro',
                'Cerámica de alta resistencia para pisos',
                '3214569870123',
                100,
                1000,
                'MT2',
                1.5,
                18.5,
                60.0,
                60.0,
                1.0,
                '3698521470123',
                'PROV-CER60',
                24.99,
                'SI',
                16,
                'Paquetes de 1.5 m²',
                'Beige',
                '60x60'
            ],
            [
                'Teclado Mecánico Gamer',
                'KB001',
                89.99,
                20,
                'Accesorios',
                'GamerTech',
                'Teclado mecánico con retroiluminación RGB y switches Cherry MX',
                '2345678901234',
                10,
                50,
                'UND',
                1,
                1.2,
                44.5,
                14.0,
                3.5,
                '2345678901234',
                'PROV-KB001',
                79.99,
                'SI',
                16,
                'Switches azules táctiles',
                'RGB',
                'Completo'
            ],
            [
                'Paquete de Clavos',
                'CLV100',
                5.99,
                50,
                'Ferretería',
                'ToolMaster',
                'Clavos de acero inoxidable',
                '9876123450987',
                20,
                100,
                'KG',
                2.5,
                2.5,
                15.0,
                10.0,
                5.0,
                '9876123450987',
                'PROV-CLV25',
                4.99,
                'SI',
                16,
                'Paquetes de 2.5 kg',
                'Plateado',
                '2 pulgadas'
            ],
            [
                'Audífonos Bluetooth ANC',
                'AUD001',
                129.99,
                12,
                'Audio',
                'SoundExpert',
                'Audífonos con cancelación activa de ruido y tecnología Bluetooth 5.2',
                '4567890123456',
                5,
                25,
                'UND',
                1,
                0.25,
                18.0,
                15.0,
                8.0,
                '4567890123456',
                'PROV-AUD001',
                119.99,
                'SI',
                16,
                'Batería de 30 horas de duración',
                'Blanco',
                'Over-ear'
            ]
        ];

        // Abrir un stream de memoria para crear el CSV
        $output = fopen('php://temp', 'r+');

        // Escribir el BOM para Excel reconozca correctamente caracteres especiales
        fputs($output, "\xEF\xBB\xBF");

        // Escribir encabezados
        fputcsv($output, $headers);

        // Escribir fila de indicaciones
        fputcsv($output, $fieldRequirements);

        // Escribir productos de ejemplo
        foreach ($exampleProducts as $product) {
            fputcsv($output, $product);
        }

        // Rebobinar el stream y leer todo el contenido
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        // Crear la respuesta con el archivo CSV
        $response = new Response($csvContent);
        $response->header('Content-Type', 'text/csv; charset=UTF-8');
        $response->header('Content-Disposition', 'attachment; filename="plantilla_productos.csv"');

        return $response;
    }
}
