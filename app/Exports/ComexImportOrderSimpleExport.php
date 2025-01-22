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

class ComexImportOrderSimpleExport implements FromArray, WithHeadings, WithColumnWidths, WithStyles
{
    private const COLUMNS = [
        'Proveedor',
        'País Origen',
        'Naviera',
        'Salida Est.',
        'Llegada Est.',
        'Num. Contenedor',
        'Tipo Contenedor',
        'Peso (KG)',
        'Producto',
        'Bultos',
        'Cantidad'
    ];

    protected $record;

    public function __construct(ComexImportOrder $record)
    {
        $this->record = $record;
    }

    private function formatNumber($number, $decimals = 2): float
    {
        return round(floatval($number), $decimals);
    }

    public function array(): array
    {
        $tenant = Filament::getTenant();
        $order = ComexImportOrder::query()
            ->where('store_id', $tenant->id)
            ->where('id', $this->record->id)
            ->with([
                'provider',
                'originCountry',
                'items.product',
                'comexShippingLineContainers.shippingLine',
                'comexShippingLineContainers.containers',
                'comexShippingLineContainers.events',
            ])
            ->first();

        return $this->getData($order);
    }

    private function getData($order): array
    {
        $rows = [];
        $maxItems = $order->items->count();
        $maxContainers = $order->comexShippingLineContainers->count();
        $maxRows = max($maxItems, $maxContainers);

        // Primera fila
        $firstShippingLineContainer = $order->comexShippingLineContainers->first();
        $firstContainer = $firstShippingLineContainer?->containers->first();
        $firstItem = $order->items->first();
        $event = $firstShippingLineContainer?->events;

        $firstRow = [
            $order->provider?->name ?? '',
            $order->originCountry?->country_name ?? '',
            $firstShippingLineContainer?->shippingLine->name ?? '',
            $event?->start_at?->format('d/m/Y') ?? '',
            $event?->end_at?->format('d/m/Y') ?? '',
            $firstContainer?->container_number ?? '',
            $firstContainer?->type->getLabel() ?? '',
            $this->formatNumber($firstContainer?->weight ?? 0),
            $firstItem?->product?->product_name ?? '',
            $firstItem?->package_quality ?? '',
            $this->formatNumber($firstItem?->quantity ?? 0),
        ];

        $rows[] = $firstRow;

        // Filas adicionales
        for ($i = 1; $i < $maxRows; $i++) {
            $row = array_fill(0, count(self::COLUMNS), '');

            if ($i < $maxContainers) {
                $shippingLineContainer = $order->comexShippingLineContainers[$i];
                $container = $shippingLineContainer->containers->first();
                $event = $shippingLineContainer->events;

                $row[2] = $shippingLineContainer->shippingLine->name;
                $row[3] = $event?->start_at?->format('d/m/Y');
                $row[4] = $event?->end_at?->format('d/m/Y');
                $row[5] = $container?->container_number;
                $row[6] = $container?->type->getLabel();
                $row[7] = $this->formatNumber($container?->weight ?? 0);
            }

            if ($i < $maxItems) {
                $item = $order->items[$i];
                $row[8] = $item->product?->product_name;
                $row[9] = $item->package_quality;
                $row[10] = $this->formatNumber($item->quantity);
            }

            $rows[] = $row;
        }

        return $rows;
    }

    public function headings(): array
    {
        return self::COLUMNS;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,  // Proveedor
            'B' => 20,  // País Origen
            'C' => 20,  // Naviera
            'D' => 15,  // Salida Est.
            'E' => 15,  // Llegada Est.
            'F' => 20,  // Num. Contenedor
            'G' => 20,  // Tipo Contenedor
            'H' => 15,  // Peso (KG)
            'I' => 30,  // Producto
            'J' => 15,  // Bultos
            'K' => 15,  // Cantidad
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = 'K';
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
            "H2:{$lastColumn}{$lastRow}" => [
                'alignment' => ['horizontal' => 'right'],
            ],
        ];
    }
}
