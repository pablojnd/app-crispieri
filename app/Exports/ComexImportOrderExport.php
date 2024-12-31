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

    private function formatNumber($number, $decimals = 2): string
    {
        return number_format($number, $decimals, ',', '.');
    }

    private function getColumnIndexes(): array
    {
        $baseIndex = 0;
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
            'expenses_start' => $baseIndex,     // 31
            'total_general' => 67              // Última columna
        ];
    }

    private function calculateTotals($order, array &$totalRow): void
    {
        $indexes = $this->getColumnIndexes();

        // Documentos
        $docTotals = $order->documents->reduce(function ($carry, $doc) {
            return [
                'fob' => $carry['fob'] + $doc->fob_total,
                'freight' => $carry['freight'] + $doc->freight_total,
                'insurance' => $carry['insurance'] + $doc->insurance_total,
                'cif' => $carry['cif'] + $doc->cif_total,
                'paid' => $carry['paid'] + $doc->total_paid,
                'pending' => $carry['pending'] + $doc->pending_amount
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
            return [
                'quantity' => $carry['quantity'] + $item->quantity,
                'total' => $carry['total'] + $item->total_price,
                'cif' => $carry['cif'] + ($item->cif_unit * $item->quantity)
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

        // Agregar primera naviera y sus datos
        $firstShippingLineContainer = $order->comexShippingLineContainers->first();
        if ($firstShippingLineContainer) {
            $firstRow = array_merge($firstRow, [
                $firstShippingLineContainer->shippingLine->name,
                $firstShippingLineContainer->estimated_departure?->format('d/m/Y'),
                $firstShippingLineContainer->estimated_arrival?->format('d/m/Y'),
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

            // Agregar datos de naviera y contenedor adicional si existe
            if ($i < $maxShippingLineContainers) {
                $shippingLineContainer = $order->comexShippingLineContainers[$i];
                array_splice($row, 9, 3, [
                    $shippingLineContainer->shippingLine->name,
                    $shippingLineContainer->estimated_departure?->format('d/m/Y'),
                    $shippingLineContainer->estimated_arrival?->format('d/m/Y'),
                ]);

                // Agregar contenedor asociado
                $container = $shippingLineContainer->containers->first();
                if ($container) {
                    array_splice($row, 12, 4, [
                        $container->container_number,
                        $container->type->getLabel(),
                        $this->formatNumber($container->weight, 2),
                        $this->formatNumber($container->cost, 2),
                    ]);
                }
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
