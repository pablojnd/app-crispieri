<?php

namespace App\Exports;

use App\Enums\ExpenseType;
use App\Models\ComexImportOrder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Filament\Facades\Filament;

class ComexImportOrderExport implements FromArray, WithHeadings, WithColumnWidths, WithStyles
{
    private const EXPENSE_HEADERS = [
        'Cantidad',
        'Monto',
        'Estado'
    ];

    private const EXPENSE_COLUMNS = [
        'Gate In' => self::EXPENSE_HEADERS,
        'THC' => self::EXPENSE_HEADERS,
        'Apertura Manifiesto' => self::EXPENSE_HEADERS,
        'Garantía' => self::EXPENSE_HEADERS,
        'Carta Responsabilidad' => self::EXPENSE_HEADERS,
        'Emisión BL' => self::EXPENSE_HEADERS,
        'Demurrage' => self::EXPENSE_HEADERS,
        'Movimiento Contenedor' => self::EXPENSE_HEADERS,
        'Grúas' => self::EXPENSE_HEADERS,
        'Descarga' => self::EXPENSE_HEADERS,
        'Flete' => self::EXPENSE_HEADERS,
        'Otros' => self::EXPENSE_HEADERS,
    ];

    private const COLUMNS = [
        'Tienda',
        'Proveedor',
        'País Origen',
        'Referencia',
        'Ref. Externa',
        'Num. SVE',
        'Tipo',
        'Estado',
        'Fecha Orden',
        // Naviera
        'Naviera',
        'Salida Est.',
        'Llegada Est.',
        // Contenedores
        'Num. Contenedor',
        'Tipo Contenedor',
        'Peso (KG)',
        'Costo Flete',
        // Documentos
        'Num. Documento',
        'Tipo Doc.',
        'Clausula',
        'FOB',
        'Flete',
        'Seguro',
        'CIF',
        'Factor',
        'Pagado',
        'Pendiente',
        // Items
        'Producto',
        'Bultos',
        'Cantidad',
        'Precio Total',
        'Precio Unitario',
        'CIF Unitario',
        // Gastos separados por tipo con subcolumnas
    ];

    private function getFlattenedColumns(): array
    {
        $baseColumns = [
            'Tienda',
            'Proveedor',
            'País Origen',
            'Referencia',
            'Ref. Externa',
            'Num. SVE',
            'Tipo',
            'Estado',
            'Fecha Orden',
            // Naviera
            'Naviera',
            'Salida Est.',
            'Llegada Est.',
            // Contenedores
            'Num. Contenedor',
            'Tipo Contenedor',
            'Peso (KG)',
            'Costo Flete',
            // Documentos
            'Num. Documento',
            'Tipo Doc.',
            'Clausula',
            'FOB',
            'Flete',
            'Seguro',
            'CIF',
            'Factor',
            'Pagado',
            'Pendiente',
            // Items
            'Producto',
            'Bultos',
            'Cantidad',
            'Precio Total',
            'Precio Unitario',
            'CIF Unitario',
        ];

        foreach (self::EXPENSE_COLUMNS as $expense => $subColumns) {
            foreach ($subColumns as $subColumn) {
                $baseColumns[] = "$expense - $subColumn";
            }
        }

        $baseColumns[] = 'TOTAL GENERAL';

        return $baseColumns;
    }

    protected $record;

    public function __construct(ComexImportOrder $record)
    {
        $this->record = $record;
    }

    private function formatNumber($number, $decimals = 2): string
    {
        return number_format($number, $decimals, ',', '.');
    }

