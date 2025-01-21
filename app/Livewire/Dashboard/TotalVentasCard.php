<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class TotalVentasCard extends Component
{
    public $data = [];
    public $montoSRF = 0;
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
            'montoTotal' => 0,
            'montoGalpon' => 0,
            'montoOtros' => 0,
            'cantidadDocumentos' => 0,
            'documentosAnulados' => 0,
        ];
        $this->montoSRF = 0;
    }

    public function actualizarDatos($filtros)
    {
        try {
            $params = [
                'sucursal' => 'GALPON'
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
                    $this->loadSRFData($filtros);
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

        $documentosAnulados = $documentos
            ->whereIn('EstadoDocumento', ['ANULADA'])
            ->count();

        $this->data = [
            'montoTotal' => $sucursalData['totales']['general'] ?? 0,
            'montoGalpon' => $sucursalData['totales']['sinNotasVenta'] ?? 0,
            'montoOtros' => $sucursalData['totales']['conNotasVenta'] ?? 0,
            'cantidadDocumentos' => $resumen['general']['cantidades']['total'] ?? 0,
            'documentosAnulados' => $documentosAnulados,
        ];
    }

    private function loadSRFData($filtros)
    {
        try {
            $params = [];
            if (($filtros['tipoFiltro'] ?? 'fecha') === 'fecha') {
                $params['date'] = $filtros['fecha'] ?? now()->format('Y-m-d');
            } else {
                $params['startDate'] = $filtros['fechaInicio'];
                $params['endDate'] = $filtros['fechaFin'];
            }

            $response = Http::get("{$this->apiBaseUrl}/notas-venta-clientes", $params);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['success']) {
                    $this->montoSRF = $data['data']['totalGeneral'] ?? 0;
                }
            }
        } catch (\Exception $e) {
            $this->montoSRF = 0;
        }
    }

    public function render()
    {
        return view('livewire.dashboard.total-ventas-card');
    }
}
