<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class BoletasCard extends Component
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
            'CantidadBoletas' => 0,
            'MontoTotalBoletas' => 0,
            'BoletasGalpon' => 0,
            'BoletasOtros' => 0,
            'BoletasAnuladas' => 0,
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

        $boletasAnuladas = $documentos
            ->where('TipoDocumento', 'BOLETA')
            ->where('EstadoDocumento', 'ANULADA')
            ->count();

        $this->data = [
            'CantidadBoletas' => $resumen['general']['cantidades']['boletas'] ?? 0,
            'MontoTotalBoletas' => $sucursalData['boletas']['total'] ?? 0,
            'BoletasGalpon' => $sucursalData['boletas']['totalSinNV'] ?? 0,
            'BoletasOtros' => $sucursalData['boletas']['conNotasVenta']['total'] ?? 0,
            'BoletasAnuladas' => $boletasAnuladas,
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.boletas-card');
    }
}
