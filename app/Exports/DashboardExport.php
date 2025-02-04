<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DashboardExport implements WithMultipleSheets
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new DashboardTotalSheet($this->data['total']),
            // new DashboardSRFSheet($this->data['srf']),
            // new DashboardFacturasSheet($this->data['facturas']),
            // new DashboardBoletasSheet($this->data['boletas']),
        ];
    }
}

class DashboardTotalSheet implements FromArray, WithTitle, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return [[
            $this->data['montoTotal'],
            $this->data['montoGalpon'],
            $this->data['montoOtros'],
            $this->data['cantidadDocumentos'],
            $this->data['documentosAnulados'],
        ]];
    }

    public function headings(): array
    {
        return [
            'Monto Total',
            'Monto Galpon',
            'Monto Otros',
            'Cantidad Documentos',
            'Documentos Anulados'
        ];
    }

    public function title(): string
    {
        return 'Resumen Total';
    }
}

// Implementaciones similares para las otras hojas...
