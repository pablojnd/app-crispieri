<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ComexImportOrderExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $comexImportOrder;

    public function __construct($comexImportOrder)
    {
        $this->comexImportOrder = $comexImportOrder->load([
            'provider',
            'documents.containers.items.product',
            'expenses.currency',
        ]);
    }

    public function collection()
    {
        return collect([$this->comexImportOrder]);
    }

    public function headings(): array
    {
        return [
            'Proveedor',
            'Factura',
            'Cantidad Contenedores',
            'Descripción Mercadería',
            'Medidas',
            'Cantidad',
            'UM',
            'Fecha Estimada Embarque',
            'Fecha Estimada Llegada',
            'Estado',
            'Número de Importación',
            'Flete x Contenedor',
            'Flete Total',
            'Fob',
            'Seguro',
            'Monto Total Factura',
            'Avance',
            'Información Pago',
            'Saldo',
            'CIF',
            'Gate In',
            'THC',
            'Apertura Manif',
            'Garantía',
            'Carta Responsabilidad',
            'Emisión BL',
            'Demurrage',
            'Total Gastos Locales',
            'Cantidad',
            'Monto',
            'Sub Total',
            'Estadía',
            'Monto',
            'SubTotal',
            'Total Neto',
            'Cantidad Horas',
            'Valor Hora',
            'Total Neto',
            'Cantidad Personas',
            'Valor Unitario',
            'Total',
            'Costo Total Final',
        ];
    }

    public function map($comexImportOrder): array
    {
        $rows = [];
        $firstRow = true;

        foreach ($comexImportOrder->documents as $document) {
            foreach ($document->containers as $container) {
                $containerMapped = false;

                foreach ($container->items as $item) {
                    $rows[] = [
                        $firstRow ? $comexImportOrder->provider->name ?? 'N/A' : '',
                        $firstRow ? $document->document_number ?? 'N/A' : '',
                        !$containerMapped ? $container->container_number ?? 'N/A' : '',
                        $item->product->name ?? 'N/A',
                        $item->product->dimensions ?? 'N/A',
                        $item->pivot->quantity ?? 'N/A',
                        $item->product->unit_measure ?? 'N/A',
                        $firstRow ? $comexImportOrder->estimated_departure?->format('j F, Y') ?? 'N/A' : '',
                        $firstRow ? $comexImportOrder->estimated_arrival?->format('j F, Y') ?? 'N/A' : '',
                        $firstRow ? $comexImportOrder->status->value ?? 'N/A' : '', // Convert enum to string
                        $firstRow ? $comexImportOrder->reference_number ?? 'N/A' : '',
                        $container->freight_per_container ?? 'N/A',
                        $container->total_freight ?? 'N/A',
                        $document->fob_total ?? 'N/A',
                        $document->insurance_total ?? 'N/A',
                        $document->total_invoice_amount ?? 'N/A',
                        $document->advance ?? 'N/A',
                        $document->payment_info ?? 'N/A',
                        $document->balance ?? 'N/A',
                        $document->cif_total ?? 'N/A',
                        // Aquí puedes agregar más columnas específicas si es necesario
                    ];
                    $firstRow = false;
                    $containerMapped = true;
                }
            }
        }

        foreach ($comexImportOrder->expenses as $expense) {
            $rows[] = [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                $expense->expense_type == 'gate_in' ? $expense->expense_amount : null,
                $expense->expense_type == 'thc' ? $expense->expense_amount : null,
                $expense->expense_type == 'manifest_opening' ? $expense->expense_amount : null,
                $expense->expense_type == 'guarantee' ? $expense->expense_amount : null,
                $expense->expense_type == 'liability_letter' ? $expense->expense_amount : null,
                $expense->expense_type == 'bl_issuance' ? $expense->expense_amount : null,
                $expense->expense_type == 'demurrage' ? $expense->expense_amount : null,
                $expense->total_local_expenses ?? null,
                $expense->quantity ?? null,
                $expense->amount ?? null,
                $expense->sub_total ?? null,
                $expense->stay ?? null,
                $expense->stay_amount ?? null,
                $expense->sub_total_stay ?? null,
                $expense->net_total ?? null,
                $expense->hours_quantity ?? null,
                $expense->hour_value ?? null,
                $expense->net_total_hours ?? null,
                $expense->persons_quantity ?? null,
                $expense->unit_value ?? null,
                $expense->total ?? null,
                $expense->final_total_cost ?? null,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        return [];
    }
}
