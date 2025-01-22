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
            // 'Num. SVE',
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

    private function formatNumber($number, $decimals = 2): float
    {
        return round(floatval($number), $decimals);
    }

    private function getColumnIndexes(): array
    {
        $baseIndex = 0;

        // Calcular el índice total_general correctamente
        $expenseCount = count(ExpenseType::cases());
        $totalGeneralIndex = 31 + ($expenseCount * 3); // 31 columnas base + (cantidad de tipos de gastos * 3 columnas por gasto)

        return [
            'base' => [
                'store' => $baseIndex++,         // 0
                'provider' => $baseIndex++,      // 1
                'country' => $baseIndex++,       // 2
                'reference' => $baseIndex++,     // 3
                'external_ref' => $baseIndex++,  // 4
                'type' => $baseIndex++,         // 5
                'status' => $baseIndex++,       // 6
                'date' => $baseIndex++,         // 7
            ],
            'shipping' => [
                'line' => $baseIndex++,         // 8
                'departure' => $baseIndex++,    // 9
                'arrival' => $baseIndex++,      // 10
            ],
            'container' => [
                'number' => $baseIndex++,       // 11
                'type' => $baseIndex++,         // 12
                'weight' => $baseIndex++,       // 13
                'cost' => $baseIndex++,         // 14
            ],
            'document' => [
                'number' => $baseIndex++,       // 15
                'type' => $baseIndex++,         // 16
                'clause' => $baseIndex++,       // 17
                'fob' => $baseIndex++,          // 18
                'freight' => $baseIndex++,      // 19
                'insurance' => $baseIndex++,    // 20
                'cif' => $baseIndex++,          // 21
                'factor' => $baseIndex++,       // 22
                'paid' => $baseIndex++,         // 23
                'pending' => $baseIndex++,      // 24
            ],
            'item' => [
                'product' => $baseIndex++,      // 25
                'packages' => $baseIndex++,     // 26
                'quantity' => $baseIndex++,     // 27
                'total_price' => $baseIndex++,  // 28
                'unit_price' => $baseIndex++,   // 29
                'cif_unit' => $baseIndex++,     // 30
            ],
            'expenses_start' => 31,
            'total_general' => $totalGeneralIndex
        ];
    }

    private function calculateTotals($order, array &$totalRow): void
    {
        $indexes = $this->getColumnIndexes();

        // Documentos
        $docTotals = $order->documents->reduce(function ($carry, $doc) {
            return [
                'fob' => $carry['fob'] + floatval($doc->fob_total),
                'freight' => $carry['freight'] + floatval($doc->freight_total),
                'insurance' => $carry['insurance'] + floatval($doc->insurance_total),
                'cif' => $carry['cif'] + floatval($doc->cif_total),
                'paid' => $carry['paid'] + floatval($doc->total_paid),
                'pending' => $carry['pending'] + floatval($doc->pending_amount)
            ];
        }, ['fob' => 0, 'freight' => 0, 'insurance' => 0, 'cif' => 0, 'paid' => 0, 'pending' => 0]);

        $totalRow[$indexes['document']['fob']] = $this->formatNumber($docTotals['fob']);
        $totalRow[$indexes['document']['freight']] = $this->formatNumber($docTotals['freight']);
        $totalRow[$indexes['document']['insurance']] = $this->formatNumber($docTotals['insurance']);
        $totalRow[$indexes['document']['cif']] = $this->formatNumber($docTotals['cif']);
        $totalRow[$indexes['document']['paid']] = $this->formatNumber($docTotals['paid']);
        $totalRow[$indexes['document']['pending']] = $this->formatNumber($docTotals['pending']);

        // Items
        $itemTotals = $order->items->reduce(function ($carry, $item) {
            $cifUnit = $item->cif_unit > 0 ? floatval($item->cif_unit) : (floatval($item->quantity) > 0 ? floatval($item->total_price) / floatval($item->quantity) : 0);

            return [
                'quantity' => $carry['quantity'] + floatval($item->quantity),
                'total' => $carry['total'] + floatval($item->total_price),
                'cif' => $carry['cif'] + ($cifUnit * floatval($item->quantity))
            ];
        }, ['quantity' => 0, 'total' => 0, 'cif' => 0]);

        $totalRow[$indexes['item']['quantity']] = $this->formatNumber($itemTotals['quantity']);
        $totalRow[$indexes['item']['total_price']] = $this->formatNumber($itemTotals['total']);
        $totalRow[$indexes['item']['cif_unit']] = $this->formatNumber($itemTotals['cif']);

        // Gastos
        $expenseTotal = 0;
        foreach (ExpenseType::cases() as $type) {
            $startIdx = $indexes['expenses_start'] + ($type->ordinal() * 3);
            $expenses = $order->expenses->where('expense_type', $type->value);

            $amount = $expenses->sum('expense_amount');
            $expenseTotal += $amount;

            $totalRow[$startIdx] = $this->formatNumber($expenses->sum('expense_quantity'));
            $totalRow[$startIdx + 1] = $this->formatNumber($amount);
        }

        // Total General (CIF + Gastos)
        $totalRow[$indexes['total_general']] = $this->formatNumber($docTotals['cif'] + $expenseTotal);

        // Asegurarnos que la última columna (BP) esté vacía
        $totalRow[] = '';
    }

    private function getData($order): array
    {
        $rows = $this->getDataRows($order);

        // Agregar fila de totales
        $totalRow = array_fill(0, count($this->getFlattenedColumns()), '');
        $totalRow[0] = 'TOTALES';

        $this->calculateTotals($order, $totalRow);

        $rows[] = $totalRow;

        return $rows;
    }

    private function getDataRows($order): array
    {
        $rows = [];
        $maxItems = $order->items->count();
        $maxShippingLineContainers = $order->comexShippingLineContainers->count();
        $maxDocs = $order->documents->count();
        $maxExpenses = $order->expenses->count();
        $maxRows = max($maxItems, $maxShippingLineContainers, $maxDocs, $maxExpenses);

        // Primera fila con datos principales
        $firstRow = [
            $order->store->name,
            $order->provider?->name,
            $order->originCountry?->country_name,
            $order->reference_number,
            $order->external_reference,
            // $order->sve_registration_number,
            $order->type->getLabel(),
            $order->status->getLabel(),
            $order->order_date?->format('d/m/Y'),
        ];

        // Modificar la sección de primera naviera
        $firstShippingLineContainer = $order->comexShippingLineContainers->first();
        if ($firstShippingLineContainer) {
            $event = $firstShippingLineContainer->events;
            $firstRow = array_merge($firstRow, [
                $firstShippingLineContainer->shippingLine->name,
                $event?->start_at?->format('d/m/Y'),
                $event?->end_at?->format('d/m/Y'),
            ]);
        } else {
            $firstRow = array_merge($firstRow, array_fill(0, 3, ''));
        }

        // Agregar primer contenedor
        $firstContainer = $firstShippingLineContainer?->containers->first();
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
            $cifUnit = $firstItem->cif_unit > 0 ? floatval($firstItem->cif_unit) : (floatval($firstItem->quantity) > 0 ? floatval($firstItem->total_price) / floatval($firstItem->quantity) : 0);

            $firstRow = array_merge($firstRow, [
                $firstItem->product?->product_name ?? 'N/A',
                $firstItem->package_quality,
                $this->formatNumber(floatval($firstItem->quantity), 2),
                $this->formatNumber(floatval($firstItem->total_price), 4),
                $this->formatNumber(floatval($firstItem->unit_price), 4),
                $this->formatNumber($cifUnit, 4),
            ]);
        } else {
            $firstRow = array_merge($firstRow, array_fill(0, 6, '')); // Espacios vacíos para item
        }

        // Reemplazar la sección de gastos con columnas detalladas
        $expensesByType = $order->expenses->groupBy('expense_type');
        $expensesStartIndex = 31; // Índice fijo donde empiezan los gastos

        foreach (ExpenseType::cases() as $expenseType) {
            $expenses = $expensesByType->get($expenseType->value);
            $startIndex = $expensesStartIndex + ($expenseType->ordinal() * 3);

            if ($expenses && $expenses->first()) {
                $expense = $expenses->first();
                $firstRow[$startIndex] = $this->formatNumber($expense->expense_quantity, 2);
                $firstRow[$startIndex + 1] = $this->formatNumber($expense->expense_amount, 2);
                $firstRow[$startIndex + 2] = $expense->payment_status->getLabel();
            } else {
                array_splice($firstRow, $startIndex, 3, array_fill(0, 3, ''));
            }
        }

        $firstRow[] = ''; // Agregar espacio vacío en la primera fila

        $rows[] = $firstRow;

        // Filas adicionales
        for ($i = 1; $i < $maxRows; $i++) {
            $row = array_fill(0, count($this->getFlattenedColumns()) + 1, ''); // +1 para la columna extra

            // Agregar item adicional si existe
            if ($i < $maxItems) {
                $item = $order->items[$i];
                $cifUnit = $item->cif_unit > 0 ? floatval($item->cif_unit) : (floatval($item->quantity) > 0 ? floatval($item->total_price) / floatval($item->quantity) : 0);

                // Usar los índices correctos del array
                $row[25] = $item->product?->product_name ?? 'N/A';
                $row[26] = $item->package_quality;
                $row[27] = $this->formatNumber(floatval($item->quantity), 2);
                $row[28] = $this->formatNumber(floatval($item->total_price), 4);
                $row[29] = $this->formatNumber(floatval($item->unit_price), 4);
                $row[30] = $this->formatNumber($cifUnit, 4);
            }

            // Agregar datos de naviera y contenedor adicional si existe
            if ($i < $maxShippingLineContainers) {
                $shippingLineContainer = $order->comexShippingLineContainers[$i];
                $event = $shippingLineContainer->events;

                // Usar asignación directa en lugar de array_splice
                $row[8] = $shippingLineContainer->shippingLine->name;
                $row[9] = $event?->start_at?->format('d/m/Y');
                $row[10] = $event?->end_at?->format('d/m/Y');

                // Agregar contenedor asociado
                $container = $shippingLineContainer->containers->first();
                if ($container) {
                    $row[11] = $container->container_number;
                    $row[12] = $container->type->getLabel();
                    $row[13] = $this->formatNumber($container->weight, 2);
                    $row[14] = $this->formatNumber($container->cost, 2);
                }
            }

            // Agregar documento adicional si existe
            if ($i < $maxDocs) {
                $document = $order->documents[$i];
                // Usar asignación directa en lugar de array_splice
                $row[15] = $document->document_number;
                $row[16] = $document->document_type->getLabel();
                $row[17] = $document->document_clause?->getLabel();
                $row[18] = $this->formatNumber($document->fob_total, 2);
                $row[19] = $this->formatNumber($document->freight_total, 2);
                $row[20] = $this->formatNumber($document->insurance_total, 2);
                $row[21] = $this->formatNumber($document->cif_total, 2);
                $row[22] = $this->formatNumber($document->factor, 9);
                $row[23] = $this->formatNumber($document->total_paid, 2);
                $row[24] = $this->formatNumber($document->pending_amount, 2);
            }

            // Modificar la sección de gastos
            foreach (ExpenseType::cases() as $expenseType) {
                $expenses = $expensesByType->get($expenseType->value);
                $startIndex = $expensesStartIndex + ($expenseType->ordinal() * 3);

                // Obtener todos los gastos del mismo tipo
                if ($expenses && $expenses->count() > $i) {
                    $expense = $expenses[$i];
                    $row[$startIndex] = $this->formatNumber($expense->expense_quantity, 2);
                    $row[$startIndex + 1] = $this->formatNumber($expense->expense_amount, 2);
                    $row[$startIndex + 2] = $expense->payment_status->getLabel();
                } else {
                    // Si no hay más gastos de este tipo, dejar las celdas vacías
                    $row[$startIndex] = '';
                    $row[$startIndex + 1] = '';
                    $row[$startIndex + 2] = '';
                }
            }

            $row[] = ''; // Agregar espacio vacío en las filas intermedias

            $rows[] = $row;
        }

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
                'comexShippingLineContainers.shippingLine',
                'comexShippingLineContainers.containers',
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
            'A' => 25,  // Tienda
            'B' => 20,  // Proveedor
            'C' => 20,  // País Origen
            'D' => 25,  // Referencia
            'E' => 20,  // Ref. Externa
            'F' => 25,  // Num. SVE
            'G' => 10,  // Tipo
            'H' => 25,  // Estado
            'I' => 25,  // Fecha Orden
            'J' => 20,  // Naviera
            'K' => 25,  // Salida Est.
            'L' => 25,  // Llegada Est.
            'M' => 20,  // Num. Contenedor
            'N' => 20,  // Tipo Contenedor
            'O' => 25,  // Peso (KG)
            'P' => 25,  // Costo Flete
            'Q' => 20,  // Num. Documento
            'R' => 25,  // Tipo Doc.
            'S' => 25,  // Clausula
            'T' => 25,  // FOB
            'U' => 25,  // Flete
            'V' => 25,  // Seguro
            'W' => 25,  // CIF
            'X' => 25,  // Factor
            'Y' => 25,  // Pagado
            'Z' => 25,  // Pendiente
            'AA' => 30, // Producto
            'AB' => 25, // Bultos
            'AC' => 25, // Cantidad
            'AD' => 25, // Precio Total
            'AE' => 25, // Precio Unitario
            'AF' => 25, // CIF Unitario
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
        $lastColumn = 'BP'; // Columna fija
        $totalGeneralColumn = 'BP'; // Columna fija para el total general
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
            // Estilo para la fila de totales
            $lastRow => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E6E6E6'],
                ],
            ],
            // Estilo específico para la celda del total general
            "{$totalGeneralColumn}{$lastRow}" => [
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
