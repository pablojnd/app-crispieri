<?php

namespace App\Exports;

use App\Models\ComexImportOrder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Filament\Facades\Filament;

class ComexImportOrderExport implements FromArray, WithHeadings
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
            $order->originCountry->name,
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
}
