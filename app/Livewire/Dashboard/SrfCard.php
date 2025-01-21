<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class SrfCard extends Component
{
    public $data = [];
    protected $apiBaseUrl = 'http://localhost:3000/api';

    protected $listeners = ['filtrosActualizados' => 'actualizarDatos'];

    public function mount()
    {
        $this->actualizarDatos([
            'tipoFiltro' => 'fecha',
            'fecha' => now()->format('Y-m-d')
        ]);
    }

    public function actualizarDatos($filtros)
    {
        try {
            $params = [];
            if ($filtros['tipoFiltro'] === 'fecha') {
                $params['date'] = $filtros['fecha'];
            } else {
                $params['startDate'] = $filtros['fechaInicio'];
                $params['endDate'] = $filtros['fechaFin'];
            }

            $response = Http::get("{$this->apiBaseUrl}/notas-venta-clientes", $params);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['success']) {
                    $this->data = $data['data'];
                }
            }
        } catch (\Exception $e) {
            $this->data = [
                'clientes' => [],
                'totalGeneral' => 0
            ];
        }
    }

    public function render()
    {
        return view('livewire.dashboard.srf-card');
    }
}
