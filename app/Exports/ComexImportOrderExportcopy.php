<?php

namespace App\Exports;

use App\Models\ComexImportOrder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Filament\Facades\Filament;

class ComexImportOrderExportcopy implements FromArray, WithHeadings, WithColumnWidths, WithStyles
{
    private const ORDER_COLUMNS = [
        'Tienda',
        'Número de Orden',
        'Referencia Externa',
        'Proveedor',
        'País de Origen',
        'Número SVE',
        'Tipo de Transporte',
        'Estado',
        'Fecha de Orden',
        'Salida Estimada',
        'Salida Real',
        'Llegada Estimada',
        'Llegada Real',
        'Total Contenedores',
        'Total Items',
    ];

    private const DOCUMENT_COLUMNS = [
        'Número Documento',
        'Tipo Documento',
        'Fecha Documento',
        'FOB',
        'Flete',
        'Seguro',
        'CIF',
        'Total Pagado',
        'Monto Pendiente',
        'Items',
    ];

    private const CONTAINER_COLUMNS = [
        'Número Contenedor',
        'Tipo Contenedor',
        'Peso',
        'Número Sello',
        'Costo',
        'Notas',
        'Items Contenedor',
    ];

    private const ITEM_COLUMNS = [
        'Producto',
        'Código',
        'Cantidad',
        'Precio Total',
        'CIF Unitario',
    ];

    private const EXPENSE_COLUMNS = [
        'Fecha Gasto',
        'Tipo Gasto',
        'Cantidad',
        'Monto',
        'Moneda',
        'Notas',
    ];

    protected $record;

    public function __construct(ComexImportOrder $record)
    {
        $this->record = $record;
    }

    private function getOrderData($order): array
    {
        return [
            $order->store->name,
            $order->reference_number,
            $order->external_reference,
            $order->provider?->name ?? '',
            $order->originCountry->country_name,
            $order->sve_registration_number,
            $order->type->getLabel(),
            $order->status->getLabel(),
            $order->order_date?->format('d/m/Y'),
            $order->estimated_departure?->format('d/m/Y'),
            $order->actual_departure?->format('d/m/Y'),
            $order->estimated_arrival?->format('d/m/Y'),
            $order->actual_arrival?->format('d/m/Y'),
            $order->containers->count(),
            $order->items->count(),
        ];
    }

    private function getDocumentData($document): array
    {
        $items = $document->items->map(function ($item) {
            return sprintf(
                "%s (Qty: %s)",
                $item->product->product_name ?? 'N/A',
                $item->quantity ?? 0
            );
        })->implode(', ');

        return [
            $document->document_number ?? '',
            $document->document_type?->getLabel() ?? '',
            $document->document_date?->format('d/m/Y') ?? '',
            number_format($document->fob_total ?? 0, 2),
            number_format($document->freight_total ?? 0, 2),
            number_format($document->insurance_total ?? 0, 2),
            number_format($document->cif_total ?? 0, 2),
            number_format($document->total_paid ?? 0, 2),
            number_format($document->pending_amount ?? 0, 2),
            $items,
        ];
    }

    private function getContainerData($container): array
    {
        $items = $container->items->map(function ($item) {
            return sprintf(
                "%s (Qty: %s, Peso: %s)",
                $item->product->product_name ?? 'N/A',
                $item->pivot->quantity ?? 0,
                number_format($item->pivot->weight ?? 0, 2)
            );
        })->implode(', ');

        return [
            $container->container_number ?? '',
            $container->type->getLabel() ?? '',
            number_format($container->weight ?? 0, 2),
            $container->seal_number ?? '',
            number_format($container->cost ?? 0, 2),
            $container->notes ?? '',
            $items,
        ];
    }

    private function getItemData($item): array
    {
        return [
            $item->product->product_name ?? '',
            $item->product->sku ?? '',
            number_format($item->quantity ?? 0, 2),
            number_format($item->total_price ?? 0, 2),
            number_format($item->cif_unit ?? 0, 2),
        ];
    }

    private function getExpenseData($expense): array
    {
        return [
            $expense->expense_date?->format('d/m/Y') ?? '',
            $expense->expense_type->getLabel() ?? '',
            number_format($expense->expense_quantity ?? 0, 2),
            number_format($expense->expense_amount ?? 0, 2),
            $expense->currency->code ?? '',
            $expense->notes ?? '',
        ];
    }

