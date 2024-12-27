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

class ComexImportOrderExport implements FromArray, WithHeadings, WithColumnWidths, WithStyles
{
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
        'Contacto',
        'Teléfono',
        'Email',
        'Salida Est.',
        'Salida Real',
        'Llegada Est.',
        'Llegada Real',
        // Contenedores
        'Num. Contenedor',
        'Tipo Contenedor',
        'Peso (KG)',
        'Costo',
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
        // Gastos
        'Tipo Gasto',
        'Cantidad',
        'Monto',
        'Total Gasto',
    ];

    protected $record;

    public function __construct(ComexImportOrder $record)
    {
        $this->record = $record;
    }

    private function getData($order): array
    {
        $rows = [];
        $maxShippingLines = $order->shippingLines->count();
        $maxContainers = $order->shippingLines->flatMap->containers->count();
        $maxDocs = $order->documents->count();
        $maxExpenses = $order->expenses->count();
        $maxRows = max($maxShippingLines, $maxContainers, $maxDocs, $maxExpenses);

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

        // Agregar primera naviera y su contenedor si existen
        $firstShippingLine = $order->shippingLines->first();
        if ($firstShippingLine) {
            $firstRow = array_merge($firstRow, [
                $firstShippingLine->name,
                $firstShippingLine->contact_person,
                $firstShippingLine->phone,
                $firstShippingLine->email,
                $firstShippingLine->estimated_departure?->format('d/m/Y'),
                $firstShippingLine->actual_departure?->format('d/m/Y'),
                $firstShippingLine->estimated_arrival?->format('d/m/Y'),
                $firstShippingLine->actual_arrival?->format('d/m/Y'),
            ]);

            // Agregar primer contenedor
            $firstContainer = $firstShippingLine->containers->first();
            if ($firstContainer) {
                $firstRow = array_merge($firstRow, [
                    $firstContainer->container_number,
                    $firstContainer->type->getLabel(),
                    number_format($firstContainer->weight, 2),
                    number_format($firstContainer->cost, 2),
                ]);
            } else {
                $firstRow = array_merge($firstRow, array_fill(0, 4, '')); // Espacios vacíos para contenedor
            }
        } else {
            $firstRow = array_merge($firstRow, array_fill(0, 12, '')); // Espacios vacíos para naviera y contenedor
        }

        // Agregar primer documento
        $firstDoc = $order->documents->first();
        if ($firstDoc) {
            $firstRow = array_merge($firstRow, [
                $firstDoc->document_number,
                $firstDoc->document_type->getLabel(),
                $firstDoc->document_clause?->getLabel(),
                number_format($firstDoc->fob_total, 2),
                number_format($firstDoc->freight_total, 2),
                number_format($firstDoc->insurance_total, 2),
                number_format($firstDoc->cif_total, 2),
                number_format($firstDoc->factor, 9),
                number_format($firstDoc->total_paid, 2),
                number_format($firstDoc->pending_amount, 2),
            ]);
        } else {
            $firstRow = array_merge($firstRow, array_fill(0, 10, '')); // Espacios vacíos para documento
        }

        // Agregar primer gasto
        $firstExpense = $order->expenses->first();
        if ($firstExpense) {
            $firstRow = array_merge($firstRow, [
                $firstExpense->expense_type->getLabel(),
                number_format($firstExpense->expense_quantity, 2),
                number_format($firstExpense->expense_amount, 2),
                number_format($firstExpense->expense_quantity * $firstExpense->expense_amount, 2),
            ]);
        } else {
            $firstRow = array_merge($firstRow, array_fill(0, 4, '')); // Espacios vacíos para gastos
        }

        $rows[] = $firstRow;

        // Filas adicionales
        for ($i = 1; $i < $maxRows; $i++) {
            $row = array_fill(0, count(self::COLUMNS), ''); // Inicializar fila vacía

            // Agregar datos de naviera adicional si existe
            if ($i < $maxShippingLines) {
                $shippingLine = $order->shippingLines[$i];
                array_splice($row, 9, 8, [
                    $shippingLine->name,
                    $shippingLine->contact_person,
                    $shippingLine->phone,
                    $shippingLine->email,
                    $shippingLine->estimated_departure?->format('d/m/Y'),
                    $shippingLine->actual_departure?->format('d/m/Y'),
                    $shippingLine->estimated_arrival?->format('d/m/Y'),
                    $shippingLine->actual_arrival?->format('d/m/Y'),
                ]);

                // Contenedor de esta naviera si existe
                $container = $shippingLine->containers[$i] ?? null;
                if ($container) {
                    array_splice($row, 17, 4, [
                        $container->container_number,
                        $container->type->getLabel(),
                        number_format($container->weight, 2),
                        number_format($container->cost, 2),
                    ]);
                }
            }

            // Agregar documento adicional si existe
            if ($i < $maxDocs) {
                $document = $order->documents[$i];
                array_splice($row, 21, 10, [
                    $document->document_number,
                    $document->document_type->getLabel(),
                    $document->document_clause?->getLabel(),
                    number_format($document->fob_total, 2),
                    number_format($document->freight_total, 2),
                    number_format($document->insurance_total, 2),
                    number_format($document->cif_total, 2),
                    number_format($document->factor, 9),
                    number_format($document->total_paid, 2),
                    number_format($document->pending_amount, 2),
                ]);
            }

            // Agregar gasto adicional si existe
            if ($i < $maxExpenses) {
                $expense = $order->expenses[$i];
                array_splice($row, 31, 4, [
                    $expense->expense_type->getLabel(),
                    number_format($expense->expense_quantity, 2),
                    number_format($expense->expense_amount, 2),
                    number_format($expense->expense_quantity * $expense->expense_amount, 2),
                ]);
            }

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
                'shippingLines.containers', // Removido .type ya que es un enum
                'documents',
                'expenses'
            ])
            ->first();

        return $this->getData($order);
    }

    public function headings(): array
    {
        return self::COLUMNS;
    }

    public function columnWidths(): array
    {
        return [
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
            'K' => 20,  // Contacto
            'L' => 15,  // Teléfono
            'M' => 25,  // Email
            'N' => 15,  // Salida Est.
            'O' => 15,  // Salida Real
            'P' => 15,  // Llegada Est.
            'Q' => 15,  // Llegada Real
            'R' => 20,  // Num. Contenedor
            'S' => 20,  // Tipo Contenedor
            'T' => 15,  // Peso (KG)
            'U' => 15,  // Costo
            'V' => 20,  // Num. Documento
            'W' => 15,  // Tipo Doc.
            'X' => 15,  // Clausula
            'Y' => 15,  // FOB
            'Z' => 15,  // Flete
            'AA' => 15, // Seguro
            'AB' => 15, // CIF
            'AC' => 15, // Factor
            'AD' => 15, // Pagado
            'AE' => 15, // Pendiente
            'AF' => 20, // Tipo Gasto
            'AG' => 12, // Cantidad
            'AH' => 12, // Monto
            'AI' => 15, // Total Gasto
        ];
    }

    public function styles(Worksheet $sheet)
    {
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
            'A1:AI' . $sheet->getHighestRow() => [
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                'alignment' => ['vertical' => 'center'],
            ],
            // Alineación para números
            'T2:AI' . $sheet->getHighestRow() => [
                'alignment' => ['horizontal' => 'right'],
            ],
        ];
    }
}
