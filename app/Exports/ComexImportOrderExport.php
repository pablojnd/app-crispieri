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
            $order->provider->name,
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
                $item->product->name ?? 'N/A',
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
                'containers',
                'documents.items.product',
                'documents.payments'
            ])
            ->first();

        $rows = [];

        foreach ($order->documents as $index => $document) {
            $rows[] = array_merge(
                $index === 0 ? $this->getOrderData($order) : array_fill(0, count(self::ORDER_COLUMNS), ''),
                $this->getDocumentData($document)
            );
        }

        return $rows;
    }

    public function headings(): array
    {
        return array_merge(self::ORDER_COLUMNS, self::DOCUMENT_COLUMNS);
    }
}
