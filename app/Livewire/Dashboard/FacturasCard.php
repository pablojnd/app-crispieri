<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class FacturasCard extends Component
{
    public $data = [];
    protected $apiBaseUrl = 'http://localhost:3000/api';

    protected $listeners = ['filtrosActualizados' => 'actualizarDatos'];

    public function mount()
    {
        $this->initializeData();
        $this->actualizarDatos([
            'tipoFiltro' => 'fecha',
            'fecha' => now()->format('Y-m-d'),
            'sucursal' => 'GALPON'
        ]);
    }

    private function initializeData()
    {
        $this->data = [
            'CantidadFacturas' => 0,
            'MontoTotalFacturas' => 0,
            'FacturasGalpon' => 0,
            'FacturasOtros' => 0,
            'FacturasAnuladas' => 0,
        ];
    }

    public function actualizarDatos($filtros)
    {
        try {
            $params = [
                'sucursal' => $filtros['sucursal'] ?? 'GALPON'
            ];

            if (($filtros['tipoFiltro'] ?? 'fecha') === 'fecha') {
                $params['fecha'] = $filtros['fecha'] ?? now()->format('Y-m-d');
            } else {
                $params['fechaInicio'] = $filtros['fechaInicio'];
                $params['fechaFin'] = $filtros['fechaFin'];
            }

            $response = Http::get("{$this->apiBaseUrl}/documentos-consolidados", $params);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['success']) {
                    $this->processConsolidatedData($data);
                }
            }
        } catch (\Exception $e) {
            $this->initializeData();
        }
    }

    private function processConsolidatedData($data)
    {
        $documentos = collect($data['data'] ?? []);
        $resumen = $data['resumen'] ?? [];
        $sucursalData = $resumen['porSucursal']['211'] ?? [];

        $facturasAnuladas = $documentos
            ->where('TipoDocumento', 'FACTURA')
            ->where('EstadoDocumento', 'ANULADA')
            ->count();

        $this->data = [
            'CantidadFacturas' => $resumen['general']['cantidades']['facturas'] ?? 0,
            'MontoTotalFacturas' => $sucursalData['facturas']['total'] ?? 0,
            'FacturasGalpon' => $sucursalData['facturas']['totalSinNV'] ?? 0,
            'FacturasOtros' => $sucursalData['facturas']['conNotasVenta']['total'] ?? 0,
            'FacturasAnuladas' => $facturasAnuladas,
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.facturas-card');
    }
}
