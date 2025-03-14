<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class OrdersSummarySheet implements FromArray, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $orders;

    public function __construct(Collection $orders)
    {
        $this->orders = $orders;
    }

    public function title(): string
    {
        return 'Resumen de Órdenes';
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->orders as $order) {
            // Calcular los totales
            $totalCIF = $order->documents->sum('cif_total');
            $totalExpenses = $order->expenses->sum('expense_amount');

            $rows[] = [
                $order->reference_number,
                $order->provider ? $order->provider->name : 'N/A',
                $order->originCountry ? $order->originCountry->country_name : 'N/A',
                $order->type->getLabel(),
                $order->status->getLabel(),
                $order->order_date?->format('d/m/Y'),
                $order->items->count(),
                (string) $this->formatNumber($order->items->sum('quantity')), // Convertir a string
                (string) $this->formatNumber($totalCIF),                      // para evitar
                (string) $this->formatNumber($totalExpenses),                 // problemas de formato
                (string) $this->formatNumber($totalCIF + $totalExpenses),     // con números
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Referencia',
            'Proveedor',
            'País Origen',
            'Tipo Transporte',
            'Estado',
            'Fecha Orden',
            'Total Items',
            'Cantidad Total',
            'Total CIF',
            'Total Gastos',
            'Total General',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->orders->count() + 1;

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F81BD'],
                ],
                'alignment' => ['horizontal' => 'center']
            ],
            // Aplicar formato numérico a las columnas específicas
            "H2:K{$lastRow}" => [
                'alignment' => ['horizontal' => 'right'],
                'numberFormat' => ['formatCode' => '#,##0.00']
            ],
        ];
    }

    private function formatNumber($number, $decimals = 2): string
    {
        if (empty($number)) return '0.00';
        return (string) round(floatval($number), $decimals);
    }
}