    private function getData($order): array
    {
        $rows = [];
        $maxItems = $order->items->count();
        $maxShippingLines = $order->shippingLines->count();
        // Obtener todos los contenedores de todas las navieras
        $containers = $order->shippingLines->flatMap->containers;
        $maxContainers = $containers->count();
        $maxDocs = $order->documents->count();
        $maxExpenses = $order->expenses->count();
        $maxRows = max($maxItems, $maxShippingLines, $maxContainers, $maxDocs, $maxExpenses);

        // Primera fila con datos principales
        $firstRow = [
            $order->store->name,
            $order->provider?->name,
            $order->originCountry?->name,
            $order->reference_number,
            $order->external_reference,
            $order->sve_registration_number,
            $order->type->getLabel(), // Cambiado de ucfirst($order->type) a $order->type->getLabel()
            $order->status->getLabel(),
            $order->order_date?->format('d/m/Y'),
        ];

        // Agregar primera naviera
        $firstShippingLine = $order->shippingLines->first();
        if ($firstShippingLine) {
            $firstRow = array_merge($firstRow, [
                $firstShippingLine->name,
                $firstShippingLine->estimated_departure?->format('d/m/Y'),
                $firstShippingLine->estimated_arrival?->format('d/m/Y'),
            ]);
        } else {
            $firstRow = array_merge($firstRow, array_fill(0, 3, ''));
        }

        // Agregar primer contenedor
        $firstContainer = $containers->first();
        if ($firstContainer) {
            $firstRow = array_merge($firstRow, [
                $firstContainer->container_number,
                $firstContainer->type->getLabel(),
                $this->formatNumber($firstContainer->weight, 2),
                $this->formatNumber($firstContainer->cost, 2),
            ]);
        } else {
            $firstRow = array_merge($firstRow, array_fill(0, 4, ''));
        }

        // Agregar primer documento
        $firstDoc = $order->documents->first();
        if ($firstDoc) {
            $firstRow = array_merge($firstRow, [
                $firstDoc->document_number,
                $firstDoc->document_type->getLabel(),
                $firstDoc->document_clause?->getLabel(),
                $this->formatNumber($firstDoc->fob_total, 2),
                $this->formatNumber($firstDoc->freight_total, 2),
                $this->formatNumber($firstDoc->insurance_total, 2),
                $this->formatNumber($firstDoc->cif_total, 2),
                $this->formatNumber($firstDoc->factor, 9),
                $this->formatNumber($firstDoc->total_paid, 2),
                $this->formatNumber($firstDoc->pending_amount, 2),
            ]);
        } else {
            $firstRow = array_merge($firstRow, array_fill(0, 10, '')); // Espacios vacíos para documento
        }

        // Agregar primer item
        $firstItem = $order->items->first();
        if ($firstItem) {
            $firstRow = array_merge($firstRow, [
                $firstItem->product?->product_name ?? 'N/A',
                $firstItem->package_quality,
                $this->formatNumber($firstItem->quantity, 2),
                $this->formatNumber($firstItem->total_price, 4),
                $this->formatNumber($firstItem->unit_price, 4),
                $this->formatNumber($firstItem->cif_unit, 4),
            ]);
        } else {
            $firstRow = array_merge($firstRow, array_fill(0, 6, '')); // Espacios vacíos para item
        }

        // Reemplazar la sección de gastos con columnas detalladas (sin columna total)
        $expensesByType = $order->expenses->groupBy('expense_type');

        foreach (ExpenseType::cases() as $expenseType) {
            $expense = $expensesByType->get($expenseType->value)?->first();
            if ($expense) {
                $firstRow[] = $this->formatNumber($expense->expense_quantity, 2);
                $firstRow[] = $this->formatNumber($expense->expense_amount, 2);
                $firstRow[] = $expense->payment_status->getLabel();
            } else {
                $firstRow = array_merge($firstRow, array_fill(0, 3, ''));
            }
        }

        $firstRow[] = ''; // Agregar espacio vacío en la primera fila

        $rows[] = $firstRow;

        // Filas adicionales
        for ($i = 1; $i < $maxRows; $i++) {
            $row = array_fill(0, count($this->getFlattenedColumns()), ''); // Inicializar fila vacía

            // Agregar item adicional si existe
            if ($i < $maxItems) {
                $item = $order->items[$i];
                $itemData = [
                    $item->product?->product_name ?? 'N/A',
                    $item->package_quality,
                    $this->formatNumber($item->quantity, 2),
                    $this->formatNumber($item->total_price, 4),
                    $this->formatNumber($item->unit_price, 4),
                    $this->formatNumber($item->cif_unit, 4),
                ];
                // Corregir el índice para los items (26 es el índice después de los documentos)
                array_splice($row, 26, 6, $itemData);
            }

            // Agregar datos de naviera adicional si existe
            if ($i < $maxShippingLines) {
                $shippingLine = $order->shippingLines[$i];
                array_splice($row, 9, 3, [
                    $shippingLine->name,
                    $shippingLine->estimated_departure?->format('d/m/Y'),
                    $shippingLine->estimated_arrival?->format('d/m/Y'),
                ]);
            }

            // Agregar contenedor si existe
            if ($i < $maxContainers) {
                $container = $containers[$i];
                array_splice($row, 12, 4, [
                    $container->container_number,
                    $container->type->getLabel(),
                    $this->formatNumber($container->weight, 2),
                    $this->formatNumber($container->cost, 2),
                ]);
            }

            // Agregar documento adicional si existe
            if ($i < $maxDocs) {
                $document = $order->documents[$i];
                array_splice($row, 16, 10, [
                    $document->document_number,
                    $document->document_type->getLabel(),
                    $document->document_clause?->getLabel(),
                    $this->formatNumber($document->fob_total, 2),
                    $this->formatNumber($document->freight_total, 2),
                    $this->formatNumber($document->insurance_total, 2),
                    $this->formatNumber($document->cif_total, 2),
                    $this->formatNumber($document->factor, 9),
                    $this->formatNumber($document->total_paid, 2),
                    $this->formatNumber($document->pending_amount, 2),
                ]);
            }

            // Agregar gastos adicionales por tipo
            foreach (ExpenseType::cases() as $expenseType) {
                $expenses = $expensesByType->get($expenseType->value);
                if ($expenses && isset($expenses[$i])) {
                    $expense = $expenses[$i];
                    // El índice base 32 es después de los items (26 + 6 columnas de items)
                    $startIndex = 32 + ($expenseType->ordinal() * 3);
                    $row[$startIndex] = $this->formatNumber($expense->expense_quantity, 2);
                    $row[$startIndex + 1] = $this->formatNumber($expense->expense_amount, 2);
                    $row[$startIndex + 2] = $expense->payment_status->getLabel();
                }
            }

            $row[] = ''; // Agregar espacio vacío en las filas intermedias

            $rows[] = $row;
        }

        // Agregar filas de totales (corregir índices)
        $totalRow = array_fill(0, count($this->getFlattenedColumns()), '');
        $totalRow[0] = 'TOTALES';

        // Totales de items
        $totalRow[28] = $this->formatNumber($order->items->sum('quantity'), 2); // Total cantidad
        $totalRow[29] = $this->formatNumber($order->items->sum('total_price'), 4); // Total precio
        $totalRow[30] = ''; // No aplica promedio unit_price
        $totalRow[31] = $this->formatNumber($order->items->sum('cif_unit'), 4); // Total CIF

        // Totales de documentos
        $totalRow[19] = $this->formatNumber($order->documents->sum('fob_total'), 2); // FOB
        $totalRow[20] = $this->formatNumber($order->documents->sum('freight_total'), 2); // Flete
        $totalRow[21] = $this->formatNumber($order->documents->sum('insurance_total'), 2); // Seguro
        $totalRow[22] = $this->formatNumber($order->documents->sum('cif_total'), 2); // CIF
        $totalRow[23] = ''; // Factor no aplica en totales
        $totalRow[24] = $this->formatNumber($order->documents->sum('total_paid'), 2); // Pagado
        $totalRow[25] = $this->formatNumber($order->documents->sum('pending_amount'), 2); // Pendiente

        // Totales por tipo de gasto
        $startColumn = 32;
        foreach (ExpenseType::cases() as $expenseType) {
            $expenses = $expensesByType->get($expenseType->value, collect());
            $totalQuantity = $expenses->sum('expense_quantity');
            $totalAmount = $expenses->sum('expense_amount');
            $totalCost = $expenses->sum(fn($e) => $e->expense_quantity * $e->expense_amount);

            $totalRow[$startColumn + ($expenseType->ordinal() * 3)] = $this->formatNumber($totalQuantity, 2);
            $totalRow[$startColumn + ($expenseType->ordinal() * 3) + 1] = $this->formatNumber($totalAmount, 2);
            // No incluimos estado en totales
        }

        // Calcular el gran total
        $granTotal = 0;

        // Sumar totales de documentos
        $granTotal += $order->documents->sum('cif_total');

        // Sumar totales por tipo de gasto
        foreach (ExpenseType::cases() as $expenseType) {
            $expenses = $expensesByType->get($expenseType->value, collect());
            $granTotal += $expenses->sum(fn($e) => $e->expense_quantity * $e->expense_amount);
        }

        // Agregar una columna más al final con el gran total
        $totalRow[] = $this->formatNumber($granTotal, 2);

        $rows[] = $totalRow;

        return $rows;
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
                'items',
                'shippingLines.containers', // Removido .type ya que es un enum
                'documents',
                'expenses'
            ])
            ->first();

        return $this->getData($order);
    }

    public function headings(): array
    {
        return $this->getFlattenedColumns();
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 15,  // Tienda
            'B' => 20,  // Proveedor
            'C' => 20,  // País Origen
            'D' => 15,  // Referencia
            'E' => 20,  // Ref. Externa
            'F' => 15,  // Num. SVE
            'G' => 10,  // Tipo
            'H' => 15,  // Estado
            'I' => 15,  // Fecha Orden
            'J' => 20,  // Naviera
            'K' => 15,  // Salida Est.
            'L' => 15,  // Llegada Est.
            'M' => 20,  // Num. Contenedor
            'N' => 20,  // Tipo Contenedor
            'O' => 15,  // Peso (KG)
            'P' => 15,  // Costo Flete
            'Q' => 20,  // Num. Documento
            'R' => 15,  // Tipo Doc.
            'S' => 15,  // Clausula
            'T' => 15,  // FOB
            'U' => 15,  // Flete
            'V' => 15,  // Seguro
            'W' => 15,  // CIF
            'X' => 15,  // Factor
            'Y' => 15,  // Pagado
            'Z' => 15,  // Pendiente
            'AA' => 30, // Producto
            'AB' => 15, // Bultos
            'AC' => 15, // Cantidad
            'AD' => 15, // Precio Total
            'AE' => 15, // Precio Unitario
            'AF' => 15, // CIF Unitario
        ];

        // Agregar anchos para las columnas de gastos
        $expenseColumns = array_merge(...array_map(
            fn($type) => array_map(
                fn($header) => "{$type} - {$header}",
                self::EXPENSE_HEADERS
            ),
            array_keys(self::EXPENSE_COLUMNS)
        ));

        $currentColumn = 'AG';
        foreach ($expenseColumns as $column) {
            $widths[$currentColumn] = 20;
            $currentColumn++;
        }

        // Agregar ancho para la columna del total general
        $widths[$currentColumn] = 25;

        return $widths;
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $this->getLastColumnLetter($sheet->getHighestColumn());
        $lastRow = $sheet->getHighestRow();

        return [
            // Estilo del encabezado
            '1' => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F81BD'],
                ],
            ],
            // Bordes y alineación para toda la tabla
            "A1:{$lastColumn}{$lastRow}" => [
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                'alignment' => ['vertical' => 'center'],
            ],
            // Alineación para números
            "T2:{$lastColumn}{$lastRow}" => [
                'alignment' => ['horizontal' => 'right'],
            ],
            // Estilo para la fila de totales y total general
            $lastRow => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E6E6E6'],
                ],
            ],
            "{$lastColumn}{$lastRow}" => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFD700'],
                ],
                'alignment' => ['horizontal' => 'right'],
            ],
        ];
    }

    private function getLastColumnLetter($column)
    {
        $length = strlen($column);
        $numbers = array_map(fn($char) => ord($char) - 64, str_split($column));

        $number = 0;
        for ($i = 0; $i < $length; $i++) {
            $number += $numbers[$i] * pow(26, $length - $i - 1);
        }

        return $this->numberToColumnLetter($number);
    }

    private function numberToColumnLetter($number)
    {
        $letter = '';
        while ($number > 0) {
            $temp = ($number - 1) % 26;
            $letter = chr($temp + 65) . $letter;
            $number = (int)(($number - $temp - 1) / 26);
        }
        return $letter;
    }
}