    public function array(): array
    {
        $tenant = Filament::getTenant();

        $order = ComexImportOrder::query()
            ->where('store_id', $tenant->id)
            ->where('id', $this->record->id)
            ->with([
                'store',
                'provider',
                'originCountry',
                'items.product',
                'containers.items.product',
                'documents.items.product',
                'documents.payments',
                'expenses.currency'
            ])
            ->first();

        // Obtener el máximo número de registros relacionados
        $maxRows = max([
            $order->documents->count(),
            $order->containers->count(),
            $order->items->count(),
            $order->expenses->count()
        ]);

        $rows = [];

        // Generar filas basadas en el máximo número de registros
        for ($i = 0; $i < $maxRows; $i++) {
            $rows[] = array_merge(
                // Datos de orden solo en primera fila
                $i === 0 ? $this->getOrderData($order) : array_fill(0, count(self::ORDER_COLUMNS), ''),

                // Datos de documentos
                isset($order->documents[$i]) ?
                    $this->getDocumentData($order->documents[$i]) :
                    array_fill(0, count(self::DOCUMENT_COLUMNS), ''),

                // Datos de contenedores
                isset($order->containers[$i]) ?
                    $this->getContainerData($order->containers[$i]) :
                    array_fill(0, count(self::CONTAINER_COLUMNS), ''),

                // Datos de items
                isset($order->items[$i]) ?
                    $this->getItemData($order->items[$i]) :
                    array_fill(0, count(self::ITEM_COLUMNS), ''),

                // Datos de gastos
                isset($order->expenses[$i]) ?
                    $this->getExpenseData($order->expenses[$i]) :
                    array_fill(0, count(self::EXPENSE_COLUMNS), '')
            );
        }

        return $rows;
    }

    public function headings(): array
    {
        return array_merge(
            self::ORDER_COLUMNS,
            self::DOCUMENT_COLUMNS,
            self::CONTAINER_COLUMNS,
            self::ITEM_COLUMNS,
            self::EXPENSE_COLUMNS
        );
    }

    public function columnWidths(): array
    {
        return [
            // Columnas de Orden (A-O)
            'A' => 15,  // Tienda
            'B' => 20,  // Número de Orden
            'C' => 20,  // Referencia Externa
            'D' => 20,  // Proveedor
            'E' => 15,  // País de Origen
            'F' => 15,  // Número SVE
            'G' => 15,  // Tipo de Transporte
            'H' => 20,  // Estado
            'I' => 20,  // Fecha de Orden
            'J' => 20,  // Salida Estimada
            'K' => 20,  // Salida Real
            'L' => 20,  // Llegada Estimada
            'M' => 20,  // Llegada Real
            'N' => 20,  // Total Contenedores
            'O' => 12,  // Total Items

            // Columnas de Documento (P-Y)
            'P' => 20,  // Número Documento
            'Q' => 20,  // Tipo Documento
            'R' => 20,  // Fecha Documento
            'S' => 12,  // FOB
            'T' => 12,  // Flete
            'U' => 12,  // Seguro
            'V' => 12,  // CIF
            'W' => 20,  // Total Pagado
            'X' => 20,  // Monto Pendiente
            'Y' => 40,  // Items

            // Columnas de Contenedor (Z-AF)
            'Z' => 20,  // Número Contenedor
            'AA' => 20, // Tipo Contenedor
            'AB' => 12, // Peso
            'AC' => 15, // Número Sello
            'AD' => 12, // Costo
            'AE' => 25, // Notas
            'AF' => 40, // Items Contenedor

            // Columnas de Item (AG-AK)
            'AG' => 40, // Producto
            'AH' => 15, // Código
            'AI' => 15, // Cantidad
            'AJ' => 15, // Precio Total
            'AK' => 12, // CIF Unitario

            // Columnas de Gasto (AL-AQ)
            'AL' => 15, // Fecha Gasto
            'AM' => 20, // Tipo Gasto
            'AN' => 12, // Cantidad
            'AO' => 12, // Monto
            'AP' => 10, // Moneda
            'AQ' => 30, // Notas
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = 'AQ'; // Última columna del reporte

        return [
            // Estilo para las cabeceras
            '1' => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center'],
            ],

            // Columnas de Orden (verde claro)
            'A1:O1' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'C6EFCE'],
                ],
            ],

            // Columnas de Documento (azul claro)
            'P1:Y1' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'BDD7EE'],
                ],
            ],

            // Columnas de Contenedor (amarillo claro)
            'Z1:AF1' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFF2CC'],
                ],
            ],

            // Columnas de Item (naranja claro)
            'AG1:AK1' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFE5CC'],
                ],
            ],

            // Columnas de Gasto (rosa claro)
            'AL1:AQ1' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFE2EF'],
                ],
            ],

            // Estilo para todo el contenido
            'A1:' . $lastColumn . $sheet->getHighestRow() => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
                'alignment' => [
                    'vertical' => 'center',
                ],
            ],
        ];
    }
}
