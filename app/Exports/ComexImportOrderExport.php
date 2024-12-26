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
        'Número Factura',
        'Cantidad Contenedor',
        'Items',
        'Salida Estimada',
        'Llegada Estimada',
        'Estado',
        'Número de Orden',
        'Costo por Contenedor',
        'Costo Total por Contenedor',
        'FOB',
        'Seguro',
        'Total Documento',
        'Avance',
        'Saldo',
        'CIF',
        'Gasto',
        'Cantidad',
        'Valor',
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
        $maxItems = $order->items->count();
        $maxExpenses = $order->expenses->count();
        $maxDocs = $order->documents->count();
        $maxRows = max($maxItems, $maxExpenses, $maxDocs);

        // Primera fila con datos principales
        $rows[] = [
            $order->store->name,
            $order->provider?->name ?? '',
            $order->documents->first()?->document_number ?? '',
            $order->containers->count(),
            $order->items->first()?->product->product_name ?? '',
            $order->estimated_departure?->format('d/m/Y'),
            $order->estimated_arrival?->format('d/m/Y'),
            $order->status->getLabel(),
            $order->reference_number,
            number_format($order->containers->avg('cost') ?? 0, 2),
            number_format($order->containers->sum('cost') ?? 0, 2),
            number_format($order->documents->sum('fob_total') ?? 0, 2),
            number_format($order->documents->sum('insurance_total') ?? 0, 2),
            number_format($order->documents->sum('cif_total') ?? 0, 2),
            number_format($order->documents->sum('total_paid') ?? 0, 2),
            number_format($order->documents->sum('pending_amount') ?? 0, 2),
            number_format($order->documents->sum('cif_total') ?? 0, 2),
            $order->expenses->first()?->expense_type->getLabel() ?? '',
            number_format($order->expenses->first()?->expense_quantity ?? 0, 2),
            number_format($order->expenses->first()?->expense_amount ?? 0, 2),
            number_format($order->expenses->first()?->expense_quantity * ($order->expenses->first()?->expense_amount ?? 0) ?? 0, 2)
        ];

        // Filas adicionales
        for ($i = 1; $i < $maxRows; $i++) {
            $row = array_fill(0, 21, ''); // Inicializar fila vacía con 21 columnas

            // Agregar documento si existe
            if ($i < $maxDocs) {
                $document = $order->documents[$i];
                $row[2] = $document->document_number; // Número de factura
                $row[11] = number_format($document->fob_total ?? 0, 2); // FOB
                $row[12] = number_format($document->insurance_total ?? 0, 2); // Seguro
                $row[13] = number_format($document->cif_total ?? 0, 2); // Total documento
                $row[14] = number_format($document->total_paid ?? 0, 2); // Avance
                $row[15] = number_format($document->pending_amount ?? 0, 2); // Saldo
                $row[16] = number_format($document->cif_total ?? 0, 2); // CIF
            }

            // Agregar item si existe
            if ($i < $maxItems) {
                $item = $order->items[$i];
                $row[4] = $item->product->product_name ?? ''; // Item
                $row[9] = number_format($item->cif_unit ?? 0, 2); // Costo por unidad
                $row[10] = number_format($item->total_price ?? 0, 2); // Costo total
            }

            // Agregar gasto si existe
            if ($i < $maxExpenses) {
                $expense = $order->expenses[$i];
                $row[17] = $expense->expense_type->getLabel(); // Tipo de gasto
                $row[18] = number_format($expense->expense_quantity ?? 0, 2); // Cantidad
                $row[19] = number_format($expense->expense_amount ?? 0, 2); // Valor
                $row[20] = number_format(($expense->expense_quantity * $expense->expense_amount) ?? 0, 2); // Total
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
                'items.product',
                'containers',
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
            'C' => 20,  // Número Factura
            'D' => 15,  // Cantidad Contenedor
            'E' => 30,  // Items
            'F' => 15,  // Salida Estimada
            'G' => 15,  // Llegada Estimada
            'H' => 15,  // Estado
            'I' => 15,  // Número de Orden
            'J' => 15,  // Costo por Contenedor
            'K' => 15,  // Costo Total por Contenedor
            'L' => 15,  // FOB
            'M' => 15,  // Seguro
            'N' => 15,  // Total Documento
            'O' => 15,  // Avance
            'P' => 15,  // Saldo
            'Q' => 15,  // CIF
            'R' => 20,  // Gasto
            'S' => 12,  // Cantidad
            'T' => 12,  // Valor
            'U' => 15,  // Total Gasto
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
            'A1:U' . $sheet->getHighestRow() => [
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                'alignment' => ['vertical' => 'center'],
            ],
            // Alineación para números
            'J2:U' . $sheet->getHighestRow() => [
                'alignment' => ['horizontal' => 'right'],
            ],
        ];
    }
}
